<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\Nas;
use App\Models\AuditLog;
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
        ]);

        // Find the router by name (sanitized the same way as in MikrotikScriptService)
        $sanitizedName = preg_replace('/[^a-zA-Z0-9\-]/', '-', $data['router_name']);
        $router = Router::where('name', $data['router_name'])
            ->orWhereRaw("REPLACE(REPLACE(name, ' ', '-'), '_', '-') = ?", [$sanitizedName])
            ->first();

        if (!$router) {
            Log::warning('Router callback: no matching router found', $data);
            return response()->json(['status' => 'error', 'message' => 'Router not found'], 404);
        }

        $old = $router->toArray();

        // Update router with detected IPs and WireGuard public key
        $updates = [];
        if (!empty($data['wan_ip'])) {
            $updates['wan_ip'] = $data['wan_ip'];
        }
        if (!empty($data['vpn_ip'])) {
            $updates['vpn_ip'] = $data['vpn_ip'];
        }
        if (!empty($data['wg_public_key'])) {
            $updates['wg_public_key'] = $data['wg_public_key'];
        }

        if (!empty($updates)) {
            $router->update($updates);
        }

        // Sync NAS table for FreeRADIUS
        if ($router->wan_ip) {
            Nas::updateOrCreate(
                ['nasname' => $router->wan_ip],
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
            'router_id' => $router->id,
            'name'      => $router->name,
            'wan_ip'    => $router->wan_ip,
            'vpn_ip'    => $router->vpn_ip,
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => 'Router registered successfully',
            'router_id' => $router->id,
        ]);
    }
}