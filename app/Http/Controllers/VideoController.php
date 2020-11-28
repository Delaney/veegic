<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Video;

class VideoController extends Controller
{
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video' => 'required|mimes:mp4,avi'
        ]);

        $video = new Video;
        $fileName = time() . '_' . $request->file->getClientOriginalName();
        
        $video->title = $fileName;
        $video->src = '/storage/' . $request->file('video')->storeAs('uploads', $fileName, 'public');
        $video->format = $request->file->getClientOriginalExtension();
    }
}
