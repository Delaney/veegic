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
        $user = $request->input('user');

        if ($user) {
            return $user->videos;
        }
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video' => 'required'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ]);
        }

        // $file = Input::file('file');
        $file = $request->file('video');
        $mime = $file->getMimeType();
        $mimes = ['video/x-flv','video/mp4','application/x-mpegURL','video/MP2T','video/3gpp','video/quicktime','video/x-msvideo','video/x-ms-wmv'];

        if (!in_array($mime, $mimes)) {
            return response()->json([
                'error' => 'invalid_file_type',
                'message' => 'The uploaded file is not a video file'
            ]);
        }
        $user = $request->input('user');

        $video = new Video;
        $video->user_id = $user->id;
        
        $fileName = time() . '_' . $request->file('video')->getClientOriginalName();
        
        $video->title = $fileName;
        $video->src = '/storage/' . $request->file('video')->storeAs('uploads', $fileName, 'public');
        $video->extension = $request->file('video')->getClientOriginalExtension();
        $video->save();

        return response()->json([
            'success'   => true,
            'message'   => 'Video uploaded successfully'
        ]);
    }
}
