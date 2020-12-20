<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Video;
use App\Models\Subtitles;
use Aws\TranscribeService\TranscribeServiceClient;

class SubtitlesController extends Controller
{
    public function transcribe(Request $request)
    {
        $slug = $request->input('slug');
        $file_only = $request->input('file_only');
        $video = Video::where('slug', $slug)->first();

        if (!$video->subtitles && !Storage::exists( $video->subtitles->src )) {
            \Log::info("Does not exist");
            
            $video_url = $video->s3_url;
            $awsTranscribeClient = new TranscribeServiceClient(([
                'region'        => $this->region,
                'version'       => 'latest',
                'credentials'   => [
                    'key'       => $this->access_key,
                    'secret'    =>  $this->secret_access_key
                ]
            ]));
    
            $job_id = uniqid();
            $transcriptionResult = $awsTranscribeClient->startTranscriptionJob([
                'LanguageCode'  => 'en-US',
                'Media' => [
                    'MediaFileUri'  => $video_url,
                ],
                'TranscriptionJobName' => $job_id,
            ]);
    
            $status = array();
            while(true) {
                $status = $awsTranscribeClient->getTranscriptionJob([
                    'TranscriptionJobName' => $job_id
                ]);
                if ($status->get('TranscriptionJob')['TranscriptionJobStatus'] == 'COMPLETED') {
                    break;
                }
    
                sleep(5);
            }
    
            $url = $status->get('TranscriptionJob')['Transcript']['TranscriptFileUri'];
    
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            $data = curl_exec($curl);
            if (curl_errno($curl)) {
                $error_msg = curl_error($curl);
                echo $error_msg;
            }
            curl_close($curl);
            $arr_data = json_decode($data);
    
            $items = $arr_data->results->items;
            $subtitles = $this->createSRT($items);
            $title = $video->title;
            
            $fileName = $title . '.srt';
            $txt = fopen($fileName, "w") or die("Unable to open file!");
            fwrite($txt, $subtitles);
            fclose($txt);
    
            $path = Storage::putFileAs('subtitles', $fileName, $fileName);

            if (!$video->subtitles) {
                $model = new Subtitles;
                $model->video_id = $video->id;
                $model->title = $title;
                $model->src = $path;
                $model->save();
            } else {
                $sub = Subtitles::where('video_id', $video->id)->first();
                $sub->src = $path;
                $sub->save();
            }
    
            
    
        }

        $headers = array(
            'Content-Disposition' => 'attachment;filename=subtitles.srt',
            'Content-Type' => 'application/octet-stream'
        );
 
        return response()->download($fileName, $fileName, $headers);
    }
}
