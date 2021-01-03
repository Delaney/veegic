<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Coordinate\Dimension;

class TrimVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $video_id;
    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($video_id, $data)
    {
        $this->video_id = $video_id;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $video = Video::find($this->video_id);

        $videoPath = storage_path('app') . '\\' . (str_replace('/','\\', $video->src));
        $newTitle = storage_path('app') . '\jobs\\' . uniqid() . '.' . $video->extension;

        $ratio = $this->data['ratio'];

        FFMpeg::open(str_replace('/','\\', $video->src))
            ->filters()
            ->resize($ratio)
            ->export()
            ->toDisk('local')
            ->inFormat(new \FFMpeg\Format\Video\X264)
            ->save($newTitle);
    }
}
