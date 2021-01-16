<?php

namespace App\Jobs;

use App\Models\EditLog;
use App\Models\Video;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BurnSRT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $log_id;
    private $srt;
    private $options;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($log_id, $options = [], $srt = null)
    {
        $this->log_id = $log_id;
        $this->srt = $srt;
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $log = EditLog::find($this->log_id);
        $video = Video::find($log->video_id);

        if ($this->srt) {
            $subtitlePath = 'storage/app/' . $this->srt;
        } else {
            $subtitle = $video->subtitles;
    
            if (!$subtitle) return false;
    
            $subtitlePath = 'storage/app/' . $subtitle->src;
        }
        
        /* LOCAL */
        // $videoPath = storage_path('app') . '\\' . (str_replace('/','\\', $video->src));
        // $newTitle = storage_path('app') . '\\' . (str_replace('/','\\', $log->result_src));
        
        /* CLOUD */
        $videoPath = storage_path('app') . '/' . $video->src;
        $newTitle = storage_path('app') . '/' . $log->result_src;

        $optionStr = '';
        if (count($this->options)) $optionStr = $this->setOptions($this->options);

        $fontdir = 'fontsdir=' . storage_path('fonts');
        $fontdir = 'fontsdir=storage/fonts';

        $command = "ffmpeg -i $videoPath -vf \"subtitles=$subtitlePath:$fontdir:$optionStr\" -c:a copy $newTitle";
        
        try {
            // \Log::info($command);
            // \Log::info("\n");
            shell_exec($command);
            // return true;
        } catch (\Exception $ex) {
            \Log::error("\n\n" . $ex . "\n\n");
        }
    }

    private function setOptions($options) {
        if (count($options)) {
            $str = '';
            
            if (array_key_exists('font_name', $options) && $options['font_name']) {
                $str = $str . 'fontname=' . $options['font_name'] . ',';
            }
            if (array_key_exists('font_size', $options) && $options['font_size']) {
                $str = $str . 'Fontsize=' . $options['font_size'] . ',';
            }
            if (array_key_exists('font_color', $options) && $options['font_color']) {
                $bgr = substr($options['font_color'],4,2) . substr($options['font_color'],2,2) . substr($options['font_color'],0,2);
                $color = hexdec($bgr);
                $str = $str . 'PrimaryColour=' . $color . ',';
            }
            
            $result = "force_style='$str'";
            return $result;
        }
    }
}
