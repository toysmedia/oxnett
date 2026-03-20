<?php

namespace App\Services;

use App\Models\Router;

class MikrotikScriptService
{
    protected const WG_KEY_PLACEHOLDER      = 'KEY_NOT_CONFIGURED_SET_WG_SERVER_PUBLIC_KEY_IN_ENV';
    protected const WG_SERVER_TUNNEL_IP     = '10.255.255.1';
    protected const WG_MANAGEMENT_SUBNET    = '10.255.255.0/24';
    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Generate the full 3-phase script (backward-compatible entry point).
     */
    public function generate(Router $router): string
    {
        $ctx = $this->buildContext($router);

        $lines = array_merge(
            $this->buildHeader($router, $ctx),
            $this->buildVariables($ctx),
            $this->generatePhase1Lines($router, $ctx),
            $this->generatePhase2Lines($router, $ctx),
            $this->generatePhase3Lines($router, $ctx)
        );

        return implode("\n", $lines) . "\n";
    }

    /**
     * Generate Phase 1 only — establish connection (identity + WireGuard + ether lockdown).
     */
    public function generatePhase1(Router $router): string
    {
        $ctx = $this->buildContext($router);

        $lines = array_merge(
            $this->buildHeader($router, $ctx, 1),
            $this->buildVariables($ctx),
            $this->generatePhase1Lines($router, $ctx)
        );

        return implode("\n", $lines) . "\n";
    }

    /**
     * Generate Phase 2 only — configure services (VLAN, RADIUS, PPPoE, Hotspot).
     */
    public function generatePhase2(Router $router): string
    {
        $ctx = $this->buildContext($router);

        $lines = array_merge(
            $this->buildHeader($router, $ctx, 2),
            $this->buildVariables($ctx),
            $this->generatePhase2Lines($router, $ctx)
        );

        return implode("\n", $lines) . "\n";
    }

    /**
     * Generate Phase 3 only — security hardening + heartbeat.
     */
    public function generatePhase3(Router $router): string
    {
        $ctx = $this->buildContext($router);

        $lines = array_merge(
            $this->buildHeader($router, $ctx, 3),
            $this->buildVariables($ctx),
            $this->generatePhase3Lines($router, $ctx)
        );

        return implode("\n", $lines) . "\n";
    }

    // ── Sanitization ───────────────────────────────────────────────────────────

    /**
     * Strip characters that could break out of a RouterOS string literal or
     * execute arbitrary commands. Keeps alphanumerics plus safe punctuation.
     */
    protected function sanitizeForRos(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9._:\- ]/', '', $value);
    }

    // ── Context builder ────────────────────────────────────────────────────────

    protected function buildContext(Router $router): array
    {
        $billingServerIp  = $this->resolveRadiusIp();
        $billingPublicKey = $this->resolveWgPublicKey();
        $wgPort           = $this->resolveWgPort();
        $wgEndpoint       = $this->resolveWgEndpoint();

        $callbackUrl        = rtrim(config('app.url'), '/') . '/api/router-callback';
        $heartbeatUrl       = rtrim(config('app.url'), '/') . '/api/router-heartbeat';
        $phaseCompleteUrl   = rtrim(config('app.url'), '/') . '/api/router-phase-complete';
        $provisionUrl       = rtrim(config('app.url'), '/') . '/admin/provision/' . $router->ref_code;
        $tunnelCallbackUrl  = 'http://' . self::WG_SERVER_TUNNEL_IP . '/api/router-callback';
        $tunnelHeartbeatUrl = 'http://' . self::WG_SERVER_TUNNEL_IP . '/api/router-heartbeat';

        $routerName = preg_replace('/[^a-zA-Z0-9\-]/', '-', (string) $router->name);
        $routerName = preg_replace('/-{2,}/', '-', $routerName);
        $routerName = trim($routerName, '-') ?: 'MikroTik-Router';

        $billingDomain = $this->sanitizeForRos(
            $router->billing_domain
                ?: (string)(config('app.billing_domain', parse_url(config('app.url'), PHP_URL_HOST) ?: 'billing.local'))
        );

        $wanIface      = $this->sanitizeForRos($router->wan_interface      ?: (string)config('app.wan_interface', 'ether1'));
        $wanIface      = $wanIface ?: 'ether1'; // safety fallback — must never be empty
        $customerIface = $this->sanitizeForRos($router->customer_interface ?: 'bridge1');
        $customerIface = $customerIface ?: 'bridge1'; // safety fallback

        $pppoePoolRange   = $this->sanitizeForRos($router->pppoe_pool_range   ?: '10.10.1.1-10.10.1.254');
        $hotspotPoolRange = $this->sanitizeForRos($router->hotspot_pool_range ?: '10.20.1.1-10.20.1.254');

        $radiusSecret = $this->sanitizeForRos((string) $router->radius_secret);

        $wgOctet            = (($router->id % 253) + 2);
        $wgRouterIp         = "10.255.255.{$wgOctet}/32";
        $wgServerIp         = self::WG_SERVER_TUNNEL_IP . '/32';
        $wgServerPingIp     = self::WG_SERVER_TUNNEL_IP; // WG server IP without subnet mask (used for ping)
        $wgSubnet           = self::WG_MANAGEMENT_SUBNET;
        $wgAllowedAddresses = "{$wgServerIp},{$wgSubnet}";

        $scriptFilename = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $router->name)) . '-mikrotik.rsc';

        $mgmtIps    = (string)config('app.management_ips', '192.168.88.1,10.0.0.1');
        $mgmtIpList = array_values(array_filter(array_map('trim', explode(',', $mgmtIps))));

        $hotspotBaseUrl = rtrim(config('app.url'), '/') . '/api/hotspot-files/' . $router->id;

        return compact(
            'billingServerIp', 'billingPublicKey', 'wgPort', 'wgEndpoint',
            'callbackUrl', 'heartbeatUrl', 'phaseCompleteUrl', 'provisionUrl',
            'tunnelCallbackUrl', 'tunnelHeartbeatUrl',
            'routerName', 'billingDomain',
            'wanIface', 'customerIface',
            'pppoePoolRange', 'hotspotPoolRange', 'radiusSecret',
            'wgRouterIp', 'wgServerIp', 'wgServerPingIp', 'wgSubnet', 'wgAllowedAddresses',
            'scriptFilename', 'mgmtIpList', 'hotspotBaseUrl'
        );
    }

    // ── Header & Variables ─────────────────────────────────────────────────────

    protected function buildHeader(Router $router, array $ctx, ?int $phase = null): array
    {
        $phaseLabel = match ($phase) {
            1 => ' — Phase 1: Establish Connection',
            2 => ' — Phase 2: Configure Services',
            3 => ' — Phase 3: Security Hardening',
            default => '',
        };

        return [
            '# ============================================================',
            '# iNettotik ISP Billing - Licensed Configuration',
            "# MikroTik Configuration Script for: {$router->name}{$phaseLabel}",
            '# Generated by iNettotik ISP Billing System',
            '# Date: ' . now()->format('Y-m-d H:i:s'),
            '#',
            '# Bootstrap one-liner (run ONCE on a fresh router):',
            "#   /tool fetch url=\"{$ctx['provisionUrl']}\" dst-path=\"{$ctx['scriptFilename']}\"",
            "#   /import {$ctx['scriptFilename']}",
            '# ============================================================',
            '',
        ];
    }

    protected function buildVariables(array $ctx): array
    {
        return [
            '# --- Configuration Variables ---',
            ":local routerName \"{$ctx['routerName']}\"",
            ":local billingServerIP \"{$ctx['billingServerIp']}\"",
            ":local wgRouterIP \"{$ctx['wgRouterIp']}\"",
            ":local wgPort {$ctx['wgPort']}",
            ":local billingPublicKey \"{$ctx['billingPublicKey']}\"",
            ":local callbackURL \"{$ctx['callbackUrl']}\"",
            ":local heartbeatURL \"{$ctx['heartbeatUrl']}\"",
            ":local phaseCompleteURL \"{$ctx['phaseCompleteUrl']}\"",
            ":local provisionURL \"{$ctx['provisionUrl']}\"",
            ":local scriptFilename \"{$ctx['scriptFilename']}\"",
            ":local radiusSecret \"{$ctx['radiusSecret']}\"",
            ":local wanInterface \"{$ctx['wanIface']}\"",
            ":local customerInterface \"{$ctx['customerIface']}\"",
            ":local pppoePoolRange \"{$ctx['pppoePoolRange']}\"",
            ":local hotspotPoolRange \"{$ctx['hotspotPoolRange']}\"",
            ":local billingDomain \"{$ctx['billingDomain']}\"",
            ':local wgSubnet "' . self::WG_MANAGEMENT_SUBNET . '"',
            '',
        ];
    }

    // ── Phase 1 lines ──────────────────────────────────────────────────────────

    protected function generatePhase1Lines(Router $router, array $ctx): array
    {
        $L = [];

        $L[] = '# ============================================================';
        $L[] = '# PHASE 1 — Establish Connection';
        $L[] = '# ============================================================';
        $L[] = '';

        $L[] = '# --- System Identity ---';
        $L[] = "/system identity set name=\"{$ctx['routerName']}\"";
        $L[] = '';

        $L = array_merge($L, $this->buildEtherLockdown($ctx));
        $L = array_merge($L, $this->buildWireGuard($ctx));
        $L = array_merge($L, $this->buildPhaseCallback(1, $ctx));

        $L[] = ':put "Phase 1 complete — connection established."';
        $L[] = '';

        return $L;
    }

    // ── Phase 2 lines ──────────────────────────────────────────────────────────

    protected function generatePhase2Lines(Router $router, array $ctx): array
    {
        $L = [];

        $L[] = '# ============================================================';
        $L[] = '# PHASE 2 — Configure Services';
        $L[] = '# ============================================================';
        $L[] = '';

        $L[] = '# --- Customer Interface (Bridge) ---';
        $L[] = ":if ([:len [/interface bridge find name=\"{$ctx['customerIface']}\"]] = 0) do={";
        $L[] = "  /interface bridge add name=\"{$ctx['customerIface']}\" comment=\"Customer Bridge - iNettotik\" disabled=no";
        $L[] = '}';
        $L[] = '';

        $L = array_merge($L, $this->buildVlans($ctx));
        $L = array_merge($L, $this->buildEtherEnable($ctx));
        $L = array_merge($L, $this->buildRadius($ctx));
        $L = array_merge($L, $this->buildIpPools($ctx));

        $L[] = '# --- PPP Profiles ---';
        $L[] = '/ppp profile remove [find name="pppoe-radius"]';
        $L[] = '/ppp profile add name="pppoe-radius" local-address=pppoe-pool remote-address=pppoe-pool use-compression=no only-one=yes';
        $L[] = '';

        $L = array_merge($L, $this->buildPppoe($ctx));
        $L = array_merge($L, $this->buildHotspot($ctx));
        $L = array_merge($L, $this->buildHotspotFileDownload($ctx));

        $L[] = '# --- Hotspot Walled Garden ---';
        $L[] = '/ip hotspot walled-garden remove [find]';
        $L[] = "/ip hotspot walled-garden add server=\"hotspot-server\" dst-host=\"{$ctx['billingDomain']}\" action=allow";
        $L[] = "/ip hotspot walled-garden add server=\"hotspot-server\" dst-host=\"*.{$ctx['billingDomain']}\" action=allow";
        $L[] = "/ip hotspot walled-garden ip add server=\"hotspot-server\" dst-address={$ctx['billingServerIp']} action=accept";
        $L[] = '';

        $L[] = '# --- NAT Masquerade ---';
        $L[] = '/ip firewall nat remove [find comment="ISP-NAT"]';
        $L[] = "/ip firewall nat add chain=srcnat out-interface=\"{$ctx['wanIface']}\" action=masquerade comment=\"ISP-NAT\"";
        $L[] = '';

        $L = array_merge($L, $this->buildPcqQueues($ctx));
        $L = array_merge($L, $this->buildPhaseCallback(2, $ctx));

        $L[] = ':put "Phase 2 complete — services configured."';
        $L[] = '';

        return $L;
    }

    // ── Phase 3 lines ──────────────────────────────────────────────────────────

    protected function generatePhase3Lines(Router $router, array $ctx): array
    {
        $L = [];

        $L[] = '# ============================================================';
        $L[] = '# PHASE 3 — Security Hardening';
        $L[] = '# ============================================================';
        $L[] = '';

        $L = array_merge($L, $this->buildFirewall($ctx));
        $L = array_merge($L, $this->buildSecurityHardening($ctx));

        $L[] = '# --- NTP Client ---';
        $L[] = ':do { /system ntp client set enabled=yes } on-error={}';
        $L[] = ':do { /system ntp client servers add address=216.239.35.0 } on-error={}';
        $L[] = ':do { /system ntp client servers add address=216.239.35.4 } on-error={}';
        $L[] = '';

        $L[] = '# --- DNS ---';
        $L[] = '/ip dns set allow-remote-requests=yes servers=8.8.8.8,1.1.1.1';
        $L[] = '';

        $L = array_merge($L, $this->buildHeartbeat($ctx));

        $L[] = '# --- Scheduled Auto-Update (nightly at 03:00) ---';
        $L[] = '/system scheduler remove [find name="iNettotik-AutoUpdate"]';
        $L[] = '/system scheduler add \\';
        $L[] = '    name="iNettotik-AutoUpdate" \\';
        $L[] = '    interval=1d \\';
        $L[] = '    start-time=03:00:00 \\';
        $L[] = '    on-event=("/tool fetch url=\"' . $ctx['provisionUrl'] . '\" dst-path=\"' . $ctx['scriptFilename'] . '\"; /import ' . $ctx['scriptFilename'] . '") \\';
        $L[] = '    comment="iNettotik: auto-fetch and apply latest config from billing server"';
        $L[] = '';

        $L[] = '# --- Weekly Config Backup ---';
        $L[] = '/system scheduler remove [find name="iNettotik-WeeklyBackup"]';
        $L[] = '/system scheduler add name="iNettotik-WeeklyBackup" interval=7d start-time=04:00:00 on-event="/system backup save name=auto-backup dont-encrypt=no" comment="iNettotik: weekly config backup"';
        $L[] = '';

        $L = array_merge($L, $this->buildPhaseCallback(3, $ctx));

        $L[] = ':put "=============================================="';
        $L[] = ':put "  iNettotik provisioning complete!"';
        $L[] = ':put "  All 3 phases applied successfully."';
        $L[] = ':put "  Auto-update scheduled daily at 03:00."';
        $L[] = ':put "=============================================="';

        return $L;
    }

    // ── Component builders ─────────────────────────────────────────────────────

    protected function buildEtherLockdown(array $ctx): array
    {
        // Ether ports are intentionally NOT disabled here: disabling them would
        // immediately disconnect any admin connected via a LAN port (WinBox session
        // lost, no recovery possible without console access).  Traffic isolation is
        // achieved in Phase 2 by placing the ports under a bridge with VLAN
        // filtering, so there is no need to touch port state in Phase 1.
        return [];
    }

    protected function buildEtherEnable(array $ctx): array
    {
        return [
            '# --- Re-enable Customer Ether Ports (under bridge with VLAN tagging) ---',
            ":foreach i in=[/interface ethernet find where name!=\"{$ctx['wanIface']}\"] do={",
            '    /interface ethernet set $i disabled=no',
            '}',
            '# Add non-WAN interfaces to customer bridge',
            ":foreach i in=[/interface ethernet find where name!=\"{$ctx['wanIface']}\"] do={",
            "    :if ([:len [/interface bridge port find interface=[/interface ethernet get \$i name]]] = 0) do={",
            "        /interface bridge port add bridge=\"{$ctx['customerIface']}\" interface=[/interface ethernet get \$i name]",
            '    }',
            '}',
            '',
        ];
    }

    protected function buildWireGuard(array $ctx): array
    {
        return [
            '# --- WireGuard Management Tunnel (wg-billing) ---',
            '',
            '# Remove existing peers, addresses and interface by name/comment',
            '# (safe on fresh router — [find] returns empty, nothing is removed)',
            '/interface wireguard peers remove [find comment="iNettotik Billing Server"]',
            '/ip address remove [find comment="iNettotik VPN IP"]',
            '/interface wireguard remove [find name="wg-billing"]',
            '',
            '# Create fresh WireGuard interface',
            "/interface wireguard add name=\"wg-billing\" listen-port={$ctx['wgPort']} mtu=1420 comment=\"iNettotik Billing Tunnel\"",
            '',
            '# Assign VPN IP',
            "/ip address add address={$ctx['wgRouterIp']} interface=\"wg-billing\" comment=\"iNettotik VPN IP\"",
            '',
            '# Add route to WG management subnet via the tunnel',
            '/ip route remove [find comment="iNettotik WG Subnet"]',
            "/ip route add dst-address={$ctx['wgSubnet']} gateway=\"wg-billing\" comment=\"iNettotik WG Subnet\"",
            '',
            '# Add billing server as peer',
            '/interface wireguard peers add interface="wg-billing" \\',
            "    public-key=\"{$ctx['billingPublicKey']}\" \\",
            "    endpoint-address={$ctx['wgEndpoint']} \\",
            "    endpoint-port={$ctx['wgPort']} \\",
            "    allowed-address=\"{$ctx['wgAllowedAddresses']}\" \\",
            '    persistent-keepalive=25s \\',
            '    comment="iNettotik Billing Server"',
            '',
            '# Allow WireGuard UDP through firewall',
            '/ip firewall filter remove [find comment="ISP-WG-BILLING"]',
            "/ip firewall filter add chain=input protocol=udp dst-port={$ctx['wgPort']} action=accept comment=\"ISP-WG-BILLING\"",
            '',
        ];
    }

    protected function buildVlans(array $ctx): array
    {
        return [
            '# --- VLAN Isolation ---',
            '# VLAN 10 = PPPoE subscribers, VLAN 20 = Hotspot subscribers',
            '/interface vlan remove [find name="vlan10-pppoe"]',
            "/interface vlan add name=\"vlan10-pppoe\" vlan-id=10 interface=\"{$ctx['customerIface']}\" comment=\"PPPoE Subscribers\"",
            '/interface vlan remove [find name="vlan20-hotspot"]',
            "/interface vlan add name=\"vlan20-hotspot\" vlan-id=20 interface=\"{$ctx['customerIface']}\" comment=\"Hotspot Subscribers\"",
            '',
            '# --- Bridge VLAN Filtering (vlan-mode=secure prevents inter-VLAN leakage) ---',
            '# frame-types=admit-all keeps untagged management frames flowing so an admin',
            '# connected from a normal laptop is not locked out after the bridge is created.',
            '# pvid=1 assigns untagged frames to VLAN 1 (the implicit management VLAN),',
            '# while tagged subscriber frames (VLAN 10/20) continue to work normally.',
            "/interface bridge set [find name=\"{$ctx['customerIface']}\"] vlan-filtering=yes",
            "/interface bridge port set [find bridge=\"{$ctx['customerIface']}\"] frame-types=admit-all pvid=1",
            '',
        ];
    }

    protected function buildRadius(array $ctx): array
    {
        return [
            '# --- RADIUS Client ---',
            '/radius remove [find]',
            "/radius add address={$ctx['wgServerPingIp']} \\",
            "    secret=\"{$ctx['radiusSecret']}\" \\",
            '    service=ppp,hotspot,login',
            '/radius incoming set accept=yes port=3799',
            '',
        ];
    }

    protected function buildIpPools(array $ctx): array
    {
        return [
            '# --- IP Address Pools ---',
            '/ip pool remove [find name="pppoe-pool"]',
            "/ip pool add name=\"pppoe-pool\" ranges={$ctx['pppoePoolRange']}",
            '/ip pool remove [find name="hotspot-pool"]',
            "/ip pool add name=\"hotspot-pool\" ranges={$ctx['hotspotPoolRange']}",
            '',
        ];
    }

    protected function buildPppoe(array $ctx): array
    {
        return [
            '# --- PPPoE Server (on VLAN 10) ---',
            '/interface pppoe-server server remove [find interface="vlan10-pppoe"]',
            '/interface pppoe-server server add interface="vlan10-pppoe" service-name="pppoe-service" default-profile="pppoe-radius" authentication=mschap2,mschap1,chap,pap one-session-per-host=yes disabled=no',
            '',
        ];
    }

    protected function buildHotspot(array $ctx): array
    {
        return [
            '# --- Hotspot Server (on VLAN 20) ---',
            '/ip hotspot remove [find interface="vlan20-hotspot"]',
            '/ip hotspot remove [find name="hotspot-server"]',
            '/ip hotspot profile remove [find name="hsprof-radius"]',
            "/ip hotspot profile add name=\"hsprof-radius\" hotspot-address=192.168.2.1 dns-name=\"{$ctx['billingDomain']}\" use-radius=yes radius-location-id=\"{$ctx['routerName']}\" login-by=http-pap,http-chap http-cookie-lifetime=1d",
            '/ip hotspot add interface="vlan20-hotspot" address-pool=hotspot-pool profile="hsprof-radius" name="hotspot-server" disabled=no',
            '',
        ];
    }

    protected function buildHotspotFileDownload(array $ctx): array
    {
        return [
            '# --- Auto-Download Hotspot Files ---',
            ":local hotspotBaseUrl \"{$ctx['hotspotBaseUrl']}\"",
            ':do {',
            '    /tool fetch url=($hotspotBaseUrl . "/login.html") dst-path="hotspot/login.html"',
            '    /tool fetch url=($hotspotBaseUrl . "/alogin.html") dst-path="hotspot/alogin.html"',
            '    /tool fetch url=($hotspotBaseUrl . "/status.html") dst-path="hotspot/status.html"',
            '    :put "Hotspot HTML files downloaded successfully."',
            '} on-error={',
            '    :put "WARNING: Could not download hotspot files. Upload them manually."',
            '}',
            '',
        ];
    }

    protected function buildPcqQueues(array $ctx): array
    {
        return [
            '# --- PCQ Queue Types ---',
            '/queue type remove [find name="pcq-download"]',
            '/queue type add name="pcq-download" kind=pcq pcq-rate=0 pcq-classifier=dst-address pcq-dst-address-mask=32 pcq-src-address-mask=32',
            '/queue type remove [find name="pcq-upload"]',
            '/queue type add name="pcq-upload" kind=pcq pcq-rate=0 pcq-classifier=src-address pcq-dst-address-mask=32 pcq-src-address-mask=32',
            '/queue tree remove [find name="Download"]',
            "/queue tree add name=\"Download\" parent=\"{$ctx['wanIface']}\" queue=pcq-download",
            '/queue tree remove [find name="Upload"]',
            '/queue tree add name="Upload" parent=global queue=pcq-upload',
            '',
        ];
    }

    protected function buildFirewall(array $ctx): array
    {
        $L = [];

        $L[] = '# --- Firewall Rules ---';
        $L[] = '';
        $L[] = '# Allow established/related connections (required for /tool fetch responses)';
        $L[] = '/ip firewall filter remove [find comment="ISP-ALLOW-ESTABLISHED"]';
        $L[] = '/ip firewall filter add chain=input \\';
        $L[] = '    connection-state=established,related action=accept \\';
        $L[] = '    comment="ISP-ALLOW-ESTABLISHED" place-before=0';
        $L[] = '';
        $L[] = '# Drop invalid connections';
        $L[] = '/ip firewall filter remove [find comment="ISP-DROP-INVALID"]';
        $L[] = '/ip firewall filter add chain=forward connection-state=invalid action=drop comment="ISP-DROP-INVALID"';
        $L[] = '';
        $L[] = '# Client-to-client isolation';
        $L[] = '/ip firewall filter remove [find comment="ISP-NO-CLIENT2CLIENT"]';
        $L[] = "/ip firewall filter add chain=forward in-interface=\"{$ctx['customerIface']}\" out-interface=\"{$ctx['customerIface']}\" action=drop comment=\"ISP-NO-CLIENT2CLIENT\"";
        $L[] = '';
        $L[] = '# Block DNS from WAN (prevent open resolver)';
        $L[] = '/ip firewall filter remove [find comment="ISP-NO-DNS-WAN"]';
        $L[] = "/ip firewall filter add chain=input protocol=udp dst-port=53 in-interface=\"{$ctx['wanIface']}\" action=drop comment=\"ISP-NO-DNS-WAN\"";
        $L[] = "/ip firewall filter add chain=input protocol=tcp dst-port=53 in-interface=\"{$ctx['wanIface']}\" action=drop comment=\"ISP-NO-DNS-WAN\"";
        $L[] = '';
        $L[] = '# Block WinBox discovery from WAN';
        $L[] = '/ip firewall filter remove [find comment="ISP-NO-WINBOX-WAN-DISC"]';
        $L[] = "/ip firewall filter add chain=input protocol=udp dst-port=5678 in-interface=\"{$ctx['wanIface']}\" action=drop comment=\"ISP-NO-WINBOX-WAN-DISC\"";
        $L[] = '';
        $L[] = '# SSH brute-force protection';
        $L[] = '/ip firewall filter remove [find comment="ISP-BRUTE-SSH"]';
        $L[] = '/ip firewall filter add chain=input protocol=tcp dst-port=22 connection-state=new src-address-list=ssh-bruteforce action=drop comment="ISP-BRUTE-SSH"';
        $L[] = '/ip firewall filter remove [find comment="ISP-DETECT-SSH"]';
        $L[] = '/ip firewall filter add chain=input protocol=tcp dst-port=22 connection-state=new action=add-src-to-address-list address-list=ssh-bruteforce address-list-timeout=1h comment="ISP-DETECT-SSH"';
        $L[] = '';

        if (!empty($ctx['mgmtIpList'])) {
            $L[] = '# Management IP Whitelist';
            $L[] = '/ip firewall address-list remove [find list="mgmt-allowed"]';
            foreach ($ctx['mgmtIpList'] as $ip) {
                $safeIp = $this->sanitizeForRos($ip);
                $L[] = "/ip firewall address-list add list=\"mgmt-allowed\" address={$safeIp} comment=\"Management\"";
            }
            $safeServerIp = $this->sanitizeForRos($ctx['billingServerIp']);
            $L[] = "/ip firewall address-list add list=\"mgmt-allowed\" address={$safeServerIp} comment=\"Billing Server\"";
            // Allow management access via the WireGuard subnet (always reachable
            // once the tunnel is up, regardless of which IP the admin uses).
            $L[] = "/ip firewall address-list add list=\"mgmt-allowed\" address=" . self::WG_MANAGEMENT_SUBNET . " comment=\"WireGuard Management Subnet\"";
            $L[] = '/ip firewall filter remove [find comment="ISP-MGMT-ALLOW"]';
            $L[] = '/ip firewall filter add chain=input src-address-list=mgmt-allowed action=accept comment="ISP-MGMT-ALLOW"';
            $L[] = '/ip firewall filter remove [find comment="ISP-MGMT-DROP"]';
            $L[] = "/ip firewall filter add chain=input action=drop src-address-list=!mgmt-allowed in-interface=\"{$ctx['wanIface']}\" protocol=tcp dst-port=22,80,8291,8728 comment=\"ISP-MGMT-DROP\"";
            $L[] = '';
        }

        // Allow all management traffic arriving on the customer bridge and the
        // WireGuard tunnel interface BEFORE the blanket WAN drop-all rule.
        // Without this, WinBox / SSH / API from a locally-connected laptop or
        // from the billing server via the tunnel would be silently dropped.
        $L[] = '# Accept management traffic from local bridge and WireGuard tunnel';
        $L[] = '/ip firewall filter remove [find comment="ISP-ACCEPT-BRIDGE-INPUT"]';
        $L[] = "/ip firewall filter add chain=input in-interface=\"{$ctx['customerIface']}\" action=accept comment=\"ISP-ACCEPT-BRIDGE-INPUT\"";
        $L[] = '/ip firewall filter remove [find comment="ISP-ACCEPT-WG-INPUT"]';
        $L[] = '/ip firewall filter add chain=input in-interface="wg-billing" action=accept comment="ISP-ACCEPT-WG-INPUT"';
        $L[] = '';

        $L[] = '# Default DROP on input chain for WAN interface';
        $L[] = '/ip firewall filter remove [find comment="ISP-DROP-ALL-INPUT"]';
        $L[] = "/ip firewall filter add chain=input action=drop in-interface=\"{$ctx['wanIface']}\" comment=\"ISP-DROP-ALL-INPUT\"";
        $L[] = '';

        return $L;
    }

    protected function buildSecurityHardening(array $ctx): array
    {
        return [
            '# --- Services (disable unused, restrict active) ---',
            '/ip service disable telnet,ftp,www-ssl,api-ssl',
            '/ip service enable ssh,winbox,api,www',
            '/ip service set ssh port=22',
            '/ip service set winbox port=8291',
            '/ip service set api port=8728',
            '/ip service set www port=80',
            '',
        ];
    }

    protected function buildHeartbeat(array $ctx): array
    {
        $secretHeader = '';
        $callbackSecret = config('app.router_callback_secret', '');
        if (!empty($callbackSecret)) {
            $secretHeader = ",X-Router-Secret: {$callbackSecret}";
        }

        return [
            '# --- Persistent Heartbeat (every 5 minutes) ---',
            '/system scheduler remove [find name="iNettotik-Heartbeat"]',
            '/system scheduler add name="iNettotik-Heartbeat" interval=5m \\',
            '    on-event={',
            '        :do {',
            "            /tool fetch url=\"{$ctx['tunnelHeartbeatUrl']}\" \\",
            '                http-method=post \\',
            "                http-header-field=\"Content-Type: application/json{$secretHeader}\" \\",
            '                http-data=("{\"router_name\":\"" . [/system identity get name] . "\"}") \\',
            '                output=none',
            '        } on-error={}',
            '    } \\',
            '    comment="iNettotik: billing server keepalive"',
            '',
            '# --- WireGuard Auto-Recovery (every 10 minutes) ---',
            '/system scheduler remove [find name="iNettotik-WG-Recovery"]',
            '/system scheduler add name="iNettotik-WG-Recovery" interval=10m \\',
            '    on-event={',
            "        :if ([/ping {$ctx['wgServerPingIp']} count=3] = 0) do={",
            '            /interface wireguard disable [find name="wg-billing"]',
            '            :delay 5s',
            '            /interface wireguard enable [find name="wg-billing"]',
            '        }',
            '    } \\',
            '    comment="iNettotik: WireGuard auto-recovery"',
            '',
        ];
    }

    protected function buildPhaseCallback(int $phase, array $ctx): array
    {
        $L = [];
        $secretHeader = '';
        $callbackSecret = config('app.router_callback_secret', '');
        if (!empty($callbackSecret)) {
            $secretHeader = ",X-Router-Secret: {$callbackSecret}";
        }

        $wanIface         = $ctx['wanIface'];
        $routerName       = $ctx['routerName'];
        $tunnelCallbackUrl = $ctx['tunnelCallbackUrl'];
        $publicCallbackUrl = $ctx['callbackUrl'];

        // Always try the WireGuard tunnel URL first (faster, avoids firewall).
        // For Phase 1, also provide a public-URL fallback in case WG isn't fully
        // established yet when the callback runs.
        $callbackUrl = $tunnelCallbackUrl;
        $fallbackUrl = ($phase === 1) ? $publicCallbackUrl : null;

        $L[] = "# --- Phase {$phase} Callback ---";
        $L[] = ':do {';
        $L[] = '    :local wanIP ""';
        $L[] = "    :do { :set wanIP [/ip address get [find interface=\"{$wanIface}\"] address] } on-error={}";
        $L[] = '    :do { :set wanIP [:pick $wanIP 0 [:find $wanIP "/"]] } on-error={}';
        $L[] = "    :if (\$wanIP = \"\") do={";
        $L[] = "        :do { :set wanIP [/ip dhcp-client get [find interface=\"{$wanIface}\"] address] } on-error={}";
        $L[] = '        :do { :set wanIP [:pick $wanIP 0 [:find $wanIP "/"]] } on-error={}';
        $L[] = '    }';
        $L[] = '    :local vpnIP ""';
        $L[] = '    :do { :set vpnIP [/ip address get [find interface="wg-billing"] address] } on-error={}';
        $L[] = '    :do { :set vpnIP [:pick $vpnIP 0 [:find $vpnIP "/"]] } on-error={}';
        $L[] = '    :local routerPubKey ""';
        $L[] = '    :do { :set routerPubKey [/interface wireguard get [find name="wg-billing"] public-key] } on-error={}';
        $L[] = "    :local cbData \"{\\\"router_name\\\":\\\"{$routerName}\\\",\\\"wan_ip\\\":\\\"\"";
        $L[] = '    :set cbData ($cbData . $wanIP . "\",\"vpn_ip\":\"" . $vpnIP)';
        $L[] = "    :set cbData (\$cbData . \"\\\",\\\"wg_public_key\\\":\\\"\" . \$routerPubKey . \"\\\",\\\"phase\\\":{$phase}}\")";
        $L[] = "    /tool fetch url=\"{$callbackUrl}\" \\";
        $L[] = '        http-method=post \\';
        $L[] = "        http-header-field=\"Content-Type: application/json{$secretHeader}\" \\";
        $L[] = '        http-data=$cbData \\';
        $L[] = '        output=none';
        $L[] = '    :put ("  WAN IP:     " . $wanIP)';
        $L[] = '    :put ("  VPN IP:     " . $vpnIP)';
        $L[] = '    :put ("  WG PubKey:  " . $routerPubKey)';

        if ($fallbackUrl !== null) {
            $L[] = '} on-error={';
            $L[] = "    :put \"Tunnel callback failed, retrying via public URL...\"";
            $L[] = '    :do {';
            $L[] = "        /tool fetch url=\"{$fallbackUrl}\" \\";
            $L[] = '            http-method=post \\';
            $L[] = "            http-header-field=\"Content-Type: application/json{$secretHeader}\" \\";
            $L[] = '            http-data=$cbData \\';
            $L[] = '            output=none';
            $L[] = '    } on-error={';
            $L[] = "        :put \"WARNING: Could not reach billing server at {$fallbackUrl}\"";
            $L[] = '    }';
        } else {
            $L[] = '} on-error={';
            $L[] = "    :put \"WARNING: Could not reach billing server at {$callbackUrl}\"";
        }

        $L[] = '}';
        $L[] = '';
        return $L;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    protected function resolveRadiusIp(): string
    {
        $ip = (string)config('radius.server_ip', '');
        if ($ip !== '') {
            $resolved = trim($ip);
            // If RADIUS_SERVER_IP is explicitly set to localhost it is useless on a
            // remote MikroTik router.  Prefer the WireGuard server IP so traffic
            // is routed through the management tunnel.
            return ($resolved === '127.0.0.1') ? self::WG_SERVER_TUNNEL_IP : $resolved;
        }
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: '';
        // When the billing server is only reachable via the WireGuard tunnel use
        // the tunnel IP so the remote router can actually reach it.
        if (!$host || $host === '127.0.0.1' || !filter_var($host, FILTER_VALIDATE_IP)) {
            return self::WG_SERVER_TUNNEL_IP;
        }
        return $host;
    }

    protected function resolveWgPublicKey(): string
    {
        return trim((string)config('wireguard.server_public_key', self::WG_KEY_PLACEHOLDER))
            ?: self::WG_KEY_PLACEHOLDER;
    }

    protected function resolveWgPort(): int
    {
        return (int) config('wireguard.port', 51820);
    }

    protected function resolveWgEndpoint(): string
    {
        $endpoint = trim((string)config('wireguard.server_endpoint', ''));
        if ($endpoint !== '') return $endpoint;
        // Fall back to parsing the app URL host (works when APP_URL contains a
        // publicly-reachable hostname or IP).  We deliberately do NOT fall back
        // to 127.0.0.1 — that address is useless to a remote MikroTik router
        // and would silently prevent the WireGuard tunnel from ever connecting.
        // Set WG_SERVER_ENDPOINT in .env to the public IP / hostname of the
        // billing server so the router can reach it from the internet.
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: '';
        return $host ?: 'WG_SERVER_ENDPOINT_NOT_CONFIGURED';
    }
}
