<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Video;
use App\Models\Subtitles;
use App\Models\Queue\TranscribeJob;
use App\Jobs\Transcribe;
use App\Models\EditLog;
use App\Subtitle;
use Illuminate\Support\Facades\File;

class SubtitlesController extends Controller
{
    public function transcribe(Request $request)
    {
        $user = $request->input('user');
        $slug = $request->input('slug');
        $id = $request->input('id');

        $video = Video::where('slug', $slug)->first();

        if (!$video) {
            return response()->json([
                'video' => false,
                'message' => 'Video not found'
            ], 400);
        }

        if ($video->user_id === $user->id) {
            $job = TranscribeJob::create([
                'video_id' => $video->id,
                'job_name' => time() . '_' . uniqid()
            ]);
            $job->save();

            $job_name = time() . '_' . uniqid();

            // $log = EditLog::create([
            //     'user_id'       => $user->id,
            //     'video_id'      => $video->id,
            //     'type'          => 'transcribe',
            //     'data'          => $job_name,
            // ]);
    
            Transcribe::dispatch($job->id, $slug)->onQueue('Subtitles');
            
            return response()->json([
                'success' => true,
                'id' => $job->id
            ]);
        } else {
            return response()->json([
                'video' => false,
                'message' => 'Permissions not found'
            ], 400);
        }
    }

    public function getSubtitles($job_id)
    {
        $job = TranscribeJob::find($job_id);

        if ($job->complete) {
            $url = $job->url;
            $video = Video::find($job->video_id);
    
            $client = new \GuzzleHttp\Client();
            $arr_data = [];
            
            $response = $client->request('GET', $url);
            if ($response->getStatusCode() == 200) {
                $arr_data = json_decode($response->getBody(), true);
            }
            
            $items = $arr_data['results']['items'];
    
            $sub = new Subtitle;
            $subtitles = $sub->createSRT($items);
            $title = $video->title;
            
            $fileName = $title . '.srt';
            $txt = fopen($fileName, "w") or die("Unable to open file!");
            fwrite($txt, $subtitles);
            fclose($txt);
    
            $path = Storage::putFileAs('subtitles', $fileName, $fileName);
    
            if (!$video->subtitles) {
                $model = new Subtitles;
                $model->video_id = $video->id;
                $model->title = $title;
                $model->src = $path;
                $model->save();
            } else {
                $sub = Subtitles::where('video_id', $video->id)->first();
                $sub->src = $path;
                $sub->save();
            }

            $headers = array(
                'Content-Disposition' => 'attachment;filename=subtitles.srt',
                'Content-Type' => 'application/octet-stream'
            );

            return response()->download($fileName, $fileName, $headers);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Transcription job not done'
            ]);
        }
    }
}
