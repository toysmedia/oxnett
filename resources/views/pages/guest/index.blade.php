@extends("layouts.public")
@section('title', 'Home')

@push('styles')
<style>
/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4f46e5 70%, #7c3aed 100%);
    color: #fff;
    padding: 80px 0 60px;
    position: relative;
    overflow: hidden;
}
.hero-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.hero-badge {
    display: inline-block;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 20px;
    padding: 5px 16px;
    font-size: .8rem;
    font-weight: 600;
    letter-spacing: .5px;
    margin-bottom: 20px;
}
.hero-title {
    font-size: 2.6rem;
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 18px;
    text-shadow: 0 2px 12px rgba(0,0,0,.15);
}
.hero-subtitle {
    font-size: 1.1rem;
    opacity: .88;
    line-height: 1.7;
    margin-bottom: 32px;
    max-width: 560px;
}
.hero-buttons .btn { min-width: 140px; }

/* Login Cards */
.login-card-section { padding: 48px 0; background: #f8f9fb; }
.login-card {
    background: #fff;
    border-radius: 14px;
    padding: 28px 20px;
    text-align: center;
    box-shadow: 0 2px 16px rgba(0,0,0,.07);
    transition: transform .2s, box-shadow .2s;
    height: 100%;
    border: 2px solid transparent;
}
.login-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(79,70,229,.13);
    border-color: #4f46e5;
}
.login-card .icon-circle {
    width: 56px; height: 56px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; margin: 0 auto 14px;
}

/* Features */
.features-section { padding: 64px 0; }
.feature-card {
    background: #fff;
    border-radius: 12px;
    padding: 28px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    border: 1px solid #e5e7eb;
    height: 100%;
}
.feature-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; margin-bottom: 14px;
}

/* Packages */
.packages-section { padding: 64px 0; background: #f8f9fb; }
.package-card {
    background: #fff;
    border-radius: 14px;
    padding: 28px 20px;
    text-align: center;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    border: 2px solid #e5e7eb;
    transition: border-color .2s, box-shadow .2s;
    height: 100%;
}
.package-card:hover {
    border-color: #4f46e5;
    box-shadow: 0 4px 24px rgba(79,70,229,.12);
}
.package-price {
    font-size: 2rem; font-weight: 800;
    color: #4f46e5; line-height: 1.1;
}

/* Contact */
.contact-section { padding: 64px 0; }
.section-title { font-size: 1.8rem; font-weight: 700; color: #1e1b4b; }
.section-subtitle { font-size: 1rem; color: #6b7280; }

@media (max-width: 767px) {
    .hero-title { font-size: 1.9rem; }
    .hero-section { padding: 52px 0 44px; }
}
</style>
@endpush

@section('content')

{{-- Hero Section --}}
<div class="hero-section">
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <div class="hero-badge">
                    <i class="bx bx-broadcast me-1"></i> Kenyan ISP Management SaaS
                </div>
                <h1 class="hero-title">
                    Manage Your ISP<br>
                    <span style="color:#a5b4fc;">Smarter &amp; Faster</span>
                </h1>
                <p class="hero-subtitle">
                    OxNet is a complete multi-tenant ISP management platform built for Kenyan internet service providers.
                    Automate PPPoE billing, MikroTik management, M-Pesa payments, and subscriber self-service — all in one place.
                </p>
                <div class="hero-buttons d-flex flex-wrap gap-3">
                    <a href="{{ route('admin.login') }}" class="btn btn-light btn-lg fw-semibold">
                        <i class="bx bx-log-in me-1"></i> Admin Login
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg fw-semibold">
                        <i class="bx bx-user me-1"></i> Customer Portal
                    </a>
                </div>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block">
                @if(config('settings.system_general.logo_path'))
                    <img src="{{ asset('storage/' . config('settings.system_general.logo_path')) }}"
                         alt="{{ config('settings.system_general.title', 'OxNet') }}"
                         class="img-fluid" style="max-height:200px;filter:drop-shadow(0 8px 32px rgba(0,0,0,.25));">
                @else
                    <div style="font-size:7rem;opacity:.7;text-shadow:0 4px 24px rgba(0,0,0,.2);">📡</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Quick Access Login Cards --}}
<div class="login-card-section">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title mb-1">Quick Access</h2>
            <p class="section-subtitle">Choose your portal to get started</p>
        </div>
        <div class="row g-3 justify-content-center">
            <div class="col-6 col-md-3 col-lg-2">
                <a href="{{ route('login') }}" class="login-card d-block text-decoration-none text-dark">
                    <div class="icon-circle" style="background:#ede9fe;">
                        <i class="bx bx-user" style="color:#7c3aed;"></i>
                    </div>
                    <div class="fw-semibold small">Customer</div>
                    <div class="text-muted" style="font-size:.75rem;">Self-service portal</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="{{ route('seller.login') }}" class="login-card d-block text-decoration-none text-dark">
                    <div class="icon-circle" style="background:#fef3c7;">
                        <i class="bx bx-store" style="color:#d97706;"></i>
                    </div>
                    <div class="fw-semibold small">Seller / Agent</div>
                    <div class="text-muted" style="font-size:.75rem;">Reseller dashboard</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="{{ route('admin.login') }}" class="login-card d-block text-decoration-none text-dark">
                    <div class="icon-circle" style="background:#e0e7ff;">
                        <i class="bx bx-shield-quarter" style="color:#4f46e5;"></i>
                    </div>
                    <div class="fw-semibold small">ISP Admin</div>
                    <div class="text-muted" style="font-size:.75rem;">Tenant management</div>
                </a>
            </div>
            @if(Route::has('community.login'))
            <div class="col-6 col-md-3 col-lg-2">
                <a href="{{ route('community.login') }}" class="login-card d-block text-decoration-none text-dark">
                    <div class="icon-circle" style="background:#d1fae5;">
                        <i class="bx bx-group" style="color:#059669;"></i>
                    </div>
                    <div class="fw-semibold small">Community</div>
                    <div class="text-muted" style="font-size:.75rem;">Forum &amp; support</div>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Features Section --}}
<div class="features-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title mb-1">Everything You Need to Run Your ISP</h2>
            <p class="section-subtitle">Powerful tools built specifically for Kenyan internet service providers</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#ede9fe;"><i class="bx bx-buildings" style="color:#7c3aed;"></i></div>
                    <h6 class="fw-bold mb-2">Multi-Tenant Platform</h6>
                    <p class="text-muted small mb-0">Each ISP gets their own isolated environment with custom branding, settings, and subscriber database.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#fce7f3;"><i class="bx bx-router" style="color:#db2777;"></i></div>
                    <h6 class="fw-bold mb-2">MikroTik Integration</h6>
                    <p class="text-muted small mb-0">Auto-configure PPPoE profiles, manage hotspot users, and monitor your MikroTik routers in real-time via RouterOS API.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#dcfce7;"><i class="bx bx-money" style="color:#16a34a;"></i></div>
                    <h6 class="fw-bold mb-2">M-Pesa STK Push</h6>
                    <p class="text-muted small mb-0">Customers pay via M-Pesa STK Push. Payments are automatically reconciled and PPPoE accounts activated instantly.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#fef3c7;"><i class="bx bx-group" style="color:#d97706;"></i></div>
                    <h6 class="fw-bold mb-2">Subscriber Management</h6>
                    <p class="text-muted small mb-0">Manage PPPoE and hotspot subscribers. Handle expirations, renewals, speed upgrades, and bulk operations effortlessly.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#e0f2fe;"><i class="bx bx-line-chart" style="color:#0284c7;"></i></div>
                    <h6 class="fw-bold mb-2">Revenue Reports</h6>
                    <p class="text-muted small mb-0">Daily, weekly, and monthly revenue analytics. Track sales by package, payment trends, and growth metrics with visual charts.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#fff7ed;"><i class="bx bx-bot" style="color:#ea580c;"></i></div>
                    <h6 class="fw-bold mb-2">AI Assistant (OxBot)</h6>
                    <p class="text-muted small mb-0">Built-in AI chatbot trained on ISP knowledge. Helps customers troubleshoot connectivity issues and navigate the portal 24/7.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Packages Section --}}
@if(count($packages) > 0)
<div class="packages-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title mb-1">Internet Packages</h2>
            <p class="section-subtitle">Choose the plan that suits your needs</p>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach($packages as $package)
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="package-card">
                    <h6 class="fw-bold mb-3">{{ $package->name }}</h6>
                    <div class="package-price mb-1">
                        {{ config('settings.system_general.currency_symbol', 'KSh') }}&nbsp;{{ number_format($package->price) }}
                    </div>
                    <div class="text-muted small mb-4">/{{ $package->valid }}</div>
                    <hr>
                    <div class="text-muted small mb-1">
                        <i class="bx bx-tachometer me-1 text-primary"></i>
                        <strong>Speed:</strong> {{ $package->profile }}
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm w-100">
                            Get Started
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Contact Section --}}
<div class="contact-section">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-md-6">
                <h2 class="section-title mb-2">Get in Touch</h2>
                <p class="text-muted mb-4">Have questions? We're here to help. Contact us through any of the channels below.</p>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:42px;height:42px;background:#ede9fe;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bx bxs-phone" style="color:#7c3aed;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Phone</div>
                            <a href="tel:{{ config('settings.system_general.contact_no', '') }}" class="text-muted small text-decoration-none">
                                {{ config('settings.system_general.contact_no', '+254 700 000 000') }}
                            </a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:42px;height:42px;background:#dcfce7;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bx bx-envelope" style="color:#16a34a;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Email</div>
                            <a href="mailto:{{ config('settings.system_general.contact_email', '') }}" class="text-muted small text-decoration-none">
                                {{ config('settings.system_general.contact_email', 'support@oxnet.co.ke') }}
                            </a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:42px;height:42px;background:#fef3c7;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bx bx-map-pin" style="color:#d97706;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Address</div>
                            <span class="text-muted small">
                                {{ config('settings.system_general.location', 'Nairobi, Kenya') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:28px;color:#fff;text-align:center;">
                        <div style="font-size:3rem;margin-bottom:8px;">🚀</div>
                        <h5 class="fw-bold mb-2">Ready to get started?</h5>
                        <p class="mb-0" style="opacity:.88;font-size:.9rem;">
                            Sign in to your portal below or contact us to set up your ISP account.
                        </p>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.login') }}" class="btn btn-primary">
                                <i class="bx bx-shield-quarter me-2"></i> ISP Admin Login
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-user me-2"></i> Customer Login
                            </a>
                            <a href="{{ route('seller.login') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-store me-2"></i> Seller / Agent Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

