<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\MpesaPayment;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $subscriber = Subscriber::where('email', $user->email)->first();

        $payments = MpesaPayment::where('subscriber_id', $subscriber?->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('customer.payments', compact('payments'));
    }
}
