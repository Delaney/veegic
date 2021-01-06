<?php

namespace App;

use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use ProtoneMedia\LaravelFFMpeg\Filters\WatermarkFactory;

class Watermark
{
	private $path;
	
	public static function add($src)
	{
		$videoPath = storage_path('app') . '/' . $src;
		$newTitle = storage_path('app/public') . '/' . $src;
		
		$command = "ffmpeg -i $videoPath -vf drawtext=text='Veegic':x=10:y=H-th-10:fontsize=36:fontcolor=white -y $newTitle";
		
		shell_exec($command);

		return $newTitle;
	} 
}