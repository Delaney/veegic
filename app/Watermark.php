<?php

namespace App;

use App\Models\EditLog;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use ProtoneMedia\LaravelFFMpeg\Filters\WatermarkFactory;
use FFMpeg\Filters\AdvancedMedia\ComplexFilters;
use App\Enums\WatermarkPosition;

class Watermark
{
	private $path;
	
	public static function add($src)
	{
		$video_path = storage_path('app') . '/' . $src;
		$new_title = storage_path('app/public') . '/' . $src;
		$file = "app\\watermarks\\default.png";
		$watermark_path = storage_path($file);
		
		$command = "ffmpeg -i $video_path -vf drawtext=text='Veegic':x=w-tw-10:y=10:fontsize=24:fontcolor=white -y $new_title";
		$command = "ffmpeg -i $video_path -i $watermark_path -filter_complex \"[1][0]scale2ref=w=oh*mdar:h=ih*0.25[logo][video];[video][logo]overlay=W-w-10:10\" -codec:a copy $new_title";
		
		shell_exec($command);

		return $new_title;
	}

	public static function put(EditLog $log, $file = null, $options = [])
	{
		$src = $log->src;
		$result_src = $log->result_src;
		$video_path = storage_path('app') . '/' . $src;
		$new_title = storage_path('app') . '/' . $result_src;
		if (!$file) $file = 'default.png';

		$file = "app\\watermarks\\$file";
		$watermark_path = storage_path($file);

		$x_offset = 10;
		if (array_key_exists('x_offset', $options)) {
			$x_offset = (integer) $options['x_offset'];
		}

		$y_offset = 10;
		if (array_key_exists('y_offset', $options)) {
			$y_offset = (integer) $options['y_offset'];
		}

		$position = WatermarkPosition::top_right;
		if (array_key_exists('position', $options)) {
			$position = $options['position'];
		}

		switch($position) {
			case WatermarkPosition::top_left:
				// Top Left
				$command = "ffmpeg -i $video_path -i $watermark_path -filter_complex \"[1][0]scale2ref=w=oh*mdar:h=ih*0.25[logo][video];[video][logo]overlay=$x_offset:$y_offset\" -codec:a copy $new_title";
				break;
			
			case WatermarkPosition::top_right:
				// Top Right
				$command = "ffmpeg -i $video_path -i $watermark_path -filter_complex \"[1][0]scale2ref=w=oh*mdar:h=ih*0.25[logo][video];[video][logo]overlay=W-w-$x_offset:$y_offset\" -codec:a copy $new_title";
				break;
		
			case WatermarkPosition::bottom_left:
				// Bottom Left
				$command = "ffmpeg -i $video_path -i $watermark_path -filter_complex \"[1][0]scale2ref=w=oh*mdar:h=ih*0.25[logo][video];[video][logo]overlay=$x_offset:H-h-$y_offset\" -codec:a copy $new_title";
				break;
		
			case WatermarkPosition::bottom_right:
				// Bottom Right
				$command = "ffmpeg -i $video_path -i $watermark_path -filter_complex \"[1][0]scale2ref=w=oh*mdar:h=ih*0.25[logo][video];[video][logo]overlay=W-w-$x_offset:H-h-$y_offset\" -codec:a copy $new_title";
				break;
		
			case WatermarkPosition::center:
				// Center
				$command = "ffmpeg -i $video_path -i $watermark_path -filter_complex \"[1][0]scale2ref=w=oh*mdar:h=ih*0.25[logo][video];[video][logo]overlay=(W-w)/2:(H-h)/2\" -codec:a copy $new_title";
				break;
				
			default:
				$command = "ffmpeg -i $video_path -i $watermark_path -filter_complex \"[1][0]scale2ref=w=oh*mdar:h=ih*0.25[logo][video];[video][logo]overlay=W-w-$x_offset:$y_offset\" -codec:a copy $new_title";
					break;
		}
		shell_exec($command);
		
		$log->progress = 100;
		$log->save();

		return $new_title;
	}
}