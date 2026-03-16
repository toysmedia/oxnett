@extends('admin.layouts.app')
@section('title', 'Router: ' . $router->name)

@section('content')
<div class="row">
    <div class="col-sm-12 mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Router: {{ $router->name }}</h5>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.isp.routers.script', $router) }}" class="btn btn-warning" target="_blank">
                    <i class="bx bx-code-alt me-1"></i> Generate Script
                </a>
                <a href="{{ route('admin.isp.routers.hotspot_files', $router) }}" class="btn btn-secondary">
                    <i class="bx bx-download me-1"></i> Hotspot Files
                </a>
                <button type="button" class="btn btn-success" onclick="testConnection()">
                    <i class="bx bx-broadcast me-1"></i> Test Connection
                </button>
                <a href="{{ route('admin.isp.routers.edit', $router) }}" class="btn btn-primary">
                    <i class="bx bx-edit me-1"></i> Edit
                </a>
                <a href="{{ route('admin.isp.routers.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    {{-- Router Details --}}
    <div class="col-sm-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Basic Information</h6>
                @if($router->is_active)
                    <span class="badge bg-success">Active</span>
                @else
                    <span class="badge bg-secondary">Inactive</span>
                @endif
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-muted" width="40%">Name</th>
                        <td>{{ $router->name }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">WAN IP</th>
                        <td><code>{{ $router->wan_ip }}</code></td>
                    </tr>
                    <tr>
                        <th class="text-muted">RADIUS Secret</th>
                        <td>
                            <span id="secretMask">••••••••</span>
                            <button class="btn btn-sm btn-link p-0 ms-2" onclick="toggleSecret()">Show</button>
                            <span id="secretValue" class="d-none"><code>{{ $router->radius_secret }}</code></span>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Billing Domain</th>
                        <td>{{ $router->billing_domain ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Created</th>
                        <td>{{ $router->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Updated</th>
                        <td>{{ $router->updated_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Network Config --}}
    <div class="col-sm-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Network Configuration</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-muted" width="40%">WAN Interface</th>
                        <td><code>{{ $router->wan_interface }}</code></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Customer Interface</th>
                        <td><code>{{ $router->customer_interface }}</code></td>
                    </tr>
                    <tr>
                        <th class="text-muted">PPPoE Pool</th>
                        <td><code>{{ $router->pppoe_pool_range }}</code></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Hotspot Pool</th>
                        <td><code>{{ $router->hotspot_pool_range }}</code></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    @if($router->notes)
    <div class="col-sm-12 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Notes</h6></div>
            <div class="card-body">{{ $router->notes }}</div>
        </div>
    </div>
    @endif

    {{-- Danger Zone --}}
    <div class="col-sm-12">
        <div class="card border-danger">
            <div class="card-header text-danger"><h6 class="mb-0"><i class="bx bx-error-circle me-1"></i> Danger Zone</h6></div>
            <div class="card-body">
                <p class="mb-3 text-muted">Permanently delete this router. All associated subscribers and sessions must be removed first.</p>
                <form action="{{ route('admin.isp.routers.destroy', $router) }}" method="POST"
                      onsubmit="return confirm('Permanently delete router &quot;{{ addslashes($router->name) }}&quot;? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-trash me-1"></i> Delete Router
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Test Connection Modal --}}
<div class="modal fade" id="testConnModal" tabindex="-1" aria-labelledby="testConnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testConnModalLabel">Test Connection — {{ $router->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="testConnBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleSecret() {
    const mask  = document.getElementById('secretMask');
    const value = document.getElementById('secretValue');
    const btn   = event.target;
    if (value.classList.contains('d-none')) {
        mask.classList.add('d-none');
        value.classList.remove('d-none');
        btn.textContent = 'Hide';
    } else {
        mask.classList.remove('d-none');
        value.classList.add('d-none');
        btn.textContent = 'Show';
    }
}

function testConnection() {
    $('#testConnBody').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Testing...</span></div><p class="mt-2 text-muted">Connecting to router…</p></div>');
    var modal = new bootstrap.Modal(document.getElementById('testConnModal'));
    modal.show();

    $.ajax({
        url: '{{ route('admin.isp.routers.test_connection', $router) }}',
        type: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function(res) {
            var e = function(s) {
                return $('<div>').text(s != null ? String(s) : '').html();
            };
            var html = '<ul class="list-group">';
            html += '<li class="list-group-item d-flex justify-content-between align-items-center">'
                  + '<span><i class="bx bx-wifi me-2"></i>API Reachable</span>'
                  + (res.api_reachable
                      ? '<span class="badge bg-success">Yes</span>'
                      : '<span class="badge bg-danger">No</span>')
                  + '</li>';
            html += '<li class="list-group-item d-flex justify-content-between align-items-center">'
                  + '<span><i class="bx bx-server me-2"></i>RADIUS Configured</span>'
                  + (res.radius_configured
                      ? '<span class="badge bg-success">Yes</span>'
                      : '<span class="badge bg-warning text-dark">Not in NAS table</span>')
                  + '</li>';
            if (res.router_identity) {
                html += '<li class="list-group-item d-flex justify-content-between align-items-center">'
                      + '<span><i class="bx bx-chip me-2"></i>Board</span>'
                      + '<span class="text-muted">' + e(res.router_identity) + '</span></li>';
            }
            if (res.version) {
                html += '<li class="list-group-item d-flex justify-content-between align-items-center">'
                      + '<span><i class="bx bx-code-alt me-2"></i>RouterOS</span>'
                      + '<span class="text-muted">' + e(res.version) + '</span></li>';
            }
            if (res.uptime) {
                html += '<li class="list-group-item d-flex justify-content-between align-items-center">'
                      + '<span><i class="bx bx-time me-2"></i>Uptime</span>'
                      + '<span class="text-muted">' + e(res.uptime) + '</span></li>';
            }
            html += '</ul>';
            if (res.error) {
                html += '<div class="alert alert-warning mt-3 mb-0"><i class="bx bx-info-circle me-1"></i>' + e(res.error) + '</div>';
            }
            $('#testConnBody').html(html);
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unknown error';
            $('#testConnBody').html('<div class="alert alert-danger mb-0"><i class="bx bx-error me-1"></i>Request failed: ' + $('<div>').text(msg).html() + '</div>');
        }
    });
}
</script>
@endpush