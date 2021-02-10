<?php

namespace App\Jobs;

use App\Models\EditLog;
use App\Models\Video;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Filesystem\MediaCollection;

class ColorFilter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $log_id;
	private $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($log_id, $type)
    {
        $this->log_id = $log_id;
		$this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $log = EditLog::find($this->log_id);
		$type = $this->type;

		switch (strtolower($type)) {
			case 'grayscale':
				$customFilter = new \FFMpeg\Filters\Video\CustomFilter("hue=s=0");
				break;

			case 'sepia':
				$customFilter = new \FFMpeg\Filters\Video\CustomFilter("colorchannelmixer=.393:.769:.189:0:.349:.686:.168:0:.272:.534:.131[colorchannelmixed]; [colorchannelmixed]eq=1.0:0:1.3:2.4:1.0:1.0:1.0:1.0");
				break;

			case 'vintage':
				$customFilter = new \FFMpeg\Filters\Video\CustomFilter("curves=vintage");
				break;

			case 'negative':
				$customFilter = new \FFMpeg\Filters\Video\CustomFilter("curves=negative");
				break;

			case 'color_negative':
				$customFilter = new \FFMpeg\Filters\Video\CustomFilter("curves=color_negative");
				break;

			case 'cross_process':
				$customFilter = new \FFMpeg\Filters\Video\CustomFilter("curves=cross_process");
				break;

			default:
				break;
		}

        FFMpeg::open($log->src)
            ->export()
            ->addFilter($customFilter)
            ->onProgress(function ($percentage, $remaining, $rate) use ($log, $type) {
                $log->progress = $percentage;
                $log->save();
                // \Log::info("Adding $type: {$percentage}% done, {$remaining} seconds left at rate: {$rate}");
            })
            ->toDisk('local')
            ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame'))
            ->save($log->result_src);
    }
}
