@extends('admin.layouts.app')
@section('title', 'MikroTik Monitor')
@push('styles')
<style>
.router-card { cursor: pointer; transition: transform .15s; }
.router-card:hover { transform: translateY(-2px); }
.status-online { color: #28a745; }
.status-offline { color: #dc3545; }
.pulse { animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
</style>
@endpush
@section('content')
<div class="row">
    <div class="col-sm-12 mb-3">
        <h5 class="mb-0">MikroTik Monitor</h5>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.isp.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">MikroTik Monitor</li>
            </ol>
        </nav>
    </div>

    @forelse($routers as $router)
    <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
        <a href="{{ route('admin.isp.mikrotik_monitor.show', $router['id']) }}" class="text-decoration-none">
            <div class="card router-card h-100 {{ $router['online'] ? 'border-success' : 'border-danger' }} border-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0">{{ $router['name'] }}</h6>
                        @if($router['online'])
                        <span class="badge bg-success pulse"><i class="bx bx-wifi"></i> Online</span>
                        @else
                        <span class="badge bg-danger"><i class="bx bx-wifi-off"></i> Offline</span>
                        @endif
                    </div>
                    <p class="small text-muted mb-1">{{ $router['wan_ip'] ?? 'N/A' }}</p>
                    @if($router['online'])
                    <div class="row g-1 mt-2">
                        <div class="col-6">
                            <small class="text-muted d-block">CPU</small>
                            <span class="fw-bold">{{ $router['cpu'] }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Memory</small>
                            <span class="fw-bold">{{ $router['memory'] }}</span>
                        </div>
                        <div class="col-12 mt-1">
                            <small class="text-muted d-block">Uptime</small>
                            <span>{{ $router['uptime'] }}</span>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Version</small>
                            <span class="small">{{ $router['version'] }}</span>
                        </div>
                    </div>
                    @else
                    <p class="text-muted small mt-2 mb-0"><i class="bx bx-error-circle"></i> Router unreachable</p>
                    @endif
                </div>
            </div>
        </a>
    </div>
    @empty
    <div class="col-12">
        <div class="card"><div class="card-body text-center text-muted py-5">
            <i class="bx bx-router" style="font-size:3rem"></i>
            <p class="mt-2">No active routers found. <a href="{{ route('admin.isp.routers.create') }}">Add a router</a></p>
        </div></div>
    </div>
    @endforelse
</div>
@endsection
