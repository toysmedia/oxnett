@extends('layouts.super-admin')
@section('title', 'Tenant Map')
@section('page-title', 'Tenant Map')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
<style>
    #tenantMap { height: calc(100vh - 220px); min-height: 480px; border-radius: 0.5rem; }
    .leaflet-popup-content { font-size: 13px; min-width: 200px; }
    .tenant-popup-status { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: 11px; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Tenant Map</h5>
        <small class="text-muted">{{ $tenants->count() }} tenant(s) plotted with coordinates</small>
    </div>
    <a href="{{ route('super-admin.tenants.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-list-ul me-1"></i>View List
    </a>
</div>

@if($tenants->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-geo-alt fs-1 d-block mb-3 opacity-25"></i>
        <p class="mb-1 fw-semibold">No tenants with coordinates yet.</p>
        <p class="small mb-0">Set <code>lat</code> and <code>lng</code> when editing a tenant to display them on the map.</p>
        <a href="{{ route('super-admin.tenants.index') }}" class="btn btn-primary btn-sm mt-3">Manage Tenants</a>
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-body p-2">
        <div id="tenantMap"></div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WPeE=" crossorigin="anonymous"></script>
<script>
(function () {
    const tenants = @json($tenants);
    const tenantBaseUrl = '{{ route('super-admin.tenants.index') }}'.replace(/\/+$/, '');

    if (!tenants.length) return;

    const map = L.map('tenantMap', { zoomControl: true });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);

    const markers = [];

    tenants.forEach(function (tenant) {
        const lat = parseFloat(tenant.lat);
        const lng = parseFloat(tenant.lng);
        if (isNaN(lat) || isNaN(lng)) return;

        const statusColor = {
            active: '#198754',
            suspended: '#ffc107',
            expired: '#dc3545',
            trial: '#0dcaf0'
        }[tenant.status] || '#6c757d';

        const icon = L.divIcon({
            className: '',
            html: `<div style="
                width:16px; height:16px;
                background:${statusColor};
                border-radius:50%;
                border:2px solid #fff;
                box-shadow:0 1px 4px rgba(0,0,0,.4);
            "></div>`,
            iconSize: [16, 16],
            iconAnchor: [8, 8],
            popupAnchor: [0, -10]
        });

        const popupHtml = `
            <strong>${tenant.name}</strong><br>
            <code>${tenant.subdomain}</code><br>
            <span class="tenant-popup-status" style="background:${statusColor};color:#fff;">${tenant.status}</span>
            <br><a href="${tenantBaseUrl}/${tenant.id}" target="_blank" style="font-size:12px;">View Details →</a>
        `;

        const marker = L.marker([lat, lng], { icon })
            .bindPopup(popupHtml, { maxWidth: 260 })
            .addTo(map);

        markers.push(marker);
    });

    if (markers.length) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.2));
        if (markers.length === 1) map.setZoom(10);
    }

    // Legend
    const legend = L.control({ position: 'bottomright' });
    legend.onAdd = function () {
        const div = L.DomUtil.create('div', '');
        div.style.cssText = 'background:#fff;padding:8px 12px;border-radius:8px;box-shadow:0 1px 6px rgba(0,0,0,.2);font-size:12px;line-height:1.8;';
        div.innerHTML = `
            <div style="font-weight:600;margin-bottom:4px;">Status</div>
            <div><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#198754;margin-right:6px;"></span>Active</div>
            <div><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#ffc107;margin-right:6px;"></span>Suspended</div>
            <div><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#dc3545;margin-right:6px;"></span>Expired</div>
            <div><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#0dcaf0;margin-right:6px;"></span>Trial</div>
        `;
        return div;
    };
    legend.addTo(map);
})();
</script>
@endpush
