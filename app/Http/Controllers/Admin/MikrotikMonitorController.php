<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\MikrotikApiService;
use Illuminate\Http\Request;

class MikrotikMonitorController extends Controller
{
    public function __construct(protected MikrotikApiService $api) {}

    public function index()
    {
        $routers = Router::where('is_active', true)->get()->map(function ($router) {
            $online = false;
            $resource = [];
            try {
                $this->api->init($router);
                $online   = $this->api->isOnline();
                $resource = $online ? $this->api->getSystemResource() : [];
            } catch (\Exception $e) {
                // Router unreachable
            }

            return [
                'id'       => $router->id,
                'name'     => $router->name,
                'wan_ip'   => $router->wan_ip,
                'online'   => $online,
                'cpu'      => $resource['cpu-load'] ?? 'N/A',
                'memory'   => isset($resource['free-memory'], $resource['total-memory'])
                    ? round((1 - $resource['free-memory'] / $resource['total-memory']) * 100) . '%'
                    : 'N/A',
                'uptime'   => $resource['uptime'] ?? 'N/A',
                'version'  => $resource['version'] ?? 'N/A',
                'board'    => $resource['board-name'] ?? $router->name,
            ];
        });

        return view('admin.isp.mikrotik_monitor.index', compact('routers'));
    }

    public function show(Router $router)
    {
        return view('admin.isp.mikrotik_monitor.show', compact('router'));
    }

    public function getData(Router $router)
    {
        try {
            $this->api->init($router);
            $online = $this->api->isOnline();

            if (!$online) {
                return response()->json(['online' => false]);
            }

            $resource   = $this->api->getSystemResource();
            $health     = $this->api->getSystemHealth();
            $board      = $this->api->getRouterBoard();
            $interfaces = $this->api->getInterfaces();
            $users      = $this->api->getActiveUsers();

            return response()->json([
                'online'     => true,
                'resource'   => $resource,
                'health'     => $health,
                'board'      => $board,
                'interfaces' => $interfaces,
                'users'      => $users,
                'timestamp'  => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['online' => false, 'error' => $e->getMessage()]);
        }
    }
}
