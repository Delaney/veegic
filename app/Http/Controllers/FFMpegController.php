<?php

namespace App\Http\Controllers;

use App\Jobs\BurnSRT;
use App\Jobs\TrimVideo;
use App\Jobs\Resize;
use App\Jobs\Clip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Video;
use App\Models\EditLog;

class FFMpegController extends Controller
{
    public function burnSRT(Request $request)
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
        $video = Video::where('slug', $request->input('slug'))->first();
        
        if (!$video) {
            return response()->json([
                'video' => false,
                'message' => 'Video not found'
            ], 400);
        }

        if ($video->user_id === $user->id) {
            $log = new EditLog();
            $log->user_id = $user->id;
            $log->video_id = $video->id;
            $log->src = $video->src;
            $log->type = 'burn_subtitles';
            $log->result_src = '/jobs//' . time() . '_' . uniqid() . '.' . $video->extension;
            $log->save();
            BurnSRT::dispatch($log->id);

            return response()->json([
                'job' => $log->id,
            ], 200);
        } else {
            return response()->json([
                'video' => false
            ], 400);
        }
    }

    public function resize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dimensions.width' => 'required',
            'dimensions.height' => 'required',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ]);
        }

        if ($request->input('slug') || $request->input('id')) {
            $user = $request->input('user');
            $log = new EditLog();
            $log->user_id = $user->id;
            
            if ($request->input('slug')) {
                $video = Video::where('slug', $request->input('slug'))->first();
                $log->video_id = $video->id;
                $log->src = $video->src;
            } else {
                $log->src = EditLog::find($request->input('id'))->result_src;
            }

            $log->type = 'resize';
            $log->result_src = 'jobs/' . time() . '_' . uniqid() . '.' . substr($log->src, -3);
            $log->save();
            
            if (!$video) {
                return response()->json([
                    'video' => false,
                    'message' => 'Video not found'
                ], 400);
            }
    
            if ($video->user_id === $user->id) {
                Resize::dispatch($log->id, $request->input('dimensions'));
    
                return response()->json([
                    'job' => $log->id,
                ], 200);
            } else {
                return response()->json([
                    'video' => false
                ], 400);
            }
        } else {
            return response()->json([
                'error' => 'Invalid request',
            ], 400);
        }

    }

    public function clip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'required',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ]);
        }

        if ($request->input('slug') || $request->input('id')) {
            $user = $request->input('user');
            $log = new EditLog();
            $log->user_id = $user->id;
            
            if ($request->input('slug')) {
                $video = Video::where('slug', $request->input('slug'))->first();
                $log->video_id = $video->id;
                $log->src = $video->src;
            } else {
                $log->src = EditLog::find($request->input('id'))->result_src;
            }

            $log->type = 'resize';
            $log->result_src = 'jobs/' . time() . '_' . uniqid() . '.' . substr($log->src, -3);
            $log->save();
            
            if (!$video) {
                return response()->json([
                    'video' => false,
                    'message' => 'Video not found'
                ], 400);
            }

            $start_time = $request->input('start_time');
            $end_time = $request->input('end_time');
            if ($video->user_id === $user->id) {
                Clip::dispatch($log->id, $start_time, $end_time);
    
                return response()->json([
                    'job' => $log->id,
                ], 200);
            } else {
                return response()->json([
                    'video' => false
                ], 400);
            }  
        } else {
            return response()->json([
                'error' => 'Invalid request',
            ], 400);
        }

    }
}
