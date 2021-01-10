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
            $log->type = 'Burn Subtitles';
            $log->result_src = 'jobs/' . time() . '_' . uniqid() . '.' . $video->extension;
            $log->save();
            BurnSRT::dispatch($log->id)->onQueue('BurnSubtitles');

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
            'ratio.x' => 'required',
            'ratio.y' => 'required',
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
            $user_id = 0;
            
            $check = $this->checkSlugOrId($request);
            if (array_key_exists('error', $check)) return $check['json'];

            $log->src = $check['src'];
            $log->video_id = $check['video_id'];
            $user_id = $check['user_id'];

            $ratioX = $request->input('ratio')['x'];
            $ratioY = $request->input('ratio')['y'];

            $log->type = 'Change aspect: ';
            $log->result_src = 'jobs/' . time() . '_' . uniqid() . '.' . substr($log->src, -3);
            $log->save();
                
            if ($user_id === $user->id) {
                Resize::dispatch($log->id, $request->input('ratio'))->onQueue('Resize');
    
                return response()->json([
                    'job' => $log->id,
                ], 200);
            } else {
                return response()->json([
                    'error'     => true,
                    'message'   => 'Unauthorized'
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
            $user_id = 0;

            $check = $this->checkSlugOrId($request);
            if (array_key_exists('error', $check)) return $check['json'];

            $log->src = $check['src'];
            $log->video_id = $check['video_id'];
            $user_id = $check['user_id'];

            $start_time = $request->input('start_time');
            $end_time = $request->input('end_time');

            $log->type = "Clip: $start_time - $end_time";
            $log->result_src = 'jobs/' . time() . '_' . uniqid() . '.' . substr($log->src, -3);
            $log->save();

            if ($user_id === $user->id) {
                Clip::dispatch($log->id, $start_time, $end_time)->onQueue('Clip');
    
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

    public function checkSlugOrId(Request $request)
    {
        $user_id = 0;
        $video_id = null;
        
        if ($request->input('slug')) {
            $video = Video::where('slug', $request->input('slug'))->first();
            if (!$video) {
                return [
                    'error' => true,
                    'json'  => response()->json([
                        'video' => false,
                        'message' => 'Video not found'
                    ], 400)
                ];
            }

            $video_id = $video->id;
            $src = $video->src;
            $user_id = $video->user_id;
        } else {
            $log = EditLog::find($request->input('id'));
            if (!$log) {
                return [
                    'error' => true,
                    'json'  => response()->json([
                        'video' => false,
                        'message' => 'Job not found'
                    ], 400)
                ];
            }

            $src = $log->result_src;
            $user_id = EditLog::find($request->input('id'))->user_id;
        }

        return [
            'src'       => $src,
            'user_id'   => $user_id,
            'video_id'     => $video_id ? $video_id : null
        ];
    }
}
