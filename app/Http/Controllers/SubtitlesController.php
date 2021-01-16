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
            $sub = Subtitles::where('video_id', $video->id)->first();

            if (!$sub) {
                $subtitles = Subtitles::create([
                    'video_id' => $video->id,
                    'title' => "$video->title.srt" ,
                    'src' => "subtitles/$video->title.srt"
                ]);

                $job_name = time() . '_' . uniqid();
                $log = EditLog::create([
                    'user_id'       => $user->id,
                    'video_id'      => $video->id,
                    'type'          => 'transcribe',
                    's3'          => $job_name,
                    'result_src'    => "subtitles/$video->title.srt"
                ]);
        
                Transcribe::dispatch($log->id, $slug);
                
                return response()->json([
                    'success' => true,
                    'id' => $log->id
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Subtitles already exist'
                ]);
            }
        } else {
            return response()->json([
                'video' => false,
                'message' => 'Permissions not found'
            ], 400);
        }
    }

    public function getSubtitles($slug, Request $request)
    {
        $user = $request->input('user');
        $video = Video::where('slug', $slug)->first();
        if (!$video) {
            return response()->json([
                'video' => false,
                'message' => 'Video not found'
            ], 400);
        }
        if ($video->user_id === $user->id) {
            $sub = $video->subtitles;
            if (!$sub) {
                return response()->json([
                    'subtitles' => false,
                    'message' => 'Subtitles not found'
                ], 400);
            }
            $path = storage_path('app/' . $sub->src);
            $log = EditLog::where('result_src', $sub->src)->first();

            if ($log->complete) {
                if ($request->input('file')) {
                    $exists = file_exists($path);
                    if ($exists) {
                        $headers = array(
                            'Content-Disposition' => 'attachment;filename=subtitles.srt',
                            'Content-Type' => 'application/octet-stream'
                        );
            
                        return response()->download(storage_path("app/$sub->src"), $sub->title, $headers);
                    } else {
                        return response()->json([
                            'subtitles' => false,
                            'message' => 'Subtitle file not found'
                        ], 400);
                    }
                } else {
                    return response()->json(unserialize($sub->data));
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Transcription job not done'
                ]);
            }

        }
    }
}
