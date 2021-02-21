<?php

namespace App\Http\Controllers;

use App\Enums\PaddleWebhookAlert;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WebhookController extends Controller
{
    public function post(Request $request) {
        // \Log::debug($request->all());
        $obj = (array) $request->all();
        $timezone = config('app.timezone');

        switch ($obj['alert_name']) {
            case PaddleWebhookAlert::created:
                // $user = User::where('email', $obj['email'])->first();

                // if ($user) {
                //     $subscription = $user->subscription;

                //     $transaction = Transaction::create([
                //         'subscription_id'           => $subscription->id,
                //         'type'                      => 'credit',
                //         'start_date'                => Carbon::parse($obj['event_time'])->timezone($timezone),
                //         'end_date'                  => Carbon::parse($obj['event_time'])->addDays(30)->timezone($timezone),
                //         'checkout_id'               => $obj['checkout_id'],
                //         'status'                    => 'pending',
                //         'paddle_subscription_id'    => $obj['subscription_id'],
                //         'action'                    => 'start'
                //     ]);
                // }

                break;

            case PaddleWebhookAlert::payment_success:
                $user = User::where('email', $obj['email'])->first();

                if ($user) {
                    $subscription = $user->subscription;

                    $transaction = Transaction::where('order_id', $obj['order_id'])
                        ->where('status', 'pending')
                        ->first();

                    if (!$transaction) {
                        $transaction = Transaction::create([
                            'subscription_id'           => $subscription->id,
                            'type'                      => 'credit',
                            'start_date'                => Carbon::parse($obj['event_time'])->timezone($timezone),
                            'order_id'                  => $obj['order_id'],
                            'checkout_id'               => $obj['checkout_id'],
                            'status'                    => 'success',
                            'paddle_subscription_id'    => $obj['subscription_id'],
                            'action'                    => 'start'
                        ]);
                    }

                    $transaction->payment_date = Carbon::parse($obj['event_time'])->timezone($timezone);
                    $transaction->end_date = Carbon::parse($obj['next_bill_date'])->timezone($timezone);
                    $transaction->payment_method = $obj['payment_method'];
                    $transaction->receipt_url = $obj['receipt_url'];

                    $transaction->save();

                    // UPDATE SUBSCRIPTION
                    $subscription->type = 'pro';
                    if (!$subscription->current_txn_id) {
                        $subscription->current_txn_id = $transaction->id;
                    }

                    $now = Carbon::now($timezone);
                    if ($now > $subscription->expire_at) {
                        $subscription->expire_at = $transaction->end_date;
                    } else {
                        $expires = Carbon::parse($subscription->expire_at)->timezone($timezone)->addDays(30);
                        $subscription->expire_at = $expires;
                    }

                    $subscription->save();
                }

                break;

            case PaddleWebhookAlert::cancelled:
                $user = User::where('email', $obj['email'])->first();

                if ($user) {
                    $subscription = $user->subscription;
                    $transaction = Transaction::create([
                        'subscription_id'           => $subscription->id,
                        'type'                      => 'debit',
                        'start_date'                => Carbon::parse($obj['event_time'])->timezone($timezone),
                        'end_date'                  => Carbon::parse($obj['cancellation_effective_date'])->timezone($timezone),
                        'checkout_id'               => $obj['checkout_id'],
                        'status'                    => 'success',
                        'paddle_subscription_id'    => $obj['subscription_id'],
                        'action'                    => 'cancel'
                    ]);

                    // UPDATE SUBSCRIPTION
                    $subscription->type = 'pro';
                    $subscription->current_txn_id = $transaction->id;
                    $subscription->expire_at = $transaction->end_date;
                    
                    $subscription->save();
                }
        }
    }
}
