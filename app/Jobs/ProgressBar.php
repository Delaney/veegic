<?php
namespace App\Jobs;

use App\Models\EditLog;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProgressBar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	private $log_id;

	private $color;
	private $height;
	
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($log_id, $color, $height)
    {
		$this->log_id = $log_id;
		$this->color = $color ? $color : 'white';
		$this->height = $height ? $height : '10';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
		$log = EditLog::find($this->log_id);
		$color = $this->color;
		$height = $this->height;

		$media = FFMpeg::open($log->src);

		$width = $media
            ->getVideoStream()
			->getDimensions()
			->getWidth();

		$duration = $media->getDurationInSeconds();
		
		$customFilter = new \FFMpeg\Filters\Video\CustomFilter("color=c=${color}:s=${width}x${height}[bar];[0][bar]overlay=-w+(w/${duration})*t:H-h:shortest=1");

        $media
            ->export()
            ->addFilter($customFilter)
            ->onProgress(function ($percentage, $remaining, $rate) use ($log) {
                $log->progress = $percentage;
                $log->save();
                // \Log::info("Adding Progress Bar: {$percentage}% done, {$remaining} seconds left at rate: {$rate}");
            })
            ->toDisk('local')
            ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame'))
            ->save($log->result_src);
    }
}
