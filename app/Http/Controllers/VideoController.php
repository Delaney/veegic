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

        $slug = $this->random_str(12);
        while (Video::where('slug', $slug)->exists()) {
            $slug = $this->random_str(12);
        }

        $fileName = time() . '_' . $request->file('video')->getClientOriginalName();
        
        $video = new Video;
        $video->user_id = $user->id;        
        $video->title = $fileName;
        $video->src = '/storage/' . $request->file('video')->storeAs('uploads', $fileName, 'public');
        $video->extension = $request->file('video')->getClientOriginalExtension();
        $video->slug = $slug;
        $video->save();

        return response()->json([
            'success'   => true,
            'message'   => 'Video uploaded successfully'
        ]);
    }

    public function download($slug)
    {
        $video = Video::where('slug', $slug)->first();

        $file = public_path() . $video->src;

        $headers = array('Content-Type: application/pdf');

        return response()->download($file, $video->title, $headers);
    }

    public static function random_str($length)
	{
		$keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$pieces = [];
		$max = mb_strlen($keyspace, '8bit') - 1;
		for ($i = 0; $i < $length; ++$i) {
			$pieces []= $keyspace[random_int(0, $max)];
		}
		return implode('', $pieces);
	} 
}
