<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\Nas;
use App\Models\AuditLog;
use App\Services\WireGuardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RouterCallbackController extends Controller
{
    public function callback(Request $request)
    {
        $data = $request->validate([
            'router_name'  => 'required|string|max:100',
            'wan_ip'       => 'nullable|ip',
            'vpn_ip'       => 'nullable|ip',
            'radius_secret' => 'nullable|string',
            'wg_public_key' => 'nullable|string|max:255',
            'phase'        => 'nullable|integer|min:0|max:3',
        ]);

        // Find the router by name (sanitized the same way as in MikrotikScriptService)
        $sanitizedName = preg_replace('/[^a-zA-Z0-9\-]/', '-', $data['router_name']);
        $router = Router::where('name', $data['router_name'])
            ->orWhereRaw("REPLACE(REPLACE(name, ' ', '-'), '_', '-') = ?", [$sanitizedName])
            ->first();

        if (!$router) {
            Log::warning('Router callback: no matching router found', ['router_name' => $data['router_name']]);
            return response()->json(['status' => 'error', 'message' => 'Router not found'], 404);
        }

        $old = $router->toArray();

        // Update router with detected IPs and WireGuard public key
        $updates = ['last_heartbeat_at' => now()];
        if (!empty($data['wan_ip'])) {
            $updates['wan_ip'] = $data['wan_ip'];
        }
        if (!empty($data['vpn_ip'])) {
            $updates['vpn_ip'] = $data['vpn_ip'];
        }
        $newWgKey = !empty($data['wg_public_key']) ? $data['wg_public_key'] : null;
        if ($newWgKey) {
            $updates['wg_public_key'] = $newWgKey;
        }
        // Advance provision_phase only forward, never backward
        if (!empty($data['phase']) && (int)$data['phase'] > (int)$router->provision_phase) {
            $updates['provision_phase'] = (int)$data['phase'];
        }

        $router->update($updates);

        // Auto-register WireGuard peer on the server when we receive a public key.
        // Use the router's authoritative vpn_ip from the DB (set by the server on creation),
        // not the client-provided value, to prevent IP spoofing.
        if ($newWgKey) {
            $vpnIp = $router->fresh()->vpn_ip;
            if ($vpnIp && filter_var($vpnIp, FILTER_VALIDATE_IP)) {
                try {
                    app(WireGuardService::class)->addPeer($newWgKey, "{$vpnIp}/32");
                } catch (\Throwable $e) {
                    Log::error('Router callback: failed to register WireGuard peer', [
                        'router_id'  => $router->id,
                        'public_key' => $newWgKey,
                        'vpn_ip'     => $vpnIp,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }
        }

        // Sync NAS table for FreeRADIUS — use vpn_ip (packets arrive from VPN), fallback to wan_ip
        $nasIp = $router->vpn_ip ?: $router->wan_ip;
        if ($nasIp) {
            Nas::updateOrCreate(
                ['nasname' => $nasIp],
                [
                    'shortname'   => $router->name,
                    'type'        => 'other',
                    'secret'      => $router->radius_secret,
                    'description' => $router->name . ' - MikroTik',
                ]
            );
        }

        AuditLog::record('router.callback', Router::class, $router->id, $old, $router->fresh()->toArray());

        Log::info('Router callback: updated successfully', [
            'router_id'       => $router->id,
            'name'            => $router->name,
            'wan_ip'          => $router->wan_ip,
            'vpn_ip'          => $router->vpn_ip,
            'provision_phase' => $router->provision_phase,
        ]);

        return response()->json([
            'status'          => 'success',
            'message'         => 'Router registered successfully',
            'router_id'       => $router->id,
            'provision_phase' => $router->provision_phase,
        ]);
    }

    /**
     * Heartbeat endpoint — called by MikroTik scheduler every 5 minutes.
     */
    public function heartbeat(Request $request)
    {
        $data = $request->validate([
            'router_name' => 'required|string|max:100',
            'vpn_ip'      => 'nullable|ip',
        ]);

        $sanitizedName = preg_replace('/[^a-zA-Z0-9\-]/', '-', $data['router_name']);
        $router = Router::where('name', $data['router_name'])
            ->orWhereRaw("REPLACE(REPLACE(name, ' ', '-'), '_', '-') = ?", [$sanitizedName])
            ->first();

        if (!$router) {
            return response()->json(['status' => 'error', 'message' => 'Router not found'], 404);
        }

        $updates = ['last_heartbeat_at' => now()];
        if (!empty($data['vpn_ip'])) {
            $updates['vpn_ip'] = $data['vpn_ip'];
        }
        $router->update($updates);

        return response()->json([
            'status' => 'ok',
            'phase'  => $router->provision_phase,
        ]);
    }

    /**
     * Phase-complete endpoint — called when each phase finishes.
     */
    public function phaseComplete(Request $request)
    {
        $data = $request->validate([
            'router_name' => 'required|string|max:100',
            'phase'       => 'required|integer|min:1|max:3',
        ]);

        $sanitizedName = preg_replace('/[^a-zA-Z0-9\-]/', '-', $data['router_name']);
        $router = Router::where('name', $data['router_name'])
            ->orWhereRaw("REPLACE(REPLACE(name, ' ', '-'), '_', '-') = ?", [$sanitizedName])
            ->first();

        if (!$router) {
            return response()->json(['status' => 'error', 'message' => 'Router not found'], 404);
        }

        if ((int)$data['phase'] > (int)$router->provision_phase) {
            $router->update([
                'provision_phase'  => (int)$data['phase'],
                'last_heartbeat_at' => now(),
            ]);
        }

        Log::info('Router phase complete', [
            'router_id' => $router->id,
            'phase'     => $data['phase'],
        ]);

        return response()->json([
            'status' => 'ok',
            'phase'  => $router->provision_phase,
        ]);
    }
}