<?php
namespace App\Services;

use App\Models\Router;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MikrotikApiService
{
    protected Router $router;
    protected string $baseUrl;
    protected array $auth;

    public function init(Router $router): self
    {
        $this->router  = $router;
        $ip            = $router->wan_ip ?? $router->vpn_ip ?? '127.0.0.1';
        $port          = $router->api_port ?? 80;
        $apiPass = '';
        if ($router->api_password) {
            try {
                $apiPass = decrypt($router->api_password);
            } catch (\Exception $e) {
                $apiPass = $router->api_password; // fallback if not encrypted
            }
        }
        $this->baseUrl = "http://{$ip}:{$port}/rest";
        $this->auth    = [
            $router->api_username ?? 'admin',
            $apiPass,
        ];
        return $this;
    }

    /**
     * Get system resource info (CPU, RAM, uptime, version).
     */
    public function getSystemResource(): array
    {
        return $this->request('/system/resource');
    }

    /**
     * Get system health (temperature, voltage).
     */
    public function getSystemHealth(): array
    {
        return $this->request('/system/health');
    }

    /**
     * Get router board info (model, serial).
     */
    public function getRouterBoard(): array
    {
        return $this->request('/system/routerboard');
    }

    /**
     * Get list of interfaces with stats.
     */
    public function getInterfaces(): array
    {
        return $this->request('/interface');
    }

    /**
     * Get traffic stats for a specific interface.
     */
    public function getInterfaceTraffic(string $interface): array
    {
        return $this->request('/interface/monitor-traffic', [
            'interface' => $interface,
            'once'      => '',
        ]);
    }

    /**
     * Get count of active PPPoE and Hotspot users.
     */
    public function getActiveUsers(): array
    {
        $pppoe   = $this->request('/ppp/active');
        $hotspot = $this->request('/ip/hotspot/active');

        return [
            'pppoe'   => is_array($pppoe)   ? count($pppoe)   : 0,
            'hotspot' => is_array($hotspot) ? count($hotspot) : 0,
        ];
    }

    /**
     * Internal HTTP request to MikroTik REST API.
     */
    protected function request(string $path, array $query = []): array
    {
        try {
            $response = Http::timeout(5)
                ->withBasicAuth(...$this->auth)
                ->get($this->baseUrl . $path, $query);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::warning('MikroTik API non-200', [
                'router' => $this->router->name,
                'path'   => $path,
                'status' => $response->status(),
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error('MikroTik API error', [
                'router' => $this->router->name ?? 'unknown',
                'path'   => $path,
                'error'  => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Check if router is reachable.
     */
    public function isOnline(): bool
    {
        $res = $this->getSystemResource();
        return !empty($res);
    }
}
