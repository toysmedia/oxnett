<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\MpesaPayment;
use App\Models\Radacct;
use App\Models\IspPackage;
use Illuminate\Http\Request;
use App\Services\MpesaService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Find subscriber record for this user (by email or phone)
        $subscriber = Subscriber::where('email', $user->email)
            ->orWhere('phone', $user->mobile ?? '')
            ->first();

        $packages = IspPackage::where('is_active', true)->orderBy('price')->get();

        $recentPayments = MpesaPayment::where('subscriber_id', $subscriber?->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $activeSessions = [];
        if ($subscriber) {
            $activeSessions = Radacct::where('username', $subscriber->username)
                ->whereNull('acctstoptime')
                ->get();
        }

        return view('customer.dashboard', compact('subscriber', 'packages', 'recentPayments', 'activeSessions'));
    }

    public function renew(Request $request)
    {
        $data = $request->validate([
            'package_id' => 'required|exists:isp_packages,id',
            'phone'      => 'required|string|min:9|max:15',
        ]);

        // Initiate STK push
        $mpesa = app(MpesaService::class);
        $user = auth()->user();
        $subscriber = Subscriber::where('email', $user->email)->first();
        $package = IspPackage::findOrFail($data['package_id']);

        try {
            $response = $mpesa->stkPush($data['phone'], $package->price, "RENEW-{$subscriber?->id}", "Renewal: {$package->name}");
            return back()->with('success', 'STK Push sent! Enter your M-Pesa PIN to complete payment.');
        } catch (\Exception $e) {
            return back()->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }
}
