<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WatermarkController extends Controller
{
    public function get(Request $request)
    {
        $user = $request->input('user');
        if ($user->subscription->type !== 'pro') {
            return response()->json([
                'error' => 'invalid_permission',
                'message' => 'Please upgrade your account'
            ], 400);
        }

        $watermark = null;
        if ($user->watermark) {
            $file = file_get_contents(storage_path('app') . '/' . "watermarks/$user->watermark");
            $watermark = base64_encode($file);

        }
        
        return response()->json([
            'success'   => true,
            'watermark' => $watermark
        ]);
    }

    public function set(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,png|max:1014'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ], 400);
        }

        $user = $request->input('user');
        if ($user->subscription->type !== 'pro') {
            return response()->json([
                'error' => 'invalid_permission',
                'message' => 'Please upgrade your account'
            ], 400);
        }

        $image = $request->file('image');
        $extension = $image->extension();
        $name = Str::random(8) . '-' . time() . '.' . $extension;
        $request->file('image')->storeAs('watermarks', $name);
        $user->watermark = $name;
        $user->save();

        $file = file_get_contents(storage_path('app') . '/' . "watermarks/$name");

        return response()->json([
            'success'   => true,
            'watermark' => base64_encode($file)
        ]);
    }
}
