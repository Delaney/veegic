<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use App\Models\Subtitles;
use App\Models\Queue\TranscribeJob;
use Aws\TranscribeService\TranscribeServiceClient;
use App\AWS;
use App\Models\Video;
use App\Models\EditLog;
use App\Subtitle;

class Transcribe implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $url;
    private $log_id;
    private $video_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($log_id, $slug)
    {
        $video = Video::where('slug', $slug)->first();
        $this->video_id = $video->id;
        $this->url = urldecode($video->s3_url);
        $this->log_id = $log_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $video = Video::find($this->video_id);
        $log = EditLog::find($this->log_id);

        $awsTranscribeClient = new TranscribeServiceClient(([
            'region'        => config('aws.region'),
            'version'       => 'latest',
            'credentials'   => [
                'key'       => config('aws.access_key'),
                'secret'    =>  config('aws.secret_access_key')
            ]
        ]));

        if ($log->src == NULL) {
            $transcriptionResult = $awsTranscribeClient->startTranscriptionJob([
                'LanguageCode'  => 'en-US',
                'Media' => [
                    'MediaFileUri'  => $this->url,
                ],
                'TranscriptionJobName' => $log->data,
            ]);

            $log->src = $transcriptionResult->get('TranscriptionJob')['TranscriptionJobStatus'];
            $log->save();
        }
        if (!$log->complete) {
            $status = array();
            $status = $awsTranscribeClient->getTranscriptionJob([
                'TranscriptionJobName' => $log->data
            ]);

            $log->src = $status->get('TranscriptionJob')['TranscriptionJobStatus'];

            if ($status->get('TranscriptionJob')['TranscriptionJobStatus'] == 'COMPLETED') {
                $log->complete = true;
                $log->save();
                $url = $status->get('TranscriptionJob')['Transcript']['TranscriptFileUri'];
                $client = new \GuzzleHttp\Client();
                $arr_data = [];
                
                $response = $client->request('GET', $url);
                if ($response->getStatusCode() == 200) {
                    $arr_data = json_decode($response->getBody(), true);
                }
                $items = $arr_data['results']['items'];
                
                $sub = $video->subtitles;
                $subtitles = (new Subtitle)->createSRT($items);

                $txt = fopen(storage_path("app/$sub->src"), "w") or die("Unable to open file!");
                fwrite($txt, $subtitles);
                fclose($txt);
            }  else {
                sleep(5);
                self::dispatch($log->id, $video->slug)->onQueue('Subtitles');
            }
        }
    }
}
