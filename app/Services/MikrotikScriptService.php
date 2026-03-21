<?php

namespace App\Services;

use App\Models\Router;

class MikrotikScriptService
{
    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Generate the full provisioning script for the given router.
     *
     * Section 1 (Foundation) is always included.
     * Subsequent sections depend on the router's service_mode:
     *   pppoe         → Section 1 + Section 2
     *   hotspot       → Section 1 + Section 3
     *   pppoe_hotspot → Section 1 + Section 2 + Section 3
     *   combined      → Section 1 + Section 4
     */
    public function generate(Router $router): string
    {
        $sections = [];

        $sections[] = $this->generateSection1($router);

        $mode = $router->service_mode ?? 'pppoe_hotspot';

        switch ($mode) {
            case 'pppoe':
                $sections[] = $this->generateSection2($router);
                break;

            case 'hotspot':
                $sections[] = $this->generateSection3($router);
                break;

            case 'combined':
                $sections[] = $this->generateSection4($router);
                break;

            case 'pppoe_hotspot':
            default:
                $sections[] = $this->generateSection2($router);
                $sections[] = $this->generateSection3($router);
                break;
        }

        return implode("\n\n", $sections);
    }

    // -------------------------------------------------------------------------
    // Section 1 — Foundation (always included)
    // -------------------------------------------------------------------------

    private function generateSection1(Router $router): string
    {
        $radiusSecret        = $this->s($router->radius_secret ?? '');
        $radiusServerIp      = $this->s($this->billingVpnIp($router));
        $billingPublicIp     = $this->s($this->billingPublicIp($router));
        $billingSubnet       = $this->s(config('openvpn.billing_server_subnet', ''));
        $openvpnPort         = (int) ($router->openvpn_port ?? config('openvpn.port', 443));

        $caFilename          = $this->s($router->ca_cert_filename    ?? 'ca.crt');
        $routerCertFilename  = $this->s($router->router_cert_filename ?? 'router.crt');
        $routerKeyFilename   = $this->s($this->deriveKeyFilename($router->router_cert_filename ?? 'router.crt'));

        // Certificate names used after import (filename without extension)
        $caCertName         = pathinfo($router->ca_cert_filename    ?? 'ca.crt',    PATHINFO_FILENAME);
        $routerCertName     = pathinfo($router->router_cert_filename ?? 'router.crt', PATHINFO_FILENAME);

        $billingDomain      = $this->s($router->billing_domain ?? config('app.url', ''));
        $refCode            = $this->s($router->ref_code ?? '');
        $mgmtUserName       = $this->s($router->ref_code ?? $router->name ?? 'mgmt-router');
        $timezone           = $this->s($router->timezone ?? 'Africa/Nairobi');
        $routerName         = $this->s($router->name ?? 'MikroTik');

        $lines = [];

        // --- Router identity ---
        $lines[] = "# ============================================================";
        $lines[] = "# Section 1 — Foundation";
        $lines[] = "# Generated for: {$routerName}";
        $lines[] = "# ============================================================";
        $lines[] = "";

        $lines[] = "/system identity set name=\"{$routerName}\"";
        $lines[] = "";

        // 1. DNS — set FIRST
        $lines[] = "# 1. DNS";
        $lines[] = "/ip dns set servers=8.8.8.8,8.8.4.4 allow-remote-requests=no";
        $lines[] = "";

        // 2. Certificate download and import
        $lines[] = "# 2. Download OpenVPN Certificates from billing server";
        $lines[] = "/tool fetch url=\"https://{$billingDomain}/api/router-certs/{$refCode}/ca.crt\" dst-path={$caFilename}";
        $lines[] = ":delay 2s";
        $lines[] = "/tool fetch url=\"https://{$billingDomain}/api/router-certs/{$refCode}/router.crt\" dst-path={$routerCertFilename}";
        $lines[] = ":delay 2s";
        $lines[] = "/tool fetch url=\"https://{$billingDomain}/api/router-certs/{$refCode}/router.key\" dst-path={$routerKeyFilename}";
        $lines[] = ":delay 2s";
        $lines[] = "";

        $lines[] = "# Import certificates";
        $lines[] = "/certificate import file-name={$caFilename} passphrase=\"\"";
        $lines[] = "/certificate import file-name={$routerCertFilename} passphrase=\"\"";
        $lines[] = "/certificate import file-name={$routerKeyFilename} passphrase=\"\"";
        $lines[] = "";

        // 3. RADIUS Client
        $lines[] = "# 3. RADIUS Client";
        $lines[] = "/radius add address={$radiusServerIp} secret=\"{$radiusSecret}\" service=hotspot,ppp timeout=3s";
        $lines[] = "/radius incoming set accept=yes port=3799";
        $lines[] = "";

        // 4. Remote Management User
        $lines[] = "# 4. Remote Management User";
        $lines[] = "/user add name=\"{$mgmtUserName}\" password=\"{$radiusSecret}\" group=full";
        $lines[] = "";

        // 5. Timezone
        $lines[] = "# 5. Timezone";
        $lines[] = "/system clock set time-zone-name={$timezone} time-zone-autodetect=no";
        $lines[] = "";

        // 6. NTP Client
        $lines[] = "# 6. NTP Client";
        $ntpServer = '216.239.35.8';
        $rosVersion = $router->routeros_version ?? '';
        $rosMajor   = $rosVersion !== '' ? (int) explode('.', $rosVersion)[0] : 0;
        if ($rosMajor === 6) {
            // RouterOS 6.x uses the legacy primary-ntp parameter
            $lines[] = "/system ntp client set enabled=yes primary-ntp={$ntpServer}";
        } else {
            // RouterOS 7.x (default): primary-ntp was removed
            $lines[] = "/system ntp client set enabled=yes";
            $lines[] = "/system ntp client servers add address={$ntpServer}";
        }
        $lines[] = "";

        // 7. Firewall Baseline
        $lines[] = "# 7. Firewall Baseline";
        if ($billingSubnet !== '') {
            $lines[] = "/ip firewall filter add chain=input src-address={$billingSubnet} action=accept comment=\"accept billing mgmt\" place-before=*0";
        }
        $lines[] = "/ip firewall filter disable [find where comment=\"defconf: fasttrack\"]";
        $lines[] = "/ip firewall filter add chain=forward src-address-list=expired action=drop place-before=*0";
        $lines[] = "";

        // 8. NAT Masquerade
        $lines[] = "# 8. NAT Masquerade";
        $lines[] = "/ip firewall nat add chain=srcnat action=masquerade place-before=*0";
        $lines[] = "";

        // 9. OpenVPN Tunnel
        $lines[] = "# 9. OpenVPN Tunnel";
        $lines[] = "/ppp profile add name=ovpn-mgmt change-tcp-mss=yes use-encryption=yes";
        $lines[] = "/interface ovpn-client add name=ovpn-mgmt connect-to={$billingPublicIp} port={$openvpnPort} user=\"{$mgmtUserName}\" password=\"{$radiusSecret}\" certificate={$routerCertName} ca-certificate={$caCertName} auth=sha1 cipher=aes256 use-peer-dns=no profile=ovpn-mgmt disabled=no";

        return implode("\n", $lines);
    }

    // -------------------------------------------------------------------------
    // Section 2 — PPPoE Service
    // -------------------------------------------------------------------------

    private function generateSection2(Router $router): string
    {
        $bridgeName    = $this->s($router->pppoe_bridge_name    ?? 'pppoe_bridge');
        $poolRange     = $this->s($router->pppoe_pool_range     ?? '19.225.0.1-19.225.255.254');
        $gatewayIp     = $this->s($router->pppoe_gateway_ip     ?? '19.225.0.1');
        $radiusSecret  = $this->s($router->radius_secret        ?? '');

        $lines = [];

        $lines[] = "# ============================================================";
        $lines[] = "# Section 2 — PPPoE Service";
        $lines[] = "# ============================================================";
        $lines[] = "";

        // Bridge
        $lines[] = "# PPPoE Bridge";
        $lines[] = "/interface bridge add name={$bridgeName}";
        $lines[] = "";

        // IP Pool
        $lines[] = "# PPPoE IP Pool";
        $lines[] = "/ip pool add name=pppoe_pool ranges={$poolRange}";
        $lines[] = "";

        // PPP Profile
        $lines[] = "# PPP Profile";
        $lines[] = "/ppp profile add name=pppoe-profile dns-server=8.8.8.8,8.8.4.4 local-address={$gatewayIp} remote-address=pppoe_pool use-encryption=yes";
        $lines[] = "";

        // PPPoE Server
        $lines[] = "# PPPoE Server";
        $lines[] = "# PAP-only is deliberate: credentials are already protected by the OpenVPN tunnel.";
        $lines[] = "/interface pppoe-server server add service-name=pppoe interface={$bridgeName} authentication=pap one-session-per-host=yes keepalive-timeout=10 default-profile=pppoe-profile disabled=no";
        $lines[] = "";

        // PPP AAA
        $lines[] = "# PPP AAA";
        $lines[] = "/ppp aaa set use-radius=yes interim-update=00:05:50 accounting=yes";

        return implode("\n", $lines);
    }

    // -------------------------------------------------------------------------
    // Section 3 — Hotspot Service
    // -------------------------------------------------------------------------

    private function generateSection3(Router $router): string
    {
        $bridgeName    = $this->s($router->hotspot_bridge_name  ?? 'hotspot_bridge');
        $poolRange     = $this->s($router->hotspot_pool_range   ?? '11.220.0.1-11.220.255.254');
        $gatewayIp     = $this->s($router->hotspot_gateway_ip   ?? '11.220.0.1');
        $prefix        = (int) ($router->hotspot_prefix         ?? 16);
        $networkAddr   = $this->networkAddress($router->hotspot_gateway_ip ?? '11.220.0.1', $prefix);
        $billingDomain = $this->s($router->billing_domain       ?? config('app.url', ''));
        $refCode       = $this->s($router->ref_code             ?? '');
        $billingVpnIp  = $this->s($this->billingVpnIp($router));

        $lines = [];

        $lines[] = "# ============================================================";
        $lines[] = "# Section 3 — Hotspot Service";
        $lines[] = "# ============================================================";
        $lines[] = "";

        // Bridge
        $lines[] = "# Hotspot Bridge";
        $lines[] = "/interface bridge add name={$bridgeName}";
        $lines[] = "";

        // Assign gateway IP to bridge
        $lines[] = "# Gateway IP";
        $lines[] = "/ip address add address={$gatewayIp}/{$prefix} interface={$bridgeName}";
        $lines[] = "";

        // IP Pool
        $lines[] = "# Hotspot IP Pool";
        $lines[] = "/ip pool add name=hs_pool ranges={$poolRange}";
        $lines[] = "";

        // DHCP Server
        $lines[] = "# DHCP Server";
        $lines[] = "/ip dhcp-server add name=hs-dhcp interface={$bridgeName} address-pool=hs_pool lease-time=1d-00:10:00 disabled=no";
        $lines[] = "/ip dhcp-server network add address={$networkAddr}/{$prefix} gateway={$gatewayIp} dns-server=8.8.8.8,8.8.4.4";
        $lines[] = "";

        // Hotspot Profile
        $lines[] = "# Hotspot Profile";
        $lines[] = "/ip hotspot profile add name=hs-profile login-by=cookie,https,http-pap,mac-cookie use-radius=yes radius-interim-update=00:06:30";
        $lines[] = "";

        // Hotspot Server
        $lines[] = "# Hotspot Server";
        $lines[] = "/ip hotspot add name=hs-server interface={$bridgeName} profile=hs-profile addresses-per-mac=3 idle-timeout=1m disabled=no";
        $lines[] = "";

        // Fetch hotspot files from billing API
        $lines[] = "# Hotspot Files";
        $lines[] = "/tool fetch url=\"https://{$billingDomain}/api/router-hotspot/{$refCode}/login.html\" dst-path=hotspot/login.html";
        $lines[] = ":delay 2s";
        $lines[] = "/tool fetch url=\"https://{$billingDomain}/api/router-hotspot/{$refCode}/alogin.html\" dst-path=hotspot/alogin.html";
        $lines[] = ":delay 2s";
        $lines[] = "/tool fetch url=\"https://{$billingDomain}/api/router-hotspot/{$refCode}/status.html\" dst-path=hotspot/status.html";
        $lines[] = "";

        // Walled garden — billing server
        $lines[] = "# Walled Garden";
        $lines[] = "/ip hotspot walled-garden ip add dst-address={$billingVpnIp} action=accept";

        return implode("\n", $lines);
    }

    // -------------------------------------------------------------------------
    // Section 4 — Combined Mode (PPPoE + Hotspot on single bridge)
    // -------------------------------------------------------------------------

    private function generateSection4(Router $router): string
    {
        $bridgeName      = 'PPP_HOTSPOT';
        $pppoePoolRange  = $this->s($router->pppoe_pool_range    ?? '19.225.0.1-19.225.255.254');
        $pppoeGateway    = $this->s($router->pppoe_gateway_ip    ?? '19.225.0.1');
        $hsPoolRange     = $this->s($router->hotspot_pool_range  ?? '11.220.0.1-11.220.255.254');
        $hsGateway       = $this->s($router->hotspot_gateway_ip  ?? '11.220.0.1');
        $hsPrefix        = (int) ($router->hotspot_prefix        ?? 16);
        $networkAddr     = $this->networkAddress($router->hotspot_gateway_ip ?? '11.220.0.1', $hsPrefix);
        $billingDomain   = $this->s($router->billing_domain      ?? config('app.url', ''));
        $refCode         = $this->s($router->ref_code            ?? '');
        $billingVpnIp    = $this->s($this->billingVpnIp($router));

        $lines = [];

        $lines[] = "# ============================================================";
        $lines[] = "# Section 4 — Combined Mode (PPPoE + Hotspot)";
        $lines[] = "# ============================================================";
        $lines[] = "";

        // Single bridge
        $lines[] = "# Combined Bridge";
        $lines[] = "/interface bridge add name={$bridgeName}";
        $lines[] = "";

        // IP addresses
        $lines[] = "# Gateway IPs";
        $lines[] = "# /16 prefix for PPPoE matches the large pool range used in combined mode.";
        $lines[] = "/ip address add address={$pppoeGateway}/16 interface={$bridgeName}";
        $lines[] = "/ip address add address={$hsGateway}/{$hsPrefix} interface={$bridgeName}";
        $lines[] = "";

        // PPPoE Pool
        $lines[] = "# PPPoE IP Pool";
        $lines[] = "/ip pool add name=pppoe_pool ranges={$pppoePoolRange}";
        $lines[] = "";

        // PPP Profile
        $lines[] = "# PPP Profile";
        $lines[] = "/ppp profile add name=pppoe-profile dns-server=8.8.8.8,8.8.4.4 local-address={$pppoeGateway} remote-address=pppoe_pool use-encryption=yes";
        $lines[] = "";

        // PPPoE Server
        $lines[] = "# PPPoE Server";
        $lines[] = "# PAP-only is deliberate: credentials are already protected by the OpenVPN tunnel.";
        $lines[] = "/interface pppoe-server server add service-name=pppoe interface={$bridgeName} authentication=pap one-session-per-host=yes keepalive-timeout=10 default-profile=pppoe-profile disabled=no";
        $lines[] = "";

        // PPP AAA
        $lines[] = "# PPP AAA";
        $lines[] = "/ppp aaa set use-radius=yes interim-update=00:05:50 accounting=yes";
        $lines[] = "";

        // Hotspot Pool
        $lines[] = "# Hotspot IP Pool";
        $lines[] = "/ip pool add name=hs_pool ranges={$hsPoolRange}";
        $lines[] = "";

        // DHCP Server
        $lines[] = "# DHCP Server";
        $lines[] = "/ip dhcp-server add name=hs-dhcp interface={$bridgeName} address-pool=hs_pool lease-time=1d-00:10:00 disabled=no";
        $lines[] = "/ip dhcp-server network add address={$networkAddr}/{$hsPrefix} gateway={$hsGateway} dns-server=8.8.8.8,8.8.4.4";
        $lines[] = "";

        // Hotspot Profile
        $lines[] = "# Hotspot Profile";
        $lines[] = "/ip hotspot profile add name=hs-profile login-by=cookie,https,http-pap,mac-cookie use-radius=yes radius-interim-update=00:06:30";
        $lines[] = "";

        // Hotspot Server
        $lines[] = "# Hotspot Server";
        $lines[] = "/ip hotspot add name=hs-server interface={$bridgeName} profile=hs-profile addresses-per-mac=3 idle-timeout=1m disabled=no";
        $lines[] = "";

        // Fetch hotspot files
        $lines[] = "# Hotspot Files";
        $lines[] = "/tool fetch url=\"https://{$billingDomain}/api/router-hotspot/{$refCode}/login.html\" dst-path=hotspot/login.html";
        $lines[] = ":delay 2s";
        $lines[] = "/tool fetch url=\"https://{$billingDomain}/api/router-hotspot/{$refCode}/alogin.html\" dst-path=hotspot/alogin.html";
        $lines[] = ":delay 2s";
        $lines[] = "/tool fetch url=\"https://{$billingDomain}/api/router-hotspot/{$refCode}/status.html\" dst-path=hotspot/status.html";
        $lines[] = "";

        // Walled garden
        $lines[] = "# Walled Garden";
        $lines[] = "/ip hotspot walled-garden ip add dst-address={$billingVpnIp} action=accept";

        return implode("\n", $lines);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Sanitize a value for safe embedding inside a RouterOS double-quoted string.
     * Strips newlines and escapes backslashes and double-quotes.
     */
    private function s(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], '', $value);
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace('"', '\\"', $value);
        return $value;
    }

    /**
     * Resolve billing server VPN IP: router field first, then global config.
     */
    private function billingVpnIp(Router $router): string
    {
        return $router->billing_server_vpn_ip
            ?: config('openvpn.billing_server_vpn_ip', '');
    }

    /**
     * Resolve billing server public IP: router field first, then global config.
     */
    private function billingPublicIp(Router $router): string
    {
        return $router->billing_server_public_ip
            ?: config('openvpn.billing_server_public_ip', '');
    }

    /**
     * Derive the key filename from a certificate filename.
     * e.g. "router.crt" → "router.key", "client.pem" → "client.key"
     */
    private function deriveKeyFilename(string $certFilename): string
    {
        $base = pathinfo($certFilename, PATHINFO_FILENAME);
        return $base . '.key';
    }

    /**
     * Calculate the network address for a given IP and prefix length.
     * e.g. ('11.220.0.1', 16) → '11.220.0.0'
     */
    private function networkAddress(string $ip, int $prefix): string
    {
        $ipLong   = ip2long($ip);
        $maskLong = ~((1 << (32 - $prefix)) - 1) & 0xFFFFFFFF;
        return long2ip($ipLong & $maskLong);
    }
}
