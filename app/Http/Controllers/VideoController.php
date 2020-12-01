<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Video;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            return $user->videos;
        }
    }

    public function upload(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'video' => 'required|mimes:mp4,avi'
        // ]);

        $user = Auth::user();

        \Log::info(print_r($user, true));

        $video = new Video;
        $video->user_id = $user->id;
        
        $fileName = time() . '_' . $request->file('video')->getClientOriginalName();
        
        $video->title = $fileName;
        $video->src = '/storage/' . $request->file('video')->storeAs('uploads', $fileName, 'public');
        $video->extension = $request->file('video')->getClientOriginalExtension();
        $video->save();
    }
}
