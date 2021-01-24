<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ClearLocalJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'local:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Max time to keep local job files, in minutes
     * 
     * @var int
     */
    protected static $max_time  = ((6) * 60);

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $files = Storage::disk('local')->files('jobs');
        $now = date('Y-m-d H:m:s');

        foreach ($files as $file) {
            $name = explode('_', $file)[0];
            $name = explode('/', $name)[1];
            $nowObj = strtotime($now);
            $diff = ($nowObj - $name)/60;
            $path = storage_path('app/' . $file);
            
            if ($diff > self::$max_time) {
                unlink($path);
            }
        }
    }
}
