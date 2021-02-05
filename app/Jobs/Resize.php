<?php
namespace App\Jobs;

use App\Models\EditLog;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use stdClass;

class Resize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $log_id;
    private $ratioX;
    private $ratioY;
    private $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($log_id, $ratioX, $ratioY, $type)
    {
        $this->log_id = $log_id;
        $this->ratioX = $ratioX;
        $this->ratioY = $ratioY;
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
        $videoPath = storage_path('app') . '/' . $log->src;

        $cmd = "ffprobe -v error -show_entries stream=width,height -of csv=p=0:s=x $videoPath";
        $resolution = shell_exec($cmd);
        $arr = explode('x', $resolution);
        if ($this->type == 'ratio') {
            $dimensions = self::getNewSize(trim($arr[0]), trim($arr[1]), $this->ratioX, $this->ratioY);
        } else {
            $dimensions = new StdClass();
            $dimensions->width = ($this->ratioX % 2 == 0) ? $this->ratioX : $this->ratioX + 1;
            $dimensions->height = ($this->ratioY % 2 == 0) ? $this->ratioY : $this->ratioY + 1;
        }

        FFMpeg::open($log->src)
            ->export()
            ->resize($dimensions->width, $dimensions->height)
            ->onProgress(function ($percentage, $remaining, $rate) use ($log) {
                $log->progress = $percentage;
                $log->save();
                // \Log::info("Resizing: {$percentage}% done, {$remaining} seconds left at rate: {$rate}");
            })
            ->toDisk('local')
            ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame'))
            ->save($log->result_src);
    }

    public static function getNewSize($width, $height, $ratioX, $ratioY) {
        $obj = new stdClass();
        if ($ratioX > $ratioY) {
            $height = ceil(($width * $ratioY) / $ratioX);
            $obj->width = $width;
            $obj->height = ($height % 2 == 0) ? $height : $height + 1;
        } else if ($ratioY > $ratioX) {
            $width = ceil(($height * $ratioX) / $ratioY);
            $obj->height = $height;
            $obj->width = ($width % 2 == 0) ? $width : $width + 1;
        } else {
            $obj->width = $width;
            $obj->height = $width;
        }

        return $obj;
    }
}
