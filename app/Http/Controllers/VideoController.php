<?php

namespace App\Http\Controllers;

set_time_limit(0);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Video;
use App\Models\Subtitles;
use Aws\S3\S3Client;
use Aws\TranscribeService\TranscribeServiceClient;
use Carbon\Carbon;

class VideoController extends Controller
{
    public function __construct()
    {
        $this->region = getenv('AWS_DEFAULT_REGION');
        $this->access_key = getenv('AWS_ACCESS_KEY_ID');
        $this->secret_access_key = getenv('AWS_SECRET_ACCESS_KEY');
        $this->bucketName = getenv('AWS_BUCKET');
    }

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

        $fileName = time() . '_' . $request->file('video')->getClientOriginalName();
        
        $video_url = $this->uploadToS3($request);
        
        $slug = $this->random_str(12);
        while (Video::where('slug', $slug)->exists()) {
            $slug = $this->random_str(12);
        }

        $video = new Video;
        $video->user_id = $user->id;        
        $video->title = $fileName;
        $video->src = '/storage/' . $request->file('video')->storeAs('uploads', $fileName, 'public');
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
        $s3 = new S3Client([
            'version'   =>  'latest',
            'region'    =>  $this->region,
            'credentials'   => [
                'key'       =>  $this->access_key,
                'secret'    =>  $this->secret_access_key
            ]
        ]);

        $fileName = time() . '_' . $request->file('video')->getClientOriginalName();
        
        try {
            $result = $s3->putObject([
                'Bucket'    =>  $this->bucketName,
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

    public function transcribe($slug)
    {
        $video = Video::where('slug', $slug)->first();
        $video_url = $video->s3_url;
        $awsTranscribeClient = new TranscribeServiceClient(([
            'region'        => $this->region,
            'version'       => 'latest',
            'credentials'   => [
                'key'       => $this->access_key,
                'secret'    =>  $this->secret_access_key
            ]
        ]));

        $job_id = uniqid();
        $transcriptionResult = $awsTranscribeClient->startTranscriptionJob([
            'LanguageCode'  => 'en-US',
            'Media' => [
                'MediaFileUri'  => $video_url,
            ],
            'TranscriptionJobName' => $job_id,
        ]);

        $status = array();
        while(true) {
            $status = $awsTranscribeClient->getTranscriptionJob([
                'TranscriptionJobName' => $job_id
            ]);
            if ($status->get('TranscriptionJob')['TranscriptionJobStatus'] == 'COMPLETED') {
                break;
            }

            sleep(5);
        }

        $url = $status->get('TranscriptionJob')['Transcript']['TranscriptFileUri'];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            echo $error_msg;
        }
        curl_close($curl);
        $arr_data = json_decode($data);

        $items = $arr_data->results->items;
        $subtitles = $this->createSRT($items);

        $model = new Subtitles;
        $model->video_id = $video->id;
        $model->title = substr($video->title, 0, -4);
        
        $fileName = $model->title . '.srt';
        $txt = fopen($fileName, "w") or die("Unable to open file!");
        fwrite($txt, $subtitles);
        fclose($txt);

        $path = Storage::putFileAs('subtitles', $fileName, $fileName);

        $model->src = $path;
        $model->save();

        $headers = array(
            'Content-Disposition' => 'attachment;filename=subtitles.srt',
            'Content-Type' => 'application/octet-stream'
        );
 
        return response()->download($fileName, $fileName, $headers);
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
    
    public function createSRT($items)
    {
        function formatTime($t) {
            try {
                $a = explode('.', $t);
                $date = new Carbon(0);
                $date->second = $a[0];
                $result = substr($date->toISOString(), 11, 8);
                return $result . ',' . $a[1];
            } catch (\Exception $ex) {
                return '';
            }
        }
        $result = '';
        $start_time = '';
        $end_time = '';
        $sentence = '';
        $n = 1;
        $t = 1;
        $wtb = 7;
        $len = count($items);

        for ($i = 0; $i < $len; $i++) {
            if ($items[$i]->type == 'pronunciation') {
                if ($start_time == '') {
                    $start_time = $items[$i]->start_time;
                }
                $end_time = $items[$i]->end_time;
                $sentence = $sentence . $items[$i]->alternatives[0]->content . ' ';
                $t++;
            } else if (
                $items[$i]->type == 'punctuation' &&
                    $items[$i]->alternatives[0]->content == '.'
            ) {
                $result = $result . $n . "\n";
                $result = $result . formatTime($start_time) . ' --> ' . formatTime($end_time) . "\n" . $sentence . "\n\n";
                $sentence = '';
                $start_time = '';
                $n++;
                $t = 1;
            }
            if ($t > $wtb) {
                $result = $result . $n . "\n";
                $result = $result . formatTime($start_time) . ' --> ' . formatTime($end_time) . "\n" . $sentence . "\n\n";
                $sentence = "";
                $start_time = '';
                $n++;
                $t = 1;
            }
        }

        return $result;
    }

    public function format(Request $request)
    {
        $t = $request->input('t');
        $a = explode('.', $t);
        $date = new Carbon(0);
        $date->second = $a[0];
        $result = substr($date->toISOString(), 11, 8);
        return $result . ',' . $a[1];
    }
}
