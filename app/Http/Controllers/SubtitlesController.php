<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Video;
use App\Models\Subtitles;
use App\Models\Queue\TranscribeJob;
use App\Jobs\Transcribe;
use App\Subtitle;

class SubtitlesController extends Controller
{
    public function transcribe(Request $request)
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
        $slug = $request->input('slug');
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
                'job_name' => uniqid()
            ]);
            $job->save();
    
            Transcribe::dispatch($job->id, $slug);
            
            return response()->json([
                'success' => true,
                'job' => $job->id
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
