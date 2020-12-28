<?php

namespace App\Jobs;

use App\Models\Video;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class BurnSRT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $video_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($video_id)
    {
        $this->video_id = $video_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $video = Video::find($this->video_id);
        $subs = $video->subtitles;

        if (!$subs) return false;

        $videoPath = storage_path('app') . '\\' . (str_replace('/','\\', $video->src));
        $subtitlePath = 'storage/app/' . $subs->src;
        $newTitle = storage_path('app') . '\uploads\jobs\\' . uniqid() . '.' . $video->extension;

        $command = "ffmpeg -i $videoPath -vf subtitles=$subtitlePath -c:a copy $newTitle";
        $path = base_path() . '\storage\app\subtitles';
        $path = (str_replace('\\','/', $path));
        // $mv = "cd $path";
        // \Log::info($mv);
        // \Log::info($command);
        
        // try {
        //     \Log::info(shell_exec($command));
        // } catch (\Exception $ex) {
        //     \Log::error("\n\n" . $ex . "\n\n");
        // }

        // FFMpeg::fromDisk('local')
        //     ->open(str_replace('/','\\', $path))
        //     // ->addFilter(function ($filters) {
        //     //     $filters->custom($subFilter);
        //     // })
        //     ->addFilter($filter)
        //     // ->filters()
        //     // ->custom("-i $subtitlePath -scodec mov_text -metadata:s:s:0 language=eng")
        //     // ->onProgress(function ($p, $rem) {
        //     //     \Log::info("Burned $p%, $rem seconds left\n");
        //     // })
        //     ->export()
        //     ->toDisk('local')
        //     ->inFormat(new \FFMpeg\Format\Video\X264)
        //     ->save($newTitle);
    }
}
