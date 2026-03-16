@extends('layouts.app')

@section('title', 'My Account')

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
