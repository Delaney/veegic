<?php

namespace App\Http\Controllers;

set_time_limit(0);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Video;
use Aws\S3\S3Client;
use Carbon\Carbon;
use App\Models\EditLog;
use App\Watermark;

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

        $name = time() . '_' . $request->file('video')->getClientOriginalName();
        $fileName = Str::of($name)->basename('.' . $request->file('video')->getClientOriginalExtension());
        
        $video_url = $this->uploadToS3($request);
        
        $slug = $this->random_str(12);
        while (Video::where('slug', $slug)->exists()) {
            $slug = $this->random_str(12);
        }

        $video = new Video;
        $video->user_id = $user->id;        
        $video->title = $fileName;
        $video->src = $request->file('video')->storeAs('uploads', $name);
        $video->extension = $request->file('video')->getClientOriginalExtension();
        $video->slug = $slug;
        $video->s3_url = $video_url;
        $video->save();

        return response()->json([
            'success'   => true,
            'message'   => 'Video uploaded successfully'
        ]);
    }

    private function uploadToS3(Request $request)
    {
        $region = config('aws.region');
        $access_key = config('aws.access_key');
        $secret_access_key = config('aws.secret_access_key');
        $bucketName = config('aws.bucket_name');

        $s3 = new S3Client([
            'version'   =>  'latest',
            'region'    =>  $region,
            'credentials'   => [
                'key'       =>  $access_key,
                'secret'    =>  $secret_access_key
            ]
        ]);

        $fileName = time() . '_' . $request->file('video')->getClientOriginalName();
        
        try {
            $result = $s3->putObject([
                'Bucket'    =>  $bucketName,
                'Key'       =>  urlencode($fileName),
                'Body'      =>  fopen($request->file('video'), 'r'),
                'ACL'       =>  'public-read',
            ]);
            $video_url = $result->get('ObjectURL');
            return $video_url;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function download($slug)
    {
        $video = Video::where('slug', $slug)->first();

        $file = public_path() . $video->src;

        $headers = array('Content-Type: application/pdf');

        return response()->download($file, $video->title, $headers);
    }

    public function downloadResult($log_id, Request $request)
    {
        $user = $request->input('user');
        $log = EditLog::find($log_id);
        if ($log && $log->user_id == $user->id) {
            $result = $log->result_src;
            $path = storage_path('app/' . $result);

            $exists = file_exists($path);

            if ($exists) {
                $size = filesize($path);
                
                $name = explode('/', $result);
                $len = count($name);
                $name = $name[$len - 1];
                
                if ($size > 0) {
                    $path = Watermark::add($result);
                    return response()->download($path, $name);
                } else {
                    return response()->json([
                        'status' => false
                    ]);
                }
            }

            return response()->json([
                'error' => 'Video not found'
            ]);
        } else {
            return response()->json([
                'error' => 'Wrong id'
            ]);
        }
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
