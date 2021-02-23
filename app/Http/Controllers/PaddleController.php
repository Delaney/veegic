<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Paddle;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaddleController extends Controller
{
    public function generate_payment_link(Request $request)
    {
        $user = (object) $request->input('user');

        $paddle = Paddle::init();
        $result = $paddle->generate_payment_link([
            'customer_email'    => $user->email,
        ]);

        if ($result && $result->success && $result->response->url) {
            return response()->json([
                'success'   => true,
                'url'       => $result->response->url
            ]);
        } else {
            return response()->json([
                'error'         => true,
                'message'       => $result->error->message
            ], 400);
        }

    }

    public function get_transactions()
    {

    }

    public function cancel_subscription(Request $request)
    {
        $user = (object) $request->input('user');
        $subscription = $user->subscription;
        $now = Carbon::now(config('app.timezone'));

        if (!$subscription->expire_at ||
        !$subscription->current_txn_id ||
        $now > $subscription->expire_at) {
            return response()->json([
                'error'     => true,
                'message'   => "This user's subscription is not active"
            ], 400);
        }

        $transaction = Transaction::find($subscription->current_txn_id);
        
        $paddle = Paddle::init();
        $result = $paddle->cancel_subscription([
            'subscription_id'    => $transaction->paddle_subscription_id,
        ]);

        if ($result && $result->success) {
            return response()->json([ 
                'success'   => true
            ]);
        } else {
            return response()->json([
                'error'         => true,
                'message'       => $result->error->message
            ], 400);
        }
    }

    public function update_payment(Request $request)
    {
        $user = (object) $request->input('user');
        $subscription = $user->subscription;
        $now = Carbon::now(config('app.timezone'));

        if (!$subscription->expire_at ||
        !$subscription->current_txn_id ||
        $now > $subscription->expire_at) {
            return response()->json([
                'error'     => true,
                'message'   => "This user's subscription is not active"
            ], 400);
        }

        $transaction = Transaction::find($subscription->current_txn_id);
        $sub_id = $transaction->paddle_subscription_id;
        
        $paddle = Paddle::init();
        $result = $paddle->list_users([
            'state' => 'active'
        ]);

        if ($result && $result->success) {
            $obj = array_filter((array) $result->response, function($user) use ($sub_id) {
                if ($user->subscription_id == $sub_id) return true;
            });
            $obj = reset($obj);

            if ($obj) {
                return response()->json([ 
                    'success'   => true,
                    'url'      => $obj->update_url
                ]);
            } else {
                return response()->json([
                    'error'         => true,
                    'message'       => 'Something went wrong, please try again'
                ], 400);
            }

        } else {
            return response()->json([
                'error'         => true,
                'message'       => $result->error->message
            ], 400);
        }
    }
}
