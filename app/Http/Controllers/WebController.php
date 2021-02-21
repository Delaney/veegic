<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;

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

        $subscription = Subscription::where('user_id', $user->id)->first();

        return response()->json([
            'user' => [
                'name'      => $user->name,
                'email'     => $user->email,
            ],
            'subscription' => [
                'type'      => $subscription->type,
                'expire_at' => $subscription->type == 'pro'?
                    $subscription->expire_at:
                    null
            ]
        ]);
    }
}
