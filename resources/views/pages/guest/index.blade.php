@extends("layouts.public")
@section('title', 'Home')

@push('styles')
<style>
/* =============================================
   OXNET – Premium Guest Landing Page Styles
   ============================================= */

/* ---------- CSS Custom Properties ---------- */
:root {
    --ox-primary:   #4f46e5;
    --ox-primary-d: #3730a3;
    --ox-purple:    #7c3aed;
    --ox-purple-l:  #a78bfa;
    --ox-dark:      #0f0e2a;
    --ox-dark-2:    #1e1b4b;
    --ox-dark-3:    #312e81;
    --ox-surface:   #f8f7ff;
    --ox-border:    #e5e7eb;
    --ox-text:      #374151;
    --ox-muted:     #6b7280;
    --ox-radius:    14px;
    --ox-radius-lg: 20px;
    --ox-shadow-sm: 0 2px 12px rgba(79,70,229,.08);
    --ox-shadow:    0 8px 32px rgba(79,70,229,.14);
    --ox-shadow-lg: 0 20px 60px rgba(79,70,229,.20);
    --ox-glass:     rgba(255,255,255,.12);
    --ox-glass-b:   rgba(255,255,255,.20);
    --ox-transition: .25s cubic-bezier(.4,0,.2,1);
}

/* ---------- Scroll-reveal base ---------- */
.ox-reveal {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity .6s ease, transform .6s ease;
}
.ox-reveal.ox-visible {
    opacity: 1;
    transform: none;
}
.ox-reveal-delay-1 { transition-delay: .1s; }
.ox-reveal-delay-2 { transition-delay: .2s; }
.ox-reveal-delay-3 { transition-delay: .3s; }
.ox-reveal-delay-4 { transition-delay: .4s; }
.ox-reveal-delay-5 { transition-delay: .5s; }
.ox-reveal-delay-6 { transition-delay: .6s; }

/* ---------- Floating animation ---------- */
@keyframes oxFloat {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    33%       { transform: translateY(-18px) rotate(3deg); }
    66%       { transform: translateY(-8px) rotate(-2deg); }
}
@keyframes oxPulse {
    0%, 100% { opacity: .4; transform: scale(1); }
    50%       { opacity: .7; transform: scale(1.08); }
}
@keyframes oxSpin {
    to { transform: rotate(360deg); }
}
@keyframes oxCounter {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: none; }
}

/* =============================================
   HERO SECTION
   ============================================= */
.ox-hero {
    background: linear-gradient(135deg, var(--ox-dark) 0%, var(--ox-dark-2) 35%, var(--ox-dark-3) 65%, var(--ox-primary) 85%, var(--ox-purple) 100%);
    color: #fff;
    padding: 110px 0 80px;
    position: relative;
    overflow: hidden;
    min-height: 92vh;
    display: flex;
    align-items: center;
}

/* Dot-grid pattern */
.ox-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 28px 28px;
    pointer-events: none;
}

/* Glowing orbs */
.ox-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
}
.ox-orb-1 {
    width: 520px; height: 520px;
    background: radial-gradient(circle, rgba(124,58,237,.35), transparent 70%);
    top: -140px; right: -100px;
    animation: oxPulse 7s ease-in-out infinite;
}
.ox-orb-2 {
    width: 380px; height: 380px;
    background: radial-gradient(circle, rgba(79,70,229,.28), transparent 70%);
    bottom: -80px; left: -60px;
    animation: oxPulse 9s ease-in-out infinite 2s;
}
.ox-orb-3 {
    width: 180px; height: 180px;
    background: radial-gradient(circle, rgba(167,139,250,.4), transparent 70%);
    top: 30%; left: 42%;
    animation: oxPulse 5s ease-in-out infinite 1s;
}

/* Floating shapes */
.ox-shape {
    position: absolute;
    pointer-events: none;
    opacity: .18;
}
.ox-shape-1 {
    width: 64px; height: 64px;
    border: 2px solid #a78bfa;
    border-radius: 12px;
    top: 18%; right: 15%;
    animation: oxFloat 6s ease-in-out infinite;
}
.ox-shape-2 {
    width: 44px; height: 44px;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    border-radius: 50%;
    top: 60%; right: 28%;
    animation: oxFloat 8s ease-in-out infinite 1.5s;
}
.ox-shape-3 {
    width: 32px; height: 32px;
    border: 2px solid #7c3aed;
    border-radius: 50%;
    bottom: 20%; left: 20%;
    animation: oxFloat 7s ease-in-out infinite 3s;
}

/* Hero content */
.ox-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--ox-glass);
    backdrop-filter: blur(12px);
    border: 1px solid var(--ox-glass-b);
    border-radius: 50px;
    padding: 6px 18px;
    font-size: .78rem;
    font-weight: 600;
    letter-spacing: .6px;
    text-transform: uppercase;
    margin-bottom: 24px;
    color: #c4b5fd;
}
.ox-hero-badge .badge-dot {
    width: 7px; height: 7px;
    background: #4ade80;
    border-radius: 50%;
    animation: oxPulse 2s ease-in-out infinite;
}

.ox-hero-title {
    font-size: clamp(2rem, 5vw, 3.4rem);
    font-weight: 900;
    line-height: 1.12;
    margin-bottom: 22px;
    letter-spacing: -.5px;
}
.ox-hero-title .ox-gradient-text {
    background: linear-gradient(90deg, #a78bfa, #c084fc, #f0abfc);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ox-hero-subtitle {
    font-size: 1.08rem;
    line-height: 1.75;
    color: rgba(255,255,255,.78);
    margin-bottom: 36px;
    max-width: 540px;
}

.ox-hero-ctas { display: flex; flex-wrap: wrap; gap: 14px; }

.ox-btn-hero-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    color: var(--ox-dark-2);
    border-radius: 50px;
    padding: 14px 28px;
    font-weight: 700;
    font-size: .92rem;
    text-decoration: none;
    border: none;
    transition: var(--ox-transition);
    box-shadow: 0 4px 20px rgba(0,0,0,.25);
}
.ox-btn-hero-primary:hover {
    background: #f0f0ff;
    color: var(--ox-primary);
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0,0,0,.3);
}

.ox-btn-hero-outline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--ox-glass);
    backdrop-filter: blur(12px);
    color: #fff;
    border: 1px solid var(--ox-glass-b);
    border-radius: 50px;
    padding: 14px 28px;
    font-weight: 600;
    font-size: .92rem;
    text-decoration: none;
    transition: var(--ox-transition);
}
.ox-btn-hero-outline:hover {
    background: rgba(255,255,255,.22);
    color: #fff;
    transform: translateY(-2px);
}

/* Hero visual card */
.ox-hero-visual {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ox-hero-glass-card {
    background: var(--ox-glass);
    backdrop-filter: blur(20px);
    border: 1px solid var(--ox-glass-b);
    border-radius: var(--ox-radius-lg);
    padding: 32px;
    text-align: center;
    animation: oxFloat 5s ease-in-out infinite;
    box-shadow: 0 24px 80px rgba(0,0,0,.3);
    max-width: 300px;
    width: 100%;
}
.ox-hero-glass-card .ox-logo-wrapper img { max-height: 80px; filter: drop-shadow(0 4px 16px rgba(255,255,255,.2)); }
.ox-hero-glass-card .ox-logo-icon { font-size: 5rem; opacity: .85; }
.ox-hero-glass-card .ox-hero-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-top: 16px;
    color: #fff;
}
.ox-hero-glass-card .ox-hero-card-sub {
    font-size: .8rem;
    color: rgba(255,255,255,.65);
    margin-top: 6px;
}

.ox-hero-stat-pill {
    position: absolute;
    background: rgba(255,255,255,.95);
    border-radius: 50px;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .78rem;
    font-weight: 700;
    color: var(--ox-dark-2);
    box-shadow: 0 4px 20px rgba(0,0,0,.18);
}
.ox-hero-stat-pill .pill-icon {
    width: 28px; height: 28px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
    flex-shrink: 0;
}
.ox-pill-1 { bottom: -16px; left: -20px; }
.ox-pill-2 { top: -16px; right: -20px; }

/* =============================================
   STATS / TRUST SECTION
   ============================================= */
.ox-stats-section {
    background: linear-gradient(135deg, var(--ox-dark-2), var(--ox-dark-3));
    padding: 60px 0;
    position: relative;
    overflow: hidden;
}
.ox-stats-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M20 20.5V18H0v5h5v5H0v5h20v-9.5zm-2 4.5h-4v-4h4v4zm10-4h-4v4h4v-4zm2-2h-4V14h4v5zm6 4h-4v-4h4v4z'/%3E%3C/g%3E%3C/svg%3E");
}
.ox-stat-item { text-align: center; }
.ox-stat-number {
    font-size: clamp(1.9rem, 4vw, 2.8rem);
    font-weight: 900;
    color: #fff;
    line-height: 1;
    margin-bottom: 6px;
    background: linear-gradient(90deg, #a78bfa, #f0abfc);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ox-stat-label {
    font-size: .85rem;
    color: rgba(255,255,255,.6);
    font-weight: 500;
    letter-spacing: .3px;
}
.ox-stat-divider {
    width: 1px;
    background: rgba(255,255,255,.15);
    height: 50px;
    align-self: center;
}

/* =============================================
   QUICK ACCESS PORTALS
   ============================================= */
.ox-portals-section {
    padding: 80px 0;
    background: var(--ox-surface);
    position: relative;
}
.ox-section-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ede9fe;
    color: var(--ox-purple);
    border-radius: 50px;
    padding: 4px 14px;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    margin-bottom: 14px;
}
.ox-section-title {
    font-size: clamp(1.5rem, 3.5vw, 2.1rem);
    font-weight: 800;
    color: var(--ox-dark-2);
    letter-spacing: -.3px;
    line-height: 1.25;
}
.ox-section-sub {
    font-size: 1rem;
    color: var(--ox-muted);
    line-height: 1.65;
}

.ox-portal-card {
    background: #fff;
    border-radius: var(--ox-radius);
    padding: 32px 20px 28px;
    text-align: center;
    border: 2px solid var(--ox-border);
    transition: var(--ox-transition);
    height: 100%;
    text-decoration: none;
    display: block;
    color: var(--ox-text);
    position: relative;
    overflow: hidden;
}
.ox-portal-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--ox-primary), var(--ox-purple));
    opacity: 0;
    transition: var(--ox-transition);
}
.ox-portal-card:hover {
    border-color: var(--ox-primary);
    box-shadow: var(--ox-shadow);
    transform: translateY(-6px);
    color: var(--ox-text);
}
.ox-portal-card:hover::before { opacity: 1; }
.ox-portal-card:hover .ox-portal-icon { transform: scale(1.1) rotate(-5deg); }

.ox-portal-icon-wrap {
    width: 64px; height: 64px;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    transition: var(--ox-transition);
}
.ox-portal-icon {
    font-size: 1.7rem;
    transition: transform var(--ox-transition);
}
.ox-portal-title { font-weight: 700; font-size: .95rem; margin-bottom: 4px; }
.ox-portal-sub { font-size: .78rem; color: var(--ox-muted); }
.ox-portal-arrow {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .75rem;
    font-weight: 600;
    color: var(--ox-primary);
    margin-top: 14px;
    opacity: 0;
    transform: translateX(-6px);
    transition: var(--ox-transition);
}
.ox-portal-card:hover .ox-portal-arrow { opacity: 1; transform: none; }

/* =============================================
   FEATURES SECTION
   ============================================= */
.ox-features-section {
    padding: 88px 0;
    background: #fff;
    position: relative;
    overflow: hidden;
}
.ox-features-section::after {
    content: '';
    position: absolute;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(79,70,229,.05), transparent 70%);
    top: 50%; right: -200px;
    transform: translateY(-50%);
    pointer-events: none;
}

.ox-feature-card {
    background: #fff;
    border-radius: var(--ox-radius);
    padding: 28px 24px;
    border: 1px solid var(--ox-border);
    height: 100%;
    transition: var(--ox-transition);
    position: relative;
    overflow: hidden;
}
.ox-feature-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(79,70,229,.03), rgba(124,58,237,.03));
    opacity: 0;
    transition: var(--ox-transition);
}
.ox-feature-card:hover {
    border-color: #c7d2fe;
    box-shadow: var(--ox-shadow);
    transform: translateY(-4px);
}
.ox-feature-card:hover::before { opacity: 1; }
.ox-feature-card:hover .ox-feature-icon-wrap { transform: rotate(-8deg) scale(1.1); }

.ox-feature-icon-wrap {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 18px;
    transition: transform var(--ox-transition);
}
.ox-feature-card h6 { font-size: .95rem; font-weight: 700; color: var(--ox-dark-2); margin-bottom: 10px; }
.ox-feature-card p  { font-size: .84rem; color: var(--ox-muted); line-height: 1.65; margin: 0; }

/* =============================================
   PACKAGES / PRICING SECTION
   ============================================= */
.ox-pricing-section {
    padding: 88px 0;
    background: var(--ox-surface);
    position: relative;
    overflow: hidden;
}

.ox-pricing-card {
    background: #fff;
    border-radius: var(--ox-radius-lg);
    padding: 36px 28px 32px;
    text-align: center;
    border: 2px solid var(--ox-border);
    transition: var(--ox-transition);
    height: 100%;
    position: relative;
    overflow: hidden;
}
.ox-pricing-card:hover {
    border-color: var(--ox-primary);
    box-shadow: var(--ox-shadow-lg);
    transform: translateY(-8px);
}
.ox-pricing-card.ox-popular {
    border-color: var(--ox-primary);
    box-shadow: var(--ox-shadow);
}
.ox-popular-badge {
    position: absolute;
    top: 16px; right: 16px;
    background: linear-gradient(90deg, var(--ox-primary), var(--ox-purple));
    color: #fff;
    border-radius: 50px;
    padding: 4px 12px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
}
.ox-pricing-name { font-size: 1rem; font-weight: 700; color: var(--ox-dark-2); margin-bottom: 16px; }
.ox-pricing-amount {
    font-size: clamp(1.8rem, 4vw, 2.4rem);
    font-weight: 900;
    color: var(--ox-primary);
    line-height: 1;
    margin-bottom: 4px;
}
.ox-pricing-period { font-size: .82rem; color: var(--ox-muted); margin-bottom: 20px; }
.ox-pricing-divider { border-color: var(--ox-border); margin: 20px 0; }
.ox-pricing-feature { font-size: .84rem; color: var(--ox-text); display: flex; align-items: center; gap: 8px; justify-content: center; }
.ox-pricing-cta {
    display: block;
    margin-top: 24px;
    padding: 11px 20px;
    border-radius: 50px;
    font-weight: 700;
    font-size: .88rem;
    text-decoration: none;
    border: 2px solid var(--ox-primary);
    color: var(--ox-primary);
    transition: var(--ox-transition);
}
.ox-pricing-cta:hover,
.ox-pricing-card.ox-popular .ox-pricing-cta {
    background: var(--ox-primary);
    color: #fff;
}
.ox-pricing-card.ox-popular .ox-pricing-cta:hover {
    background: var(--ox-primary-d);
    border-color: var(--ox-primary-d);
}

/* =============================================
   CONTACT SECTION
   ============================================= */
.ox-contact-section {
    padding: 88px 0;
    background: #fff;
}
.ox-contact-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    border-radius: var(--ox-radius);
    border: 1px solid var(--ox-border);
    background: #fff;
    transition: var(--ox-transition);
    margin-bottom: 16px;
}
.ox-contact-item:hover { border-color: #c7d2fe; box-shadow: var(--ox-shadow-sm); }
.ox-contact-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.ox-contact-label { font-size: .75rem; font-weight: 600; color: var(--ox-muted); text-transform: uppercase; letter-spacing: .5px; }
.ox-contact-value { font-size: .9rem; color: var(--ox-text); text-decoration: none; font-weight: 500; }
.ox-contact-value:hover { color: var(--ox-primary); }

.ox-cta-card {
    background: linear-gradient(135deg, var(--ox-dark-2) 0%, var(--ox-dark-3) 50%, var(--ox-primary) 100%);
    border-radius: var(--ox-radius-lg);
    padding: 40px 32px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.ox-cta-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 20px 20px;
    pointer-events: none;
}
.ox-cta-card .ox-rocket { font-size: 3.5rem; margin-bottom: 16px; display: block; animation: oxFloat 4s ease-in-out infinite; }
.ox-cta-card h4 { font-weight: 800; font-size: 1.3rem; margin-bottom: 10px; }
.ox-cta-card p { opacity: .8; font-size: .9rem; margin-bottom: 24px; line-height: 1.6; }

.ox-cta-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 50px;
    font-weight: 700;
    font-size: .88rem;
    text-decoration: none;
    transition: var(--ox-transition);
    border: 1.5px solid transparent;
    width: 100%;
    justify-content: center;
    margin-bottom: 10px;
}
.ox-cta-btn-primary {
    background: #fff;
    color: var(--ox-dark-2);
    border-color: #fff;
}
.ox-cta-btn-primary:hover { background: #f0f0ff; color: var(--ox-primary); }
.ox-cta-btn-outline {
    background: var(--ox-glass);
    color: #fff;
    border-color: var(--ox-glass-b);
    backdrop-filter: blur(8px);
}
.ox-cta-btn-outline:hover { background: rgba(255,255,255,.22); color: #fff; }

/* =============================================
   RESPONSIVE OVERRIDES
   ============================================= */
@media (max-width: 991.98px) {
    .ox-hero { padding: 100px 0 64px; min-height: auto; }
    .ox-hero-visual { margin-top: 40px; }
    .ox-pill-1, .ox-pill-2 { display: none; }
    .ox-stat-divider { display: none; }
}
@media (max-width: 767.98px) {
    .ox-hero { padding: 90px 0 52px; }
    .ox-hero-ctas { justify-content: flex-start; }
    .ox-portals-section, .ox-features-section, .ox-pricing-section, .ox-contact-section { padding: 60px 0; }
    .ox-stats-section { padding: 44px 0; }
    .ox-stat-item { margin-bottom: 28px; }
    .ox-cta-card { padding: 28px 20px; }
}
@media (max-width: 575.98px) {
    .ox-hero-ctas { flex-direction: column; }
    .ox-btn-hero-primary, .ox-btn-hero-outline { width: 100%; justify-content: center; }
}
</style>
@endpush

@section('content')

{{-- ╔══════════════════════════════════════════╗
     ║  HERO SECTION                            ║
     ╚══════════════════════════════════════════╝ --}}
<section class="ox-hero">
    <div class="ox-orb ox-orb-1"></div>
    <div class="ox-orb ox-orb-2"></div>
    <div class="ox-orb ox-orb-3"></div>
    <div class="ox-shape ox-shape-1"></div>
    <div class="ox-shape ox-shape-2"></div>
    <div class="ox-shape ox-shape-3"></div>

    <div class="container position-relative">
        <div class="row align-items-center gy-5">

            {{-- Left: Copy --}}
            <div class="col-lg-7">
                <div class="ox-hero-badge ox-reveal">
                    <span class="badge-dot"></span>
                    Kenyan ISP Management SaaS
                </div>
                <h1 class="ox-hero-title ox-reveal ox-reveal-delay-1">
                    Manage Your ISP<br>
                    <span class="ox-gradient-text">Smarter &amp; Faster</span>
                </h1>
                <p class="ox-hero-subtitle ox-reveal ox-reveal-delay-2">
                    OxNet is a complete multi-tenant ISP management platform built for Kenyan internet service providers.
                    Automate PPPoE billing, MikroTik management, M-Pesa payments, and subscriber self-service — all in one place.
                </p>
                <div class="ox-hero-ctas ox-reveal ox-reveal-delay-3">
                    <a href="{{ route('admin.login') }}" class="ox-btn-hero-primary">
                        <i class="bx bx-shield-quarter"></i> Admin Login
                    </a>
                    <a href="{{ route('login') }}" class="ox-btn-hero-outline">
                        <i class="bx bx-user"></i> Customer Portal
                    </a>
                </div>
            </div>

            {{-- Right: Visual card --}}
            <div class="col-lg-5 d-flex justify-content-center ox-reveal ox-reveal-delay-2">
                <div class="ox-hero-visual">
                    <div class="ox-hero-glass-card">
                        <div class="ox-logo-wrapper">
                            @if(config('settings.system_general.logo_path'))
                                <img src="{{ asset('storage/' . config('settings.system_general.logo_path')) }}"
                                     alt="{{ config('settings.system_general.title', 'OxNet') }}"
                                     class="img-fluid">
                            @else
                                <div class="ox-logo-icon">📡</div>
                            @endif
                        </div>
                        <div class="ox-hero-card-title">{{ config('settings.system_general.title', 'OxNet') }}</div>
                        <div class="ox-hero-card-sub">ISP Management Platform</div>
                    </div>
                    <div class="ox-hero-stat-pill ox-pill-1">
                        <div class="pill-icon" style="background:#dcfce7;"><i class="bx bx-check-shield" style="color:#16a34a;"></i></div>
                        <span>99.9% Uptime</span>
                    </div>
                    <div class="ox-hero-stat-pill ox-pill-2">
                        <div class="pill-icon" style="background:#ede9fe;"><i class="bx bx-group" style="color:#7c3aed;"></i></div>
                        <span>500+ ISPs</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ╔══════════════════════════════════════════╗
     ║  STATS / TRUST BADGES                   ║
     ╚══════════════════════════════════════════╝ --}}
<section class="ox-stats-section">
    <div class="container position-relative">
        <div class="row g-4 align-items-center justify-content-center">
            <div class="col-6 col-sm-3 ox-reveal">
                <div class="ox-stat-item">
                    <div class="ox-stat-number" data-target="500">500+</div>
                    <div class="ox-stat-label">ISPs Managed</div>
                </div>
            </div>
            <div class="d-none d-sm-flex col-sm-1 justify-content-center">
                <div class="ox-stat-divider"></div>
            </div>
            <div class="col-6 col-sm-3 ox-reveal ox-reveal-delay-1">
                <div class="ox-stat-item">
                    <div class="ox-stat-number">99.9%</div>
                    <div class="ox-stat-label">Uptime SLA</div>
                </div>
            </div>
            <div class="d-none d-sm-flex col-sm-1 justify-content-center">
                <div class="ox-stat-divider"></div>
            </div>
            <div class="col-6 col-sm-3 ox-reveal ox-reveal-delay-2">
                <div class="ox-stat-item">
                    <div class="ox-stat-number">24/7</div>
                    <div class="ox-stat-label">Expert Support</div>
                </div>
            </div>
            <div class="d-none d-sm-flex col-sm-1 justify-content-center">
                <div class="ox-stat-divider"></div>
            </div>
            <div class="col-6 col-sm-3 ox-reveal ox-reveal-delay-3">
                <div class="ox-stat-item">
                    <div class="ox-stat-number">M-Pesa</div>
                    <div class="ox-stat-label">Auto-reconciled</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ╔══════════════════════════════════════════╗
     ║  QUICK ACCESS PORTALS                   ║
     ╚══════════════════════════════════════════╝ --}}
<section class="ox-portals-section">
    <div class="container">
        <div class="text-center mb-5 ox-reveal">
            <div class="ox-section-tag"><i class="bx bx-log-in-circle"></i> Portals</div>
            <h2 class="ox-section-title mb-2">Quick Access</h2>
            <p class="ox-section-sub">Choose your portal to get started instantly</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-md-3 col-lg-2 ox-reveal ox-reveal-delay-1">
                <a href="{{ route('login') }}" class="ox-portal-card">
                    <div class="ox-portal-icon-wrap" style="background:#ede9fe;">
                        <i class="bx bx-user ox-portal-icon" style="color:#7c3aed;"></i>
                    </div>
                    <div class="ox-portal-title">Customer</div>
                    <div class="ox-portal-sub">Self-service portal</div>
                    <div class="ox-portal-arrow"><i class="bx bx-right-arrow-alt"></i> Enter</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 ox-reveal ox-reveal-delay-2">
                <a href="{{ route('seller.login') }}" class="ox-portal-card">
                    <div class="ox-portal-icon-wrap" style="background:#fef3c7;">
                        <i class="bx bx-store ox-portal-icon" style="color:#d97706;"></i>
                    </div>
                    <div class="ox-portal-title">Seller / Agent</div>
                    <div class="ox-portal-sub">Reseller dashboard</div>
                    <div class="ox-portal-arrow"><i class="bx bx-right-arrow-alt"></i> Enter</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 ox-reveal ox-reveal-delay-3">
                <a href="{{ route('admin.login') }}" class="ox-portal-card">
                    <div class="ox-portal-icon-wrap" style="background:#e0e7ff;">
                        <i class="bx bx-shield-quarter ox-portal-icon" style="color:#4f46e5;"></i>
                    </div>
                    <div class="ox-portal-title">ISP Admin</div>
                    <div class="ox-portal-sub">Tenant management</div>
                    <div class="ox-portal-arrow"><i class="bx bx-right-arrow-alt"></i> Enter</div>
                </a>
            </div>
            @if(Route::has('community.login'))
            <div class="col-6 col-md-3 col-lg-2 ox-reveal ox-reveal-delay-4">
                <a href="{{ route('community.login') }}" class="ox-portal-card">
                    <div class="ox-portal-icon-wrap" style="background:#d1fae5;">
                        <i class="bx bx-group ox-portal-icon" style="color:#059669;"></i>
                    </div>
                    <div class="ox-portal-title">Community</div>
                    <div class="ox-portal-sub">Forum &amp; support</div>
                    <div class="ox-portal-arrow"><i class="bx bx-right-arrow-alt"></i> Enter</div>
                </a>
            </div>
            @endif
        </div>
    </div>
</section>

{{-- ╔══════════════════════════════════════════╗
     ║  FEATURES SECTION                       ║
     ╚══════════════════════════════════════════╝ --}}
<section class="ox-features-section">
    <div class="container position-relative">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6 ox-reveal">
                <div class="ox-section-tag"><i class="bx bx-layer"></i> Features</div>
                <h2 class="ox-section-title mb-0">Everything You Need<br>to Run Your ISP</h2>
            </div>
            <div class="col-lg-6 ox-reveal ox-reveal-delay-1">
                <p class="ox-section-sub mb-0 mt-3 mt-lg-0">
                    Powerful tools built specifically for Kenyan internet service providers — from billing to network management.
                </p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-sm-6 col-lg-4 ox-reveal ox-reveal-delay-1">
                <div class="ox-feature-card">
                    <div class="ox-feature-icon-wrap" style="background:#ede9fe;"><i class="bx bx-buildings" style="color:#7c3aed;"></i></div>
                    <h6>Multi-Tenant Platform</h6>
                    <p>Each ISP gets their own isolated environment with custom branding, settings, and subscriber database.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4 ox-reveal ox-reveal-delay-2">
                <div class="ox-feature-card">
                    <div class="ox-feature-icon-wrap" style="background:#fce7f3;"><i class="bx bx-router" style="color:#db2777;"></i></div>
                    <h6>MikroTik Integration</h6>
                    <p>Auto-configure PPPoE profiles, manage hotspot users, and monitor your MikroTik routers in real-time via RouterOS API.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4 ox-reveal ox-reveal-delay-3">
                <div class="ox-feature-card">
                    <div class="ox-feature-icon-wrap" style="background:#dcfce7;"><i class="bx bx-money" style="color:#16a34a;"></i></div>
                    <h6>M-Pesa STK Push</h6>
                    <p>Customers pay via M-Pesa STK Push. Payments are automatically reconciled and PPPoE accounts activated instantly.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4 ox-reveal ox-reveal-delay-1">
                <div class="ox-feature-card">
                    <div class="ox-feature-icon-wrap" style="background:#fef3c7;"><i class="bx bx-group" style="color:#d97706;"></i></div>
                    <h6>Subscriber Management</h6>
                    <p>Manage PPPoE and hotspot subscribers. Handle expirations, renewals, speed upgrades, and bulk operations effortlessly.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4 ox-reveal ox-reveal-delay-2">
                <div class="ox-feature-card">
                    <div class="ox-feature-icon-wrap" style="background:#e0f2fe;"><i class="bx bx-line-chart" style="color:#0284c7;"></i></div>
                    <h6>Revenue Reports</h6>
                    <p>Daily, weekly, and monthly revenue analytics. Track sales by package, payment trends, and growth metrics with visual charts.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4 ox-reveal ox-reveal-delay-3">
                <div class="ox-feature-card">
                    <div class="ox-feature-icon-wrap" style="background:#fff7ed;"><i class="bx bx-bot" style="color:#ea580c;"></i></div>
                    <h6>AI Assistant (OxBot)</h6>
                    <p>Built-in AI chatbot trained on ISP knowledge. Helps customers troubleshoot connectivity issues and navigate the portal 24/7.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ╔══════════════════════════════════════════╗
     ║  PACKAGES / PRICING SECTION             ║
     ╚══════════════════════════════════════════╝ --}}
@if(count($packages) > 0)
<section class="ox-pricing-section">
    <div class="container">
        <div class="text-center mb-5 ox-reveal">
            <div class="ox-section-tag"><i class="bx bx-package"></i> Pricing</div>
            <h2 class="ox-section-title mb-2">Internet Packages</h2>
            <p class="ox-section-sub">Choose the plan that perfectly suits your needs</p>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach($packages as $index => $package)
            <div class="col-sm-6 col-md-4 col-lg-3 ox-reveal ox-reveal-delay-{{ ($index % 4) + 1 }}">
                <div class="ox-pricing-card {{ $index === 1 ? 'ox-popular' : '' }}">
                    @if($index === 1)
                        <div class="ox-popular-badge">Popular</div>
                    @endif
                    <div class="ox-pricing-name">{{ $package->name }}</div>
                    <div class="ox-pricing-amount">
                        {{ config('settings.system_general.currency_symbol', 'KSh') }}&nbsp;{{ number_format($package->price) }}
                    </div>
                    <div class="ox-pricing-period">/{{ $package->valid }}</div>
                    <hr class="ox-pricing-divider">
                    <div class="ox-pricing-feature">
                        <i class="bx bx-tachometer" style="color:var(--ox-primary);font-size:1rem;"></i>
                        <span>{{ $package->profile }}</span>
                    </div>
                    <a href="{{ route('login') }}" class="ox-pricing-cta">Get Started</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ╔══════════════════════════════════════════╗
     ║  CONTACT SECTION                        ║
     ╚══════════════════════════════════════════╝ --}}
<section class="ox-contact-section">
    <div class="container">
        <div class="row g-5 align-items-start">

            {{-- Contact info --}}
            <div class="col-lg-6 ox-reveal">
                <div class="ox-section-tag"><i class="bx bx-support"></i> Contact</div>
                <h2 class="ox-section-title mb-3">Get in Touch</h2>
                <p class="ox-section-sub mb-4">Have questions? We're here to help. Reach us through any channel below.</p>

                <div class="ox-contact-item">
                    <div class="ox-contact-icon" style="background:#ede9fe;">
                        <i class="bx bxs-phone" style="color:#7c3aed;"></i>
                    </div>
                    <div>
                        <div class="ox-contact-label">Phone</div>
                        <a href="tel:{{ config('settings.system_general.contact_no', '') }}" class="ox-contact-value">
                            {{ config('settings.system_general.contact_no', '+254 700 000 000') }}
                        </a>
                    </div>
                </div>

                <div class="ox-contact-item">
                    <div class="ox-contact-icon" style="background:#dcfce7;">
                        <i class="bx bx-envelope" style="color:#16a34a;"></i>
                    </div>
                    <div>
                        <div class="ox-contact-label">Email</div>
                        <a href="mailto:{{ config('settings.system_general.contact_email', '') }}" class="ox-contact-value">
                            {{ config('settings.system_general.contact_email', 'support@oxnet.co.ke') }}
                        </a>
                    </div>
                </div>

                <div class="ox-contact-item">
                    <div class="ox-contact-icon" style="background:#fef3c7;">
                        <i class="bx bx-map-pin" style="color:#d97706;"></i>
                    </div>
                    <div>
                        <div class="ox-contact-label">Address</div>
                        <span class="ox-contact-value">
                            {{ config('settings.system_general.location', 'Nairobi, Kenya') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- CTA card --}}
            <div class="col-lg-6 ox-reveal ox-reveal-delay-2">
                <div class="ox-cta-card">
                    <span class="ox-rocket">🚀</span>
                    <h4>Ready to get started?</h4>
                    <p>Sign in to your portal below or contact us to set up your ISP account today.</p>
                    <a href="{{ route('admin.login') }}" class="ox-cta-btn ox-cta-btn-primary">
                        <i class="bx bx-shield-quarter"></i> ISP Admin Login
                    </a>
                    <a href="{{ route('login') }}" class="ox-cta-btn ox-cta-btn-outline">
                        <i class="bx bx-user"></i> Customer Login
                    </a>
                    <a href="{{ route('seller.login') }}" class="ox-cta-btn ox-cta-btn-outline">
                        <i class="bx bx-store"></i> Seller / Agent Login
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
(function () {
    // Scroll-reveal using IntersectionObserver
    var els = document.querySelectorAll('.ox-reveal');
    if (!('IntersectionObserver' in window)) {
        els.forEach(function (el) { el.classList.add('ox-visible'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('ox-visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });
    els.forEach(function (el) { io.observe(el); });
})();
</script>
@endpush

