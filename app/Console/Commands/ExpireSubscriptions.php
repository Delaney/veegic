<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $timezone = config('app.timezone');
        $date = Carbon::now($timezone)->subDay()->format('Y-m-d 00:00:00');
        echo "Checking subscriptions: $date";

        $subscriptions = Subscription::where('expire_at', $date)->get();

        foreach ($subscriptions as $sub) {
            // \Log::info((array) $sub);
            $sub->type = 'free';
            $sub->save();
        }
    }
}
