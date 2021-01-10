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

        // $videoPath = storage_path('app') . '\\' . (str_replace('/','\\', $log->src));
        // $newTitle = storage_path('app') . '\\' . $log->result_src;

        $videoPath = storage_path('app') . '/' . $log->src;
        $newTitle = storage_path('app') . '/' . $log->result_src;
		
        $cmd = "ffprobe -i $videoPath -show_format -v quiet | sed -n 's/duration=//p'";
        $end_time = ($this->end_time) ? $this->end_time : $this->toTimecode(shell_exec($cmd));
        $command = "ffmpeg -ss $this->start_time -i $videoPath -vcodec copy -to $end_time $newTitle";

        try {
            // \Log::info($command);
            // \Log::info("\n");
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
