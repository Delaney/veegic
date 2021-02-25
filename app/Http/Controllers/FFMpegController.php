<?php

namespace App\Http\Controllers;

use App\Jobs\BurnSRT;
use App\Jobs\Resize;
use App\Jobs\Clip;
use App\Jobs\ProgressBar;
use App\Jobs\ClipResize;
use App\Jobs\ColorFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Video;
use App\Models\EditLog;
use App\Subtitle;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use App\Watermark;

class FFMpegController extends Controller
{
    public function burnSRT(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug'          => 'required',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ], 400);
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

            $srt = null;
            if ($request->input('srtObjects')) {
                $subs = Subtitle::objToSrt($request->input('srtObjects'));

                $name = time() . '_' . Str::random(8) . '.srt';
                $srt = "subtitles/$name";
                $txt = fopen(storage_path("app/subtitles/$name"), "w") or die("Unable to open file!");
                fwrite($txt, $subs);
                fclose($txt);
            } else if ($request->file('srt')) {
                $name = time() . '_' . Str::random(8) . '.srt';
                $srt = $request->file('srt')->storeAs('subtitles', $name);
            }

            $options = [];
            if ($request->input('options')) {
                $options = $request->input('options');
            }

            BurnSRT::dispatch($log->id, $options, $srt);

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

            if ($request->input('dimensions') && count($request->input('dimensions'))) {
                $ratio_x = $request->input('dimensions')['x'];
                $ratio_y = $request->input('dimensions')['y'];
                $log->type = "Change size: $ratio_x:$ratio_y";
                $type = 'resize';
            } else {
                $ratio_x = $request->input('ratio')['x'];
                $ratio_y = $request->input('ratio')['y'];
                $log->type = "Change aspect ratio: $ratio_x:$ratio_y";
                $type = 'ratio';
            }

            $log->result_src = 'jobs/' . time() . '_' . uniqid() . '.' . substr($log->src, -3);
            $log->save();
                
            if ($user_id === $user->id) {
                Resize::dispatch($log->id, $ratio_x, $ratio_y, $type);
    
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
            ], 400);
        }

        if ($request->input('slug') || $request->input('id')) {
            $check = $this->checkSlugOrId($request);
            if (array_key_exists('error', $check)) return $check['json'];
            
            $user = $request->input('user');
            $log = new EditLog();
            $log->user_id = $user->id;
            $log->src = $check['src'];
            $log->video_id = $check['video_id'];
            $user_id = $check['user_id'];

            $start_time = $request->input('start_time');
            $end_time = $request->input('end_time');

            $log->type = "Clip: $start_time - $end_time";
            $log->result_src = 'jobs/' . time() . '_' . uniqid() . '.' . substr($log->src, -3);
            $log->save();

            if ($user_id === $user->id) {
                Clip::dispatch($log->id, $start_time, $end_time);
    
                return response()->json([
                    'job' => $log->id,
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Invalid request'
                ], 400);
            }  
        } else {
            return response()->json([
                'error' => 'Invalid request',
            ], 400);
        }

    }

    public function watermark(Request $request)
    {
        if ($request->input('slug') || $request->input('id')) {
            $check = $this->checkSlugOrId($request);
            if (array_key_exists('error', $check)) return $check['json'];
            
            $user = $request->input('user');
            $log = new EditLog();
            $log->user_id = $user->id;
            $log->src = $check['src'];
            $log->video_id = $check['video_id'];
            $log->result_src = 'jobs/' . time() . '_' . uniqid() . '.' . substr($log->src, -3);
            $log->type = "Add Watermark";
            $log->save();
            $user_id = $check['user_id'];

            if ($user_id === $user->id) {
                Watermark::put($log);
    
                return response()->json([
                    'job' => $log->id,
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Invalid request'
                ], 400);
            }  
        } else {
            return response()->json([
                'error' => 'Invalid request',
            ], 400);
        }

    }

    public function addFilter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:grayscale,sepia,vintage,negative,color_negative,cross_process',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ], 400);
        }

        if ($request->input('slug') || $request->input('id')) {
            $check = $this->checkSlugOrId($request);
            if (array_key_exists('error', $check)) return $check['json'];
            
            $user = $request->input('user');
            $log = new EditLog();
            $log->user_id = $user->id;
            $log->src = $check['src'];
            $log->video_id = $check['video_id'];
            $user_id = $check['user_id'];

            $type = $request->input('type');

            $log->type = "Add Filter: $type";
            $log->result_src = 'jobs/' . time() . '_' . uniqid() . '.' . substr($log->src, -3);
            $log->save();

            if ($user_id === $user->id) {
                ColorFilter::dispatch($log->id, $type);
    
                return response()->json([
                    'job' => $log->id,
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Invalid request'
                ], 400);
            }  
        } else {
            return response()->json([
                'error' => 'Invalid request',
            ], 400);
        }

    }

    public function addProgressBar(Request $request)
    {
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

            $color = $request->input('color');
            $height = $request->input('height');

            $log->type = "Add progress bar | Height=${height}, Color=${color}";

            $log->result_src = 'jobs/' . time() . '_' . uniqid() . '.' . substr($log->src, -3);
            $log->save();
            
                
            if ($user_id === $user->id) {
                ProgressBar::dispatch($log->id, $color, $height);
    
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

    public function getFrame(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'time_code' => 'required',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ], 400);
        }

        if ($request->input('slug') || $request->input('id')) {
            $user = $request->input('user');
            $check = $this->checkSlugOrId($request);
            if (array_key_exists('error', $check)) return $check['json'];

            $src = $check['src'];
            $user_id = $check['user_id'];

            $time_code = $request->input('time_code');

            $fileName = time() . '_' . uniqid() . '.jpg';

            if ($user_id === $user->id) {
                $media = FFMpeg::open($src);

                $duration = $media->getDurationInSeconds();

                $time_code = "$time_code:00";
        
                $start = \FFMpeg\Coordinate\TimeCode::fromString($time_code);
                $seconds = $start->toSeconds();

                if ($duration >= $seconds) {
                    $media->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($seconds))
                    ->save(storage_path('app/public') . '/' . "thumbnails/$fileName");

                    $file = file_get_contents(storage_path('app/public') . '/' . "thumbnails/$fileName");
                    $file = base64_encode($file);

                    unlink(storage_path('app/public') . '/' . "thumbnails/$fileName");

                    // return response()->download(
                    //     base64_encode(storage_path('app/public') . '/' . "thumbnails/$fileName")
                    // );
                    return response()->json([
                        'frame' => $file
                    ]);
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Timecode value is greater than video duration'
                    ], 400);    
                }
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

    public function makeGIF(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time'    => 'required',
            'end_time'      => 'required',
            'scale'         => 'required|in:240,320,640,720'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ], 400);
        }

        if ($request->input('slug') || $request->input('id')) {
            $user = $request->input('user');
            $check = $this->checkSlugOrId($request);
            if (array_key_exists('error', $check)) return $check['json'];

            $src = $check['src'];
            $user_id = $check['user_id'];

            $fileName = time() . '_' . uniqid() . '.gif';

            if ($user_id === $user->id) {
                $start_time = $request->input('start_time');
                $end_time = $request->input('end_time');
                $fps = $request->input('fps');
                $scale = $request->input('scale');

                $diff = strtotime($end_time) - strtotime($start_time);
                $start_time = "$start_time:00";
                
                $start = \FFMpeg\Coordinate\TimeCode::fromString($start_time);

                $media = FFMpeg::open($src);

                $dimensions = new \FFMpeg\Coordinate\Dimension($scale, 1);
                
                $res = $media
                    ->gif($start, $dimensions, $diff)
                    ->save(storage_path('app/public') . '/' . "gif/$fileName");

                $file = file_get_contents(storage_path('app/public') . '/' . "gif/$fileName");
                $file = base64_encode($file);

                unlink(storage_path('app/public') . '/' . "gif/$fileName");
                return response()->json([
                    'gif' => $file
                ]);
                // return response()->download(storage_path('app/public') . '/' . "gif/$fileName", $fileName);
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

    public function saveClipResize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'original'              => 'required|exists:videos,slug',
            'clip'                  => 'required|boolean',
            'resize'                => 'required|boolean',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ], 400);
        }

        $check = null;
        if (!$request->input('id')) {
            $request->merge(['slug' => $request->input('original')]);
        }
        
        $check = $this->checkSlugOrId($request);
        if (array_key_exists('error', $check)) return $check['json'];    
        
        $user = $request->input('user');
        $log = new EditLog();
        $log->user_id = $user->id;

        $log->src = $check['src'];
        $log->video_id = $check['video_id'];
        $user_id = $check['user_id'];

        $options = [];
        $clip = $request->input('clip');
        $resize = $request->input('resize');

        if ($clip) {
            $options['clip'] = true;
            $options['start_time'] = $request->input('start_time');
            $options['end_time'] = $request->input('end_time');
            $clip_type = "Clip: " . $options['start_time'] . ' - ' . $options['end_time'];
        }

        if ($resize) {
            $options['resize'] = true;
            if ($request->input('dimensions') && count($request->input('dimensions'))) {
                $options['ratio_x'] = $request->input('dimensions')['x'];
                $options['ratio_y'] = $request->input('dimensions')['y'];
                $resize_type = "Change size: " . $options['ratio_x'] . ':' . $options['ratio_y'];
                $options['type'] = 'resize';
            } else {
                $options['ratio_x'] = $request->input('ratio')['x'];
                $options['ratio_y'] = $request->input('ratio')['y'];
                $resize_type = "Change aspect ratio: " . $options['ratio_x'] . ':' . $options['ratio_y'];
                $options['type'] = 'ratio';
            }
        }

        $log_type = '';
        $log_type .= $request->input('clip') ? "$clip_type | " : '';
        $log_type .= $request->input('resize') ? "$resize_type | " : '';
        $log_type .= 'Save';
        $log->type = $log_type;
        
        $log->result_src = ($clip || $resize) ?
            'jobs/' . time() . '_' . uniqid() . '.' . substr($log->src, -3) :
            $check['src'];
        $log->save();
            
        if ($user_id === $user->id) {
            if ($clip || $resize) {
                ClipResize::dispatch($log->id, $options);
            }

            $video = Video::where('slug', $request->input('original'))->first();
            $video->progress = $log->id;
            $video->save();

            return response()->json([
                'job' => $log->id,
            ], 200);
        } else {
            return response()->json([
                'error'     => true,
                'message'   => 'Unauthorized'
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
        } else if ($request->input('original')) {
            $video = Video::where('slug', $request->input('original'))->first();
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
