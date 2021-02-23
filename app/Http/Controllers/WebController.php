<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Transaction;

class WebController extends Controller
{
    public function index()
    {
        return view('index');
    }

    public function user(Request $request)
    {
        $user = (object) $request->input('user');
        if (!$user) return response()->json([
            'message' => 'Unauthorized'
        ], 401);

        $subscription   = Subscription::where('user_id', $user->id)->first();
        if ($subscription->type == 'pro' && $subscription->current_txn_id) {
            $transaction    = Transaction::find($subscription->current_txn_id);
        }

        $response['user'] = [
            'name'      => $user->name,
            'email'     => $user->email,
        ];
        $response['subscription'] = [
            'type'      => $subscription->type,
            'expire_at' => $subscription->type == 'pro'?
                $subscription->expire_at:
                null
        ];
        if ($subscription->type == 'pro' && isset($transaction)) {
            $response['subscription']['renewal'] = $transaction->action == 'start'?
                true : false;
        }

        return response()->json($response);
    }
}
