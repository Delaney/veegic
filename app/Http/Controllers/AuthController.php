<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ApiToken;
use Carbon\Carbon;

class AuthController extends Controller
{
	public function register(Request $request)
	{
		try {
			$validator = Validator::make($request->all(), [
				'email' => 'email|required',
				'password' => 'required'
			]);

			if ($validator->fails()) {
				$error = $validator->errors()->first();
				return response()->json([
					'error' => 'invalid_input',
					'message' => $error
				]);
			}
			
			$user = User::where('email', $request->email)->first();
			if ($user) {
				return response()->json([
					'message' => 'Email already exists',
					'token_type' => 'Bearer',
				], 400);
			}

			$user = User::create([
				'name'	=>	$request->input('name'),
				'email'	=>	$request->input('email'),
				'password'	=> Hash::make($request->input('password'))
			]);
			$this->generateApiKey($user);
            $this->generateToken($user);
			return response()->json([
				'success' => true,
				'api_token' => $user->api_token,
			]);
		} catch (\Exception $error) {
			return response()->json([
				'message' => 'Login Error',
				'error' => $error,
			], 500);
		}
	}

	public function login(Request $request)
	{
		try {
			$validator = Validator::make($request->all(), [
				'email' => 'email|required',
				'password' => 'required',
			]);

			if ($validator->fails()) {
				$error = $validator->errors()->first();
				return response()->json([
					'error' => 'invalid_input',
					'message' => $error
				]);
			}

			$credentials = request(['email', 'password']);

			if (!Auth::attempt($credentials)) {
				return response()->json([
					'message' => 'Unauthorized'
				], 401);
			}
			
			$user = User::where('email', $request->email)->first();
			if (!Hash::check($request->password, $user->password, [])) {
				return response()->json([
					'message' => 'Unauthorized'
				], 401);
			}
			$this->generateApiKey($user);
            $this->generateToken($user);
            return response()->json([
                'api_token' => $user->api_token,
            ]);
		} catch (\Exception $error) {
			return response()->json([
				'message' => 'Error',
				'error' => $error,
			], 500);
		}
	}

	protected function generateToken($user)
    {
        $token = ApiToken::where('user_id', $user->id)
        ->first();

        $date = Carbon::now()->addDays(30);

        if ($token) {
            $token->api_token = $user->api_token;
            $token->api_token_expire_at = $date;
            $token->save();
        } else {
            $token = ApiToken::create([
                'user_id' => $user->id,
                'api_token' => $user->api_token,
                'api_token_expire_at' => $date,
            ]);
        }
    }

    protected function generateApiKey($user)
    {
        $user->roll_api_key();
        $date = Carbon::now()->addDays(30);
        $user->api_token_expire_at = $date;
        $user->save();
    }
}