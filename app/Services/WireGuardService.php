<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class WireGuardService
{
    protected string $interface;

    public function __construct()
    {
        $this->interface = config('wireguard.interface', 'wg0');
    }

    /**
     * Add a WireGuard peer to the server interface.
     */
    public function addPeer(string $publicKey, string $allowedIps): bool
    {
        $result = Process::run([
            'sudo', 'wg', 'set', $this->interface,
            'peer', $publicKey,
            'allowed-ips', $allowedIps,
            'persistent-keepalive', '25',
        ]);

        if ($result->successful()) {
            Log::info('WireGuardService: peer added', [
                'interface'   => $this->interface,
                'public_key'  => $publicKey,
                'allowed_ips' => $allowedIps,
            ]);
            $this->savePersist();
            return true;
        }

        Log::error('WireGuardService: failed to add peer', [
            'interface'   => $this->interface,
            'public_key'  => $publicKey,
            'allowed_ips' => $allowedIps,
            'output'      => $result->output(),
            'error'       => $result->errorOutput(),
        ]);

        return false;
    }

    /**
     * Remove a WireGuard peer from the server interface.
     */
    public function removePeer(string $publicKey): bool
    {
        $result = Process::run([
            'sudo', 'wg', 'set', $this->interface,
            'peer', $publicKey, 'remove',
        ]);

        if ($result->successful()) {
            Log::info('WireGuardService: peer removed', [
                'interface'  => $this->interface,
                'public_key' => $publicKey,
            ]);
            $this->savePersist();
            return true;
        }

        Log::error('WireGuardService: failed to remove peer', [
            'interface'  => $this->interface,
            'public_key' => $publicKey,
            'output'     => $result->output(),
            'error'      => $result->errorOutput(),
        ]);

        return false;
    }

    /**
     * Persist the current WireGuard configuration to disk.
     *
     * Uses `wg showconf` piped via `tee` into the conf file so that peers added
     * at runtime survive a reboot.  (`wg-quick save` is not a valid subcommand
     * on most distributions and silently does nothing.)
     */
    public function savePersist(): bool
    {
        $confPath = "/etc/wireguard/{$this->interface}.conf";

        $showConf = Process::run(['sudo', 'wg', 'showconf', $this->interface]);

        if (! $showConf->successful()) {
            Log::error('WireGuardService: wg showconf failed', [
                'interface' => $this->interface,
                'output'    => $showConf->output(),
                'error'     => $showConf->errorOutput(),
            ]);
            return false;
        }

        $result = Process::run(['sudo', 'tee', $confPath], input: $showConf->output());

        if ($result->successful()) {
            Log::info('WireGuardService: configuration saved', [
                'interface' => $this->interface,
                'path'      => $confPath,
            ]);
            return true;
        }

        Log::error('WireGuardService: failed to save configuration', [
            'interface' => $this->interface,
            'path'      => $confPath,
            'output'    => $result->output(),
            'error'     => $result->errorOutput(),
        ]);

        return false;
    }
}
