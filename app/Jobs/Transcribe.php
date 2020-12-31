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
use App\Subtitle;

class Transcribe implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $video_id;
    private $url;
    private $job_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($job_id, $slug)
    {
        $video = Video::where('slug', $slug)->first();
        $this->video_id = $video->id;
        $this->url = urldecode($video->s3_url);
        $this->job_id = $job_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $video = Video::find($this->video_id);
        $job = TranscribeJob::find($this->job_id);
        $awsTranscribeClient = new TranscribeServiceClient(([
            'region'        => AWS::credentials()->region,
            'version'       => 'latest',
            'credentials'   => [
                'key'       => AWS::credentials()->access_key,
                'secret'    =>  AWS::credentials()->secret_access_key
            ]
        ]));

        if ($job->status == NULL) {
            $transcriptionResult = $awsTranscribeClient->startTranscriptionJob([
                'LanguageCode'  => 'en-US',
                'Media' => [
                    'MediaFileUri'  => $this->url,
                ],
                'TranscriptionJobName' => $job->job_name,
            ]);

            $job->status = $transcriptionResult->get('TranscriptionJob')['TranscriptionJobStatus'];
            $job->save();
        }
        if (!$job->complete) {
            $status = array();
            $status = $awsTranscribeClient->getTranscriptionJob([
                'TranscriptionJobName' => $job->job_name
            ]);

            $job->status = $status->get('TranscriptionJob')['TranscriptionJobStatus'];

            if ($status->get('TranscriptionJob')['TranscriptionJobStatus'] == 'COMPLETED') {
                $job->complete = true;
                $job->url = $status->get('TranscriptionJob')['Transcript']['TranscriptFileUri'];
                $job->save();
            }  else {
                self::dispatch($job->id, $video->slug);
            }
        }
    }
}
