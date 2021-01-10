<?php
namespace App\Jobs;

use App\Models\EditLog;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use stdClass;

class Resize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $log_id;

    private $ratio;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($log_id, $ratio)
    {
        $this->log_id = $log_id;
        $this->ratio = $ratio;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $log = EditLog::find($this->log_id);
        $videoPath = storage_path('app') . '/' . $log->src;

        $cmd = "ffprobe -v error -show_entries stream=width,height -of csv=p=0:s=x $videoPath";
        $resolution = shell_exec($cmd);
        $arr = explode('x', $resolution);
        $dimensions = $this->getNewSize(trim($arr[0]), trim($arr[1]), $this->ratio);

        FFMpeg::open($log->src)
            ->export()
            ->resize($dimensions->width, $dimensions->height)
            ->onProgress(function ($percentage, $remaining, $rate) {
                \Log::info("{$percentage}% done, {$remaining} seconds left at rate: {$rate}");
            })
            ->toDisk('local')
            ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame'))
            ->save($log->result_src);
    }

    public function getNewSize($width, $height, $ratio) {
        $obj = new stdClass();
        if ($ratio['x'] > $ratio['y']) {
            $height = ceil(($width * $ratio['y']) / $ratio['x']);
            $obj->width = $width;
            $obj->height = ($height % 2 == 0) ? $height : $height + 1;
        } else if ($ratio['y'] > $ratio['x']) {
            $width = ceil(($height * $ratio['x']) / $ratio['y']);
            $obj->height = $height;
            $obj->width = ($width % 2 == 0) ? $width : $width + 1;
        } else {
            $obj->width = $width;
            $obj->height = $height;
        }

        return $obj;
    }
}
