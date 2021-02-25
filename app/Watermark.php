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
		
		$command = "ffmpeg -i $videoPath -vf drawtext=text='Veegic':x=w-tw-10:y=10:fontsize=24:fontcolor=white -y $newTitle";
		
		shell_exec($command);

		return $newTitle;
	}
	
	public static function padd($src)
	{
		$video_path = storage_path('app') . '/' . $src;
		$new_title = storage_path('app/public') . '/' . $src;
		// $new_title = 'public/' . $src;

		// $font_dir = str_replace('\\', '/', storage_path('fonts\roboto.ttf'));
		// $font_dir = storage_path('fonts\roboto.ttf');
		// $font_dir = 'C\:\\localdev\\video\\api\\storage\\fonts\\roboto.ttf';
		$font_dir = 'C:\\localdev\\video\\api\\storage\\fonts\\roboto.ttf';
		
		// Top Left
		// $command = "ffmpeg -i $video_path -vf drawtext=fontfile='{$font_dir}':text='Veegic':x=w-tw-10:y=10:fontsize=24:fontcolor=white -y $new_title";
		// $command = "ffmpeg -i $video_path -vf drawtext=fontfile='{$font_dir}':text='Veegic':x=w-tw-10:y=10:fontsize=24:fontcolor=white -y $new_title";
		// \Log::debug($command);
		$command = "ffmpeg -i $video_path -vf drawtext=fontfile='C:\\localdev\\video\\api\\storage\\fonts\\roboto.ttf':text='Veegic':x=w-tw-10:y=10:fontsize=24:fontcolor=white -y $new_title";
		\Log::debug($command);

		// $command = "ffmpeg -i $video_path -vf drawtext=text='Veegic':x=w-tw-10:y=10:fontsize=24:fontcolor=white -y $new_title";
		
		// $command = "ffmpeg -i $video_path -vf drawtext=text='Veegic':x=w-tw-10:y=10:fontsize=24:fontcolor=white -y $new_title";
		
		// $command = "ffmpeg -i $video_path -vf drawtext=text='Veegic':x=w-tw-10:y=10:fontsize=24:fontcolor=white -y $new_title";

		// $customFilter = new \FFMpeg\Filters\Video\CustomFilter("drawtext=fontfile={$font_dir}:x=w-tw-25:y=10:fontsize=30:fontcolor=white:text='Veegic'");
		

        // FFMpeg::open($src)
        //     ->export()
        //     ->addFilter($customFilter)
        //     ->onProgress(function ($percentage, $remaining, $rate) {
        //         \Log::info("Watermark: {$percentage}% done, {$remaining} seconds left at rate: {$rate}");
        //     })
        //     ->toDisk('local')
        //     ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame'))
        //     ->save($new_title);
		
		shell_exec($command);

		return $new_title;
	}

	public static function put($src, $file = null)
	{
		$new_title = 'app/public/' . $src;
		if (!$file) $file = 'default.png';

		$file = "watermarks\\$file";
		$watermarkPath = storage_path($file);

		FFMpeg::open($src)
			// ->addFilter(function ($filters) use ($watermarkPath) {
			// 	$filters->watermark($watermarkPath, [
			// 		'position'	=> 'absolute',
			// 		'top'		=> 25,
			// 		'right'		=> 25
			// 	]);
			// })
			->addWatermark(function(WatermarkFactory $watermark) use ($file) {
				$watermark->open($file)
					->right(25)
					->top(25);
			})
			->export()
			->toDisk('local')
		    ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame'))
            ->save($new_title);
	}
}