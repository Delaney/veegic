<?php
namespace App\Jobs;

use App\Models\EditLog;
// use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\X264;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Resize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $log_id;

    private $dimensions;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($log_id, $dimensions)
    {
        $this->log_id = $log_id;
        $this->dimensions = $dimensions;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $log = EditLog::find($this->log_id);
        $videoPath = str_replace('/','\\', $log->src);

        FFMpeg::open($videoPath)
            ->export()
            ->resize($this->dimensions['width'], $this->dimensions['height'])
            ->onProgress(function ($percentage, $remaining, $rate) {
                \Log::info("{$percentage}% done, {$remaining} seconds left at rate: {$rate}");
            })
            ->toDisk('local')
            ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame'))
            ->save(str_replace('/','\\', $log->result_src));
    }
}
