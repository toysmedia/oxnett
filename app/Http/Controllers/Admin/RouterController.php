<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\Nas;
use App\Models\AuditLog;
use App\Models\IspSetting;
use App\Services\MikrotikScriptService;
use App\Services\WireGuardService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class RouterController extends Controller
{
    public function __construct(protected MikrotikScriptService $scriptService) {}

    public function index()
    {
        $routers = Router::orderBy('name')->get();
        return view('admin.isp.routers.index', compact('routers'));
    }

    public function create()
    {
        return view('admin.isp.routers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'model'            => 'nullable|string|max:100',
            'routeros_version' => 'nullable|string|max:50',
            'notes'            => 'nullable|string',
            'is_active'        => 'boolean',
        ]);

        $validated['radius_secret']      = Str::random(16);
        $validated['wan_interface']      = 'ether1';
        $validated['customer_interface'] = 'bridge1';
        $validated['pppoe_pool_range']   = '10.10.1.1-10.10.1.254';
        $validated['hotspot_pool_range'] = '10.20.1.1-10.20.1.254';
        $validated['billing_domain']     = IspSetting::getValue('billing_domain', '');
        $validated['wan_ip']             = null;
        $validated['is_active']          = $request->boolean('is_active', true);

        $router = Router::create($validated);

        $wgOctet   = (($router->id % 253) + 2);
        $poolOctet = (($router->id - 1) % 254) + 1;
        $router->update([
            'ref_code'           => 'RTR-' . str_pad($router->id, 3, '0', STR_PAD_LEFT),
            'vpn_ip'             => '10.255.255.' . $wgOctet,
            'pppoe_pool_range'   => "10.10.{$poolOctet}.1-10.10.{$poolOctet}.254",
            'hotspot_pool_range' => "10.20.{$poolOctet}.1-10.20.{$poolOctet}.254",
        ]);

        AuditLog::record('router.created', Router::class, $router->id, [], $router->fresh()->toArray());

        return redirect()->route('admin.isp.routers.index')
            ->with('success', "Router '{$router->name}' created successfully.");
    }

    public function edit(Router $router)
    {
        return view('admin.isp.routers.edit', compact('router'));
    }

    public function update(Request $request, Router $router)
    {
        $old  = $router->toArray();
        $data = $request->validate([
            'name'               => 'required|string|max:100',
            'model'              => 'nullable|string|max:100',
            'routeros_version'   => 'nullable|string|max:50',
            'radius_secret'      => 'required|string|max:100',
            'wan_interface'      => 'required|string|max:50',
            'customer_interface' => 'required|string|max:50',
            'pppoe_pool_range'   => 'required|string',
            'hotspot_pool_range' => 'required|string',
            'billing_domain'     => 'nullable|string|max:255',
            'is_active'          => 'boolean',
            'notes'              => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $router->update($data);

        if ($router->vpn_ip || $router->wan_ip) {
            $this->syncNas($router);
        }

        AuditLog::record('router.updated', Router::class, $router->id, $old, $router->fresh()->toArray());

        return redirect()->route('admin.isp.routers.index')
            ->with('success', "Router '{$router->name}' updated.");
    }

    public function destroy(Router $router)
    {
        AuditLog::record('router.deleted', Router::class, $router->id, $router->toArray(), []);
        $nasIp = $router->vpn_ip ?: $router->wan_ip;
        if ($nasIp) {
            Nas::where('nasname', $nasIp)->delete();
        }
        // Remove WireGuard peer from server if key is known
        if ($router->wg_public_key) {
            try {
                app(WireGuardService::class)->removePeer($router->wg_public_key);
            } catch (\Throwable $e) {
                Log::error('RouterController: failed to remove WireGuard peer on delete', [
                    'router_id'  => $router->id,
                    'public_key' => $router->wg_public_key,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
        $router->delete();
        return redirect()->route('admin.isp.routers.index')->with('success', 'Router deleted.');
    }

    public function show(Router $router)
    {
        return view('admin.isp.routers.show', compact('router'));
    }

    public function script(Router $router)
    {
        $script = $this->scriptService->generate($router);
        return view('admin.isp.routers.mikrotik_script', compact('router', 'script'));
    }

    public function downloadScript(Router $router)
    {
        $script   = $this->scriptService->generate($router);
        $filename = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $router->name)) . '-mikrotik.rsc';
        return response($script, 200, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function provision(string $token)
    {
        $router = Router::where('ref_code', $token)
                        ->where('is_active', true)
                        ->firstOrFail();

        $script   = app(MikrotikScriptService::class)->generate($router);
        $filename = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $router->name)) . '-mikrotik.rsc';

        return response($script, 200, [
            'Content-Type'        => 'text/plain; charset=utf-8',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function serveHotspotFile(Router $router, string $file)
    {
        $allowedFiles = [
            'login.html'  => 'hotspot.login',
            'alogin.html' => 'hotspot.alogin',
            'status.html' => 'hotspot.status',
        ];

        if (!array_key_exists($file, $allowedFiles)) {
            abort(404);
        }

        $viewName = $allowedFiles[$file];

        if (!view()->exists($viewName)) {
            abort(404);
        }

        $parsedHost    = parse_url(config('app.url'), PHP_URL_HOST);
        $billingDomain = $router->billing_domain ?: ($parsedHost ?: 'localhost');
        $appName       = config('app.name', 'iNettotik');

        return response(
            view($viewName, compact('router', 'billingDomain', 'appName'))->render(),
            200,
            ['Content-Type' => 'text/html; charset=utf-8']
        );
    }

    public function downloadHotspotFiles(Router $router)
    {
        $tmpDir  = sys_get_temp_dir();
        $zipPath = $tmpDir . '/hotspot-' . $router->id . '-' . time() . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Failed to create ZIP file.');
        }

        $billingDomain = $router->billing_domain ?: parse_url(config('app.url'), PHP_URL_HOST);
        $appName       = config('app.name', 'iNettotik');

        $zip->addFromString('login.html',  view('hotspot.login',  compact('router', 'billingDomain', 'appName'))->render());
        $zip->addFromString('alogin.html', view('hotspot.alogin', compact('router', 'billingDomain', 'appName'))->render());
        $zip->addFromString('status.html', view('hotspot.status', compact('router', 'billingDomain', 'appName'))->render());
        $zip->close();

        return response()->download($zipPath, "hotspot-files-{$router->id}.zip", [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Test connection to a router via its VPN IP using RouterOS REST API.
     *
     * Uses port 8728 (RouterOS API) not 80.
     * Also fetches MAC address from /interface/ethernet for WinBox display.
     * Returns online/offline status based on whether the API responds.
     */
    public function testConnection(Router $router)
    {
        $result = [
            'api_reachable'     => false,
            'radius_configured' => false,
            'online'            => false,
            'router_identity'   => null,
            'uptime'            => null,
            'version'           => null,
            'mac_address'       => null,
            'board_name'        => null,
            'cpu_load'          => null,
            'free_memory'       => null,
            'error'             => null,
        ];

        // Check RADIUS/NAS table — use vpn_ip (with fallback to wan_ip) to match syncNas
        $nasIp = $router->vpn_ip ?: $router->wan_ip;
        $result['radius_configured'] = $nasIp ? Nas::where('nasname', $nasIp)->exists() : false;

        // Prefer VPN IP (WireGuard tunnel) over WAN IP
        $ip      = $router->vpn_ip ?: $router->wan_ip;
        // RouterOS REST API runs on port 80 by default but we enable it on 8728
        // The script sets: /ip service set api port=8728
        // REST API on RouterOS 7.x runs on /rest/* via www service (port 80)
        // or dedicated api-ssl (443). We use the plain API port 8728 with the
        // RouterOS REST API which is available on the www port (80) in ROS 7.x.
        // Use port 80 for REST, 8728 for raw API. REST API is on www (80/443).
        $apiPort = $router->api_port ?? 80;
        $apiUser = $router->api_username ?? 'admin';
        $apiPass = $router->api_password ?? '';

        if (!$ip) {
            $result['error'] = 'No IP address configured for this router. Run the provision script first.';
            return response()->json($result);
        }

        try {
            // RouterOS 7.x REST API is available on the www service port (default 80)
            // at /rest/* endpoints. We try port 80 first, then 443.
            $baseUrl  = "http://{$ip}:{$apiPort}";
            $http     = Http::withBasicAuth($apiUser, $apiPass)
                            ->timeout(5)
                            ->withoutVerifying(); // skip SSL for VPN connections

            // Fetch system resource
            $response = $http->get("{$baseUrl}/rest/system/resource");

            if ($response->successful()) {
                $data = $response->json();
                $result['api_reachable']   = true;
                $result['online']          = true;
                $result['board_name']      = $data['board-name']   ?? null;
                $result['uptime']          = $data['uptime']        ?? null;
                $result['version']         = $data['version']       ?? null;
                $result['cpu_load']        = isset($data['cpu-load']) ? $data['cpu-load'] . '%' : null;
                $result['free_memory']     = isset($data['free-memory'])
                                             ? round($data['free-memory'] / 1048576, 1) . ' MiB free'
                                             : null;

                // Fetch router identity
                $identResp = $http->get("{$baseUrl}/rest/system/identity");
                if ($identResp->successful()) {
                    $result['router_identity'] = $identResp->json()['name'] ?? null;
                }

                // Fetch MAC address of the WAN interface for WinBox column
                $wanIface  = $router->wan_interface ?: 'ether1';
                $ifResp    = $http->get("{$baseUrl}/rest/interface", [
                    'name' => $wanIface,
                ]);
                if ($ifResp->successful()) {
                    $interfaces = $ifResp->json();
                    if (!empty($interfaces)) {
                        $iface = is_array($interfaces[0] ?? null) ? $interfaces[0] : $interfaces;
                        $result['mac_address'] = $iface['mac-address'] ?? null;
                    }
                }

                // If MAC found, save it to the router record for persistent display
                if ($result['mac_address'] && $router->mac_address !== $result['mac_address']) {
                    $router->update(['mac_address' => $result['mac_address']]);
                }

                // Save board name and version back to DB if changed
                $updates = [];
                if ($result['board_name'] && $router->model !== $result['board_name']) {
                    $updates['model'] = $result['board_name'];
                }
                if ($result['version'] && $router->routeros_version !== $result['version']) {
                    $updates['routeros_version'] = $result['version'];
                }
                if (!empty($updates)) {
                    $router->update($updates);
                }

            } else {
                $result['error'] = 'Router API responded with HTTP ' . $response->status()
                                 . '. Check credentials or ensure the www service is enabled on the router.';
            }
        } catch (\Exception $e) {
            $result['online'] = false;
            $result['error']  = 'Could not connect: ' . $e->getMessage();
        }

        return response()->json($result);
    }

    /**
     * Quick online check for the index table status column.
     * Lighter than full testConnection — just pings the REST API.
     */
    public function pingStatus(Router $router)
    {
        $ip      = $router->vpn_ip ?: $router->wan_ip;
        $apiPort = $router->api_port ?? 80;
        $apiUser = $router->api_username ?? 'admin';
        $apiPass = $router->api_password ?? '';

        if (!$ip) {
            return response()->json(['online' => false]);
        }

        try {
            $response = Http::withBasicAuth($apiUser, $apiPass)
                            ->timeout(3)
                            ->withoutVerifying()
                            ->get("http://{$ip}:{$apiPort}/rest/system/identity");

            return response()->json([
                'online'   => $response->successful(),
                'identity' => $response->successful() ? ($response->json()['name'] ?? null) : null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['online' => false]);
        }
    }

    protected function syncNas(Router $router): void
    {
        $nasIp = $router->vpn_ip ?: $router->wan_ip;
        if (!$nasIp) {
            return;
        }
        Nas::updateOrCreate(
            ['nasname' => $nasIp],
            [
                'shortname'   => $router->name,
                'type'        => 'mikrotik',
                'secret'      => $router->radius_secret,
                'description' => $router->name . ' - MikroTik',
            ]
        );
    }
}