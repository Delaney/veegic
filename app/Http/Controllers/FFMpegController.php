<?php

namespace App\Http\Controllers;

use App\Jobs\BurnSRT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Video;

class FFMpegController extends Controller
{
    public function burnSRT(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ]);
        }

        $user = $request->input('user');
        $video = Video::where('slug', $request->input('slug'))->first();
        
        if (!$video) {
            return response()->json([
                'video' => false,
                'message' => 'Video not found'
            ], 400);
        }

        if ($video->user_id === $user->id) {
            BurnSRT::dispatch($video->id);

            return response()->json([
                'job' => true,
            ], 200);
        } else {
            return response()->json([
                'video' => false
            ], 400);
        }
    }
}
