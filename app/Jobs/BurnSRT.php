<?php

namespace App\Jobs;

use App\Models\EditLog;
use App\Models\Video;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BurnSRT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $log_id;
    private $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($log_id)
    {
        $this->log_id = $log_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $log = EditLog::find($this->log_id);
        $video = Video::find($log->video_id);
        $subtitle = $video->subtitles;

        if (!$subtitle) return false;

        $videoPath = storage_path('app') . '\\' . (str_replace('/','\\', $video->src));
        // $videoPath = storage_path('app') . '/' . $video->src;

        $subtitlePath = 'storage/app/' . $subtitle->src;
        // $subtitlePath = storage_path('app') . '\\' . (str_replace('/','\\', $subtitle->src));
        
        $newTitle = storage_path('app') . $log->result_src;
        // $newTitle = storage_path('app') . '/jobs/' . $result;

        $command = "ffmpeg -i $videoPath -vf subtitles=$subtitlePath -c:a copy $newTitle";
        
        try {
            \Log::info($command);
            \Log::info("\n");
            shell_exec($command);
            return true;
        } catch (\Exception $ex) {
            \Log::error("\n\n" . $ex . "\n\n");
        }
    }
}
