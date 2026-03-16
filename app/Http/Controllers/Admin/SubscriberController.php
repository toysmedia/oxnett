<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\IspPackage;
use App\Models\Router;
use App\Models\AuditLog;
use App\Services\RadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriberController extends Controller
{
    public function __construct(protected RadiusService $radius) {}

    public function index(Request $request)
    {
        $query = Subscriber::with(['package', 'router']);
        // Allow filtering by 'type' (alias for connection_type) on the general index view
        if ($request->filled('type')) {
            $query->where('connection_type', $request->type);
        }
        $this->applyFilters($query, $request);
        $subscribers = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $packages = IspPackage::where('is_active', true)->orderBy('name')->get();
        $routers  = Router::where('is_active', true)->orderBy('name')->get();
        return view('admin.isp.subscribers.index', compact('subscribers', 'packages', 'routers'));
    }

    public function create()
    {
        $packages = IspPackage::where('is_active', true)->orderBy('price')->get();
        $routers  = Router::where('is_active', true)->orderBy('name')->get();
        return view('admin.isp.subscribers.create', compact('packages', 'routers'));
    }

    /** Display PPPoE subscribers only. */
    public function pppoe(Request $request)
    {
        $query = Subscriber::with(['package', 'router'])
            ->where('connection_type', 'pppoe');
        $this->applyFilters($query, $request);
        $subscribers = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $packages = IspPackage::where('is_active', true)->orderBy('name')->get();
        $routers  = Router::where('is_active', true)->orderBy('name')->get();
        return view('admin.isp.subscribers.pppoe', compact('subscribers', 'packages', 'routers'));
    }

    /** Display Hotspot subscribers only. */
    public function hotspot(Request $request)
    {
        $query = Subscriber::with(['package', 'router'])
            ->where('connection_type', 'hotspot');
        $this->applyFilters($query, $request);
        $subscribers = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $packages = IspPackage::where('is_active', true)->orderBy('name')->get();
        $routers  = Router::where('is_active', true)->orderBy('name')->get();
        return view('admin.isp.subscribers.hotspot', compact('subscribers', 'packages', 'routers'));
    }

    /** Shared filter logic for subscriber queries. */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('username', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%");
            });
        }
        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('package_id')) $query->where('isp_package_id', $request->package_id);
        if ($request->filled('router_id')) $query->where('router_id', $request->router_id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'email'           => 'nullable|email|max:100',
            'phone'           => 'required|string|max:20',
            'username'        => 'required|string|max:64|unique:subscribers,username',
            'password'        => 'required|string|min:6|max:64',
            'isp_package_id'  => 'nullable|exists:isp_packages,id',
            'router_id'       => 'nullable|exists:routers,id',
            'connection_type' => 'required|in:pppoe,hotspot',
            'status'          => 'required|in:active,suspended,expired',
            'expires_at'      => 'nullable|date',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
        ]);

        $plainPassword = $data['password'];
        $data['password_hash']    = bcrypt($plainPassword);
        $data['radius_password']  = encrypt($plainPassword);
        $data['created_by']       = 'admin';
        unset($data['password']);

        $subscriber = Subscriber::create($data);

        // Provision RADIUS
        if ($subscriber->isp_package_id) {
            $this->radius->provisionUser($subscriber->username, $plainPassword, $subscriber->package);
        }

        AuditLog::record('subscriber.created', Subscriber::class, $subscriber->id, [], $subscriber->only(['name','username','phone','status']));

        return redirect()->route('admin.isp.subscribers.index')->with('success', "Subscriber '{$subscriber->name}' created.");
    }

    public function show(Subscriber $subscriber)
    {
        $subscriber->load(['package', 'router', 'payments']);
        $sessions = \App\Models\Radacct::where('username', $subscriber->username)
            ->orderBy('acctstarttime', 'desc')
            ->limit(20)
            ->get();
        return view('admin.isp.subscribers.show', compact('subscriber', 'sessions'));
    }

    public function edit(Subscriber $subscriber)
    {
        $packages = IspPackage::where('is_active', true)->orderBy('price')->get();
        $routers  = Router::where('is_active', true)->orderBy('name')->get();
        return view('admin.isp.subscribers.edit', compact('subscriber', 'packages', 'routers'));
    }

    public function update(Request $request, Subscriber $subscriber)
    {
        $old = $subscriber->only(['name','username','phone','status','isp_package_id']);
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'email'           => 'nullable|email|max:100',
            'phone'           => 'required|string|max:20',
            'username'        => "required|string|max:64|unique:subscribers,username,{$subscriber->id}",
            'password'        => 'nullable|string|min:6|max:64',
            'isp_package_id'  => 'nullable|exists:isp_packages,id',
            'router_id'       => 'nullable|exists:routers,id',
            'connection_type' => 'required|in:pppoe,hotspot',
            'status'          => 'required|in:active,suspended,expired',
            'expires_at'      => 'nullable|date',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
        ]);

        $plainPassword = null;
        if (!empty($data['password'])) {
            $plainPassword = $data['password'];
            $data['password_hash']   = bcrypt($plainPassword);
            $data['radius_password'] = encrypt($plainPassword);
        }
        unset($data['password']);

        $subscriber->update($data);

        // Re-provision RADIUS if package or password changed
        if ($subscriber->isp_package_id) {
            $radPass = $plainPassword ?? decrypt($subscriber->radius_password);
            if ($data['status'] === 'suspended') {
                $this->radius->suspendUser($subscriber->username);
            } else {
                $this->radius->provisionUser($subscriber->username, $radPass, $subscriber->package);
            }
        }

        AuditLog::record('subscriber.updated', Subscriber::class, $subscriber->id, $old, $subscriber->fresh()->only(['name','username','phone','status']));
        return redirect()->route('admin.isp.subscribers.index')->with('success', 'Subscriber updated.');
    }

    public function destroy(Subscriber $subscriber)
    {
        $this->radius->removeUser($subscriber->username);
        AuditLog::record('subscriber.deleted', Subscriber::class, $subscriber->id, $subscriber->toArray(), []);
        $subscriber->delete();
        return redirect()->route('admin.isp.subscribers.index')->with('success', 'Subscriber deleted.');
    }

    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:suspend,activate,delete',
            'ids'    => 'required|array',
            'ids.*'  => 'integer',
        ]);

        $subscribers = Subscriber::whereIn('id', $data['ids'])->get();

        foreach ($subscribers as $subscriber) {
            match ($data['action']) {
                'suspend'  => $this->radius->suspendUser($subscriber->username) && $subscriber->update(['status' => 'suspended']),
                'activate' => $subscriber->update(['status' => 'active']),
                'delete'   => $this->radius->removeUser($subscriber->username) ?: $subscriber->delete(),
                default    => null,
            };
        }

        return back()->with('success', ucfirst($data['action']) . ' applied to ' . count($data['ids']) . ' subscribers.');
    }
}