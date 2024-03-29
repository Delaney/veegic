<?php

namespace App\Http\Controllers;

set_time_limit(0);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use App\Models\Video;
use Aws\S3\S3Client;
use Carbon\Carbon;
use App\Models\EditLog;
use App\Models\Subtitles;
use App\Watermark;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->input('user');

        if ($user) {
            $videos = $user->videos->toArray();
            $data = array_map(function($video) {
                if ($video['progress']) {
                    $progress = EditLog::find($video['progress']);
                    try {
                        $media = FFMpeg::open($progress->result_src);
            
                        $dimensions = $media
                            ->getVideoStream()
                            ->getDimensions();
                        
                    } catch (\Exception $ex) {
                        \Log::error($ex->getMessage());
                    }

                    $arr = array_merge($video, ['progress' => [
                        'id'    => $progress->id,
                        'src'   => $progress->result_src,
                        'dimensions' => isset($dimensions) ? "{$dimensions->getWidth()}x{$dimensions->getHeight()}" : $video['dimensions']
                    ]]);
                        
                    return $arr;
                }
                // return Arr::only($video, ['id', 'title', 'slug', 's3_url', 'dimensions', 'thumbnail', 'progress']);
                return $video;
            }, $videos);

            return $data;
        }

        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
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
            ], 400);
        }

        $file = $request->file('video');
        $mime = $file->getMimeType();
        $mimes = ['video/x-flv','video/mp4','application/x-mpegURL','video/MP2T','video/3gpp','video/quicktime','video/x-msvideo','video/x-ms-wmv'];

        if (!in_array($mime, $mimes)) {
            return response()->json([
                'error' => 'invalid_file_type',
                'message' => 'The uploaded file is not a video file'
            ], 400);
        }
        $user = $request->input('user');

        $name = time() . '_' . Str::random(8) . '.' . $request->file('video')->getClientOriginalExtension();
        $fileName = Str::of($name)->basename('.' . $request->file('video')->getClientOriginalExtension());
        
        $video_url = $this->uploadToS3($request->file('video'), $name);
        
        $slug = $this->random_str(15);
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

        $media = FFMpeg::open($video->src);
        
        $dimensions = $media
            ->getVideoStream()
            ->getDimensions();

        $thumbnail = $media->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(2))
            ->save(storage_path('app/public') . '/' . "thumbnails/$fileName.jpg");
        
        $video->dimensions = "{$dimensions->getWidth()}x{$dimensions->getHeight()}";
        $video->thumbnail = "thumbnails/$fileName.jpg";
        $video->save();

        return response()->json([
            'success'   => true,
            'slug'   => $video->slug,
            'dimensions' => $video->dimensions,
            'thumbnail' => $video->thumbnail,
            's3'        => $video->s3_url
        ]);
    }

    public function uploadLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ], 400);
        }

        $url = $request->input('url');
        $user = $request->input('user');
        
        $fileName = Str::random(8);
        $name = 'uploads/' . time() . '_' . $fileName . '.mp4';
        
        Storage::disk('local')
            ->put($name, file_get_contents($url));

        $media = FFMpeg::open($name);

        $dimensions = $media
            ->getVideoStream()
            ->getDimensions();
        
        $width = $dimensions->getWidth();
        $height = $dimensions->getHeight();

		$duration = $media->getDurationInSeconds();
        
        $slug = $this->random_str(15);
        while (Video::where('slug', $slug)->exists()) {
            $slug = $this->random_str(12);
        }

        $video_url = $this->uploadToS3(storage_path('app/') . $name, $fileName . '.mp4');

        $video = new Video;
        $video->user_id = $user->id;        
        $video->title = $fileName;
        $video->src = $name;
        $video->extension = 'mp4';
        $video->slug = $slug;
        $video->s3_url = $video_url;

        $media = FFMpeg::open($video->src);
        
        $dimensions = $media
            ->getVideoStream()
            ->getDimensions();

        $thumbnail = $media->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(2))
            ->save(storage_path('app/public') . '/' . "thumbnails/$fileName.jpg");
        
        $video->dimensions = "{$dimensions->getWidth()}x{$dimensions->getHeight()}";
        $video->thumbnail = "thumbnails/$fileName.jpg";
        $video->save();

        return response()->json([
            'success'   => true,
            'slug'   => $video->slug,
            'dimensions' => $video->dimensions,
            'thumbnail' => $video->thumbnail,
            's3'        => $video->s3_url
        ]);
    }

    private function uploadToS3($file, $name)
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

        try {
            $result = $s3->putObject([
                'Bucket'    =>  $bucketName,
                'Key'       =>  urlencode($name),
                'Body'      =>  fopen($file, 'r'),
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

    public function deleteVideo($slug)
    {
        $video = Video::where('slug', $slug)->first();
        if ($video->subtitles) {
            $sub = Subtitles::where('video_id', $video->id)->first();
            unlink(storage_path('app') . '/' . $sub->src);
            $sub->delete();
        }

        unlink(storage_path('app') . '/' . $video->src);
        $video->delete();

        return response()->json([
            'success'   => true
        ]);
    }

    public function saveProgress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug'      => 'required',
            'job_id'    => 'required'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ], 400);
        }

        $video = Video::where('slug', $request->input('slug'))->first();

        $video->progress = $request->input('job_id');
        $video->save();

        return response()->json([
            'success'   => true
        ]);
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
        $subscription = $user->subscription;

        $log = EditLog::find($log_id);
        if ($log && $log->user_id == $user->id) {
            $result = $log->result_src;
            $path = storage_path('app/' . $result);

            if (file_exists(public_path("storage/$result"))) {
                return response()->json([
                    'exists'    => true,
                    'complete' => true,
                    'url' => 'storage/' . $result
                ]);
            }

            $progress = $log->progress;
            if ($progress == 100) {
                $name = explode('/', $result);
                $len = count($name);
                $name = $name[$len - 1];

                $format = $request->input('format');
                if ($format && $format != substr($result, -3)) {
                    $newTitle = substr($result, 0, (strlen($result) - 3));
                    $newFile = "${newTitle}${format}";

                    FFMpeg::open($result)
                        ->export()
                        ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame'))
                        ->save($newFile);
                    $result = $newFile;
                    $name = explode('/', $result);
                    $len = count($name);
                    $name = $name[$len - 1];
                }

                if ($subscription->type == 'free') {
                    $path = Watermark::add($result);
                    
                } else {
                        file_put_contents(public_path('storage/' . $result), file_get_contents(storage_path('app/') . $result));
                    $path = public_path('storage/' . $result);
                }
                
                if ($request->input('final')) {
                    // $path = Watermark::add($result);
                    
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
                    
                    try {
                        $result = $s3->putObject([
                            'Bucket'    =>  $bucketName,
                            'Key'       =>  urlencode($name),
                            'Body'      =>  fopen($path, 'r'),
                            'ACL'       =>  'public-read',
                        ]);
                        $path = $result->get('ObjectURL');
                        $log->s3 = $path;
                        $log->save();

                        return response()->json([
                            'complete' => true,
                            'url' => $path
                        ]);
                    } catch (\Exception $e) {
                        return response()->json([
                            'error' => $e->getMessage()
                        ], 400);
                    }
                }
                //  else {
                //     file_put_contents(public_path('storage/' . $result), file_get_contents($path));
                //     $path = public_path('storage/' . $result);
                // }
                return response()->json([
                    'complete' => true,
                    'url' => 'storage/' . $result
                ]);
                    
                
            } 
            else {
                return response()->json([
                    'complete' => false,
                    'progress' => $progress
                ]);
            }
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
