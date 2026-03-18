@extends('customer.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col">
        <h4 class="fw-bold mb-0">
            <i class="fa-solid fa-gauge-high text-primary me-2"></i>
            Welcome back, {{ auth('customer')->user()->name ?? auth('customer')->user()->username }}
        </h4>
        <p class="text-muted small mb-0">
            {{ now()->format('l, d F Y') }}
        </p>
    </div>
    <div class="col-auto">
        <a href="{{ route('customer.payments.renew') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-rotate me-1"></i>Renew Package
        </a>
    </div>
</div>

{{-- Top stat cards --}}
@if($subscriber)
<div class="row g-3 mb-4">
    {{-- Package card --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <span class="p-2 rounded bg-primary bg-opacity-10 me-2">
                        <i class="fa-solid fa-box text-primary"></i>
                    </span>
                    <span class="text-muted small">Current Package</span>
                </div>
                <div class="fw-bold fs-5">{{ $subscriber->package?->name ?? 'No Package' }}</div>
                @if($subscriber->package)
                <div class="small text-muted mt-1">
                    <i class="fa-solid fa-arrow-up text-success"></i> {{ $subscriber->package->speed_upload }}Mbps &nbsp;
                    <i class="fa-solid fa-arrow-down text-info"></i> {{ $subscriber->package->speed_download }}Mbps
                </div>
                <div class="small text-muted">KES {{ number_format($subscriber->package->price, 0) }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Status card --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <span class="p-2 rounded bg-success bg-opacity-10 me-2">
                        <i class="fa-solid fa-circle-check text-success"></i>
                    </span>
                    <span class="text-muted small">Account Status</span>
                </div>
                <div class="fw-bold fs-5">
                    <span class="badge fs-6 {{ $subscriber->status === 'active' ? 'bg-success' : ($subscriber->status === 'suspended' ? 'bg-warning text-dark' : 'bg-danger') }} text-capitalize">
                        {{ $subscriber->status }}
                    </span>
                </div>
                <div class="small text-muted mt-1 text-capitalize">
                    <i class="fa-solid fa-plug me-1"></i>{{ $subscriber->connection_type ?? 'PPPoE' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Expiry countdown card --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <span class="p-2 rounded bg-warning bg-opacity-10 me-2">
                        <i class="fa-solid fa-clock text-warning"></i>
                    </span>
                    <span class="text-muted small">Expires</span>
                </div>
                @if($subscriber->expires_at)
                <div class="fw-bold fs-6 {{ $subscriber->isExpired() ? 'text-danger' : '' }}">
                    {{ $subscriber->expires_at->format('d M Y H:i') }}
                </div>
                <div class="small mt-1 countdown-digits {{ $subscriber->isExpired() ? 'text-danger' : 'text-success' }}"
                     id="main-countdown" data-expiry="{{ $subscriber->expires_at->toISOString() }}">
                    {{ $subscriber->isExpired() ? 'Expired' : $subscriber->expires_at->diffForHumans() }}
                </div>
                @else
                <div class="text-muted">No expiry set</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Data usage card --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <span class="p-2 rounded bg-info bg-opacity-10 me-2">
                        <i class="fa-solid fa-chart-bar text-info"></i>
                    </span>
                    <span class="text-muted small">Total Data Used</span>
                </div>
                <div class="fw-bold fs-5">{{ $usage['total_formatted'] }}</div>
                <div class="small text-muted mt-1">
                    <i class="fa-solid fa-arrow-down text-info me-1"></i>{{ $usage['download_formatted'] }} DL &nbsp;
                    <i class="fa-solid fa-arrow-up text-success me-1"></i>{{ $usage['upload_formatted'] }} UL
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick actions --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="{{ route('customer.payments.renew') }}" class="btn btn-primary">
                    <i class="fa-solid fa-rotate me-1"></i>Renew Package
                </a>
                <a href="{{ route('customer.payments.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-receipt me-1"></i>View Payments
                </a>
                <a href="{{ route('customer.support.create') }}" class="btn btn-outline-info">
                    <i class="fa-solid fa-headset me-1"></i>Get Support
                </a>
                <a href="{{ route('customer.package.index') }}" class="btn btn-outline-success">
                    <i class="fa-solid fa-box me-1"></i>View Packages
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Active sessions --}}
@if($activeSessions->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-wifi me-1 text-success"></i>Active Sessions</h6>
        <span class="badge bg-success rounded-pill">{{ $activeSessions->count() }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>IP Address</th><th>NAS</th><th>Connected Since</th></tr>
            </thead>
            <tbody>
                @foreach($activeSessions as $session)
                <tr>
                    <td><code>{{ $session->framedipaddress ?? 'N/A' }}</code></td>
                    <td><code>{{ $session->nasipaddress ?? 'N/A' }}</code></td>
                    <td>{{ $session->acctstarttime?->format('d M Y H:i') ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Recent sessions --}}
@if($recentSessions->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-history me-1 text-muted"></i>Recent Sessions</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr><th>Start</th><th>End</th><th>Download</th><th>Upload</th><th>IP</th></tr>
            </thead>
            <tbody>
                @foreach($recentSessions as $session)
                <tr>
                    <td>{{ $session->acctstarttime?->format('d M H:i') }}</td>
                    <td>{{ $session->acctstoptime ? $session->acctstoptime->format('d M H:i') : '<span class="badge bg-success">Active</span>' }}</td>
                    <td>{{ $usageService->formatBytes($session->acctoutputoctets ?? 0) }}</td>
                    <td>{{ $usageService->formatBytes($session->acctinputoctets ?? 0) }}</td>
                    <td><code>{{ $session->framedipaddress ?? 'N/A' }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Recent payments --}}
@if($recentPayments->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-money-bill-wave me-1 text-success"></i>Recent Payments</h6>
        <a href="{{ route('customer.payments.index') }}" class="btn btn-sm btn-outline-secondary">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr><th>Receipt</th><th>Amount</th><th>Package</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                @foreach($recentPayments as $p)
                <tr>
                    <td><code>{{ $p->mpesa_receipt_number ?? 'Pending' }}</code></td>
                    <td>KES {{ number_format($p->amount, 0) }}</td>
                    <td>{{ $p->package?->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge {{ $p->status === 'completed' ? 'bg-success' : ($p->status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}">
                            {{ ucfirst($p->status) }}
                        </span>
                    </td>
                    <td>{{ $p->created_at->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@else
<div class="alert alert-info">
    <strong>No subscriber account linked.</strong>
    Please contact your ISP to get connected.
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const el = document.getElementById('main-countdown');
    if (!el || !el.dataset.expiry) return;
    const expiry = new Date(el.dataset.expiry).getTime();
    function update() {
        const diff = expiry - Date.now();
        if (diff <= 0) { el.textContent = 'Expired'; return; }
        const d = Math.floor(diff / 86400000);
        const h = Math.floor((diff % 86400000) / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        const pad = n => String(n).padStart(2, '0');
        el.textContent = d + 'd ' + pad(h) + 'h ' + pad(m) + 'm ' + pad(s) + 's remaining';
    }
    update();
    setInterval(update, 1000);
})();
</script>
@endpush


@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <h4 class="fw-bold">👤 My Account</h4>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@if($subscriber)
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">📦 Current Subscription</h6>
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Username</td><td class="fw-bold">{{ $subscriber->username }}</td></tr>
                    <tr><td class="text-muted">Package</td><td>{{ $subscriber->package?->name ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">Type</td><td><span class="badge bg-primary text-capitalize">{{ $subscriber->connection_type }}</span></td></tr>
                    <tr><td class="text-muted">Status</td>
                        <td><span class="badge {{ $subscriber->status === 'active' ? 'bg-success' : 'bg-danger' }} text-capitalize">{{ $subscriber->status }}</span></td></tr>
                    <tr><td class="text-muted">Expires</td>
                        <td class="{{ $subscriber->isExpired() ? 'text-danger' : '' }}">
                            {{ $subscriber->expires_at?->format('d M Y H:i') ?? 'No expiry' }}
                        </td></tr>
                </table>
            </div>
        </div>
    </div>

    @if($activeSessions->isNotEmpty())
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">🌐 Active Sessions</h6>
                @foreach($activeSessions as $session)
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                    <div>
                        <div class="small fw-bold">{{ $session->framedipaddress ?? 'N/A' }}</div>
                        <div class="small text-muted">Since: {{ $session->acctstarttime?->format('H:i d M') }}</div>
                    </div>
                    <div class="small text-muted">{{ $session->nasipaddress }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Renew/Upgrade -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">🔄 Renew or Upgrade Package</h6>
        <form action="{{ route('customer.renew') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-md-4">
                <select name="package_id" class="form-select" required>
                    <option value="">Select Package</option>
                    @foreach($packages as $p)
                    <option value="{{ $p->id }}" {{ $subscriber->isp_package_id == $p->id ? 'selected' : '' }}>
                        {{ $p->name }} - KES {{ number_format($p->price, 0) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="tel" name="phone" class="form-control" placeholder="Your M-Pesa phone (e.g. 0712345678)" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">💳 Pay with M-Pesa</button>
            </div>
        </form>
    </div>
</div>
@else
<div class="alert alert-info">
    <strong>No subscriber account found.</strong>
    <a href="{{ route('customer.buy') }}" class="btn btn-sm btn-primary ms-2">Buy a Package</a>
</div>
@endif

<!-- Payment History -->
@if($recentPayments->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h6 class="fw-bold mb-0">💰 Payment History</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Receipt</th><th>Amount</th><th>Package</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @foreach($recentPayments as $p)
                    <tr>
                        <td><code>{{ $p->mpesa_receipt_number ?? 'Pending' }}</code></td>
                        <td>KES {{ number_format($p->amount, 0) }}</td>
                        <td>{{ $p->package?->name ?? 'N/A' }}</td>
                        <td><span class="badge {{ $p->status === 'completed' ? 'bg-success' : ($p->status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ ucfirst($p->status) }}</span></td>
                        <td>{{ $p->created_at->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
