<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\EditLog;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ClipResize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $log_id;
    private $options = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($log_id, $options)
    {
        $this->log_id = $log_id;
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $o = $this->options;

        $log = EditLog::find($this->log_id);
        $media = FFMpeg::open($log->src);

        $clipFilter = null;
        if (array_key_exists('clip', $o) && $o['clip']) {
            $start_time = $o['start_time'] . ':00';
            $diff = strtotime($o['end_time']) - strtotime($o['start_time']);
            
            $start = \FFMpeg\Coordinate\TimeCode::fromString($start_time);
            $duration = \FFMpeg\Coordinate\TimeCode::fromSeconds($diff);        
            $clipFilter = new \FFMpeg\Filters\Video\ClipFilter($start, $duration);
        }

        $resizeFilter = null;
        if (array_key_exists('resize', $o) && $o['resize']) {
            $dim = $media
                ->getVideoStream()
                ->getDimensions();

            if ($o['type'] == 'ratio') {
                $dimensions = Resize::getNewSize($dim->getWidth(), $dim->getHeight(), $o['ratio_x'], $o['ratio_y']);
            } else {
                $dimensions = new \stdClass();
                $dimensions->width = ($o['ratio_x'] % 2 == 0) ? $o['ratio_x'] : $o['ratio_x'] + 1;
                $dimensions->height = ($o['ratio_y'] % 2 == 0) ? $o['ratio_y'] : $o['ratio_y'] + 1;
            }
            $d = new \FFMpeg\Coordinate\Dimension($dimensions->width, $dimensions->height);
            $resizeFilter = new \FFMpeg\Filters\Video\ResizeFilter($d);
        }

        $media = $clipFilter ? $media->addFilter($clipFilter) : $media;
        $media = $resizeFilter ? $media->addFilter($resizeFilter) : $media;
            
        $media->export()
            ->onProgress(function ($percentage) use ($log) {
                // \Log::info("CR: {$percentage}% done");
                $log->progress = $percentage;
                $log->save();
            })
            ->toDisk('local')
            ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame'))
            ->save($log->result_src);
    }
}
