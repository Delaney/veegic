<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\ApiToken;
use Illuminate\Http\Response;
use Carbon\Carbon;

class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $error_response = [
            'error' => 'invalid_api_key',
            'message' => 'Invalid api key provided.'
        ];

        if($request->bearerToken() != null && $request->bearerToken() != "") {

            $api_token = ApiToken::where('api_token', $request->bearerToken())
                ->where('api_token_expire_at', '>', Carbon::now())
                ->first();

            if($api_token) {
                $user = User::find($api_token->user_id);
                if($user) {
                    $request->merge(['user' => $user]);
                } else {
                    return response()->json($error_response, 401);
                }
            } else {
                return response()->json($error_response, 401);
            }
        } else {
            return response()->json($error_response, 401);
        }

        return $next($request);
    }
}
