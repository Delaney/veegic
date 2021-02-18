<?php

namespace App\Http\Controllers;

use App\Paddle;
use Illuminate\Http\Request;

class PaddleController extends Controller
{
    public function generate_payment_link(Request $request) {
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
        }

    }
}
