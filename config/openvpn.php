<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenVPN Management Tunnel
    |--------------------------------------------------------------------------
    |
    | These settings are used to generate MikroTik provisioning scripts that
    | establish an OpenVPN tunnel back to the billing server.
    |
    */

    // Default OpenVPN port (non-standard to avoid ISP blocking)
    'port' => (int) env('OPENVPN_PORT', 443),

    // Public IP or hostname of the billing server.
    // MikroTik routers connect to this address to establish the OpenVPN tunnel.
    'billing_server_public_ip' => env('BILLING_SERVER_PUBLIC_IP', ''),

    // VPN IP of the billing server (assigned by OpenVPN, e.g. 10.8.0.1).
    // Used as the RADIUS server address in generated scripts.
    'billing_server_vpn_ip' => env('BILLING_SERVER_VPN_IP', ''),

    // Subnet of the billing server (CIDR notation, e.g. 10.8.0.0/24).
    // Used in firewall rules to allow management traffic from the billing server.
    'billing_server_subnet' => env('BILLING_SERVER_SUBNET', ''),
];
