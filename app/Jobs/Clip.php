<?php
namespace App\Jobs;

use App\Models\EditLog;
// use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Format\Video\X264;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Clip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $log_id;

	private $start_time;
	
	private $end_time;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($log_id, $start_time, $end_time = null)
    {
        $this->log_id = $log_id;
		$this->start_time = $start_time;
		$this->end_time = $end_time;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $log = EditLog::find($this->log_id);

        $videoPath = storage_path('app') . '\\' . (str_replace('/','\\', $log->src));
        // $videoPath = str_replace('/','\\', $log->src);
        
        $newTitle = storage_path('app') . '\\' . $log->result_src;
		
        // $clipFilter = new \FFMpeg\Filters\Video\ClipFilter($start);

        // FFMpeg::open($videoPath)
        //     ->addFilter($clipFilter)
		// 	->export()
        //     ->clip($start, $duration)
        //     ->onProgress(function ($percentage, $remaining, $rate) {
        //         \Log::info("{$percentage}% done, {$remaining} seconds left at rate: {$rate}");
        //     })
        //     ->toDisk('local')
        //     ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame'))
        //     ->save(str_replace('/','\\', $log->result_src));

        // $duration = "ffprobe -v error -select_streams v:0 -show_entries stream=duration -of default=noprint_wrappers=1:nokey=1 $videoPath";
        $cmd = "ffprobe -i $videoPath -show_format -v quiet | sed -n 's/duration=//p'";
        $end_time = ($this->end_time) ? $this->end_time : $this->toTimecode(shell_exec($cmd));
        // \Log::info($end_time);
        // \Log::info("\n");

        $command = "ffmpeg -i $videoPath -ss $this->start_time -to $end_time -c:v copy -c:a copy $newTitle";

        try {
            \Log::info($command);
            \Log::info("\n");
            shell_exec($command);
            return true;
        } catch (\Exception $ex) {
            \Log::error("\n\n" . $ex . "\n\n");
        }
    }

    protected function toTimecode($seconds) {
        $t = round($seconds);
        return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60);
    }
}
