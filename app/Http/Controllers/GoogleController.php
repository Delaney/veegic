<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Google\Cloud\Translate\V2\TranslateClient;
use stdClass;

class GoogleController extends Controller
{
    public function translate(Request $request) {
        $validator = Validator::make($request->all(), [
            'language'      => 'required',
            'srtObjects'    => 'required',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'error' => 'invalid_input',
                'message' => $error
            ]);
        }

        $arr = $request->input('srtObjects');
        $translate = new TranslateClient([
            'key' => config('services.google.key')
        ]);
        $target = $request->input('language');

        $resultArray = [];

        $sentences = array_map(function($obj) {
            return $obj['sentence'];
        }, $arr);

        $result = $translate->translateBatch($sentences, [
            'target'    => $target,
            'format'    => 'text',
        ]);

        $n = 0;
        foreach($arr as $obj) {
            $o = new stdClass();
            $o->index = $obj['index'];
            $o->start_time = $obj['start_time'];
            $o->end_time = $obj['end_time'];
            if ($obj['sentence']) {
                $o->sentence = $result[$n]['text'];
                $n++;
            } else {
                $o->sentence = $obj['sentence'];
            }

            array_push($resultArray, $o);
        }

        return $resultArray;
    }

    public function getLanguages(Request $request) {
        $translate = new TranslateClient([
            'key' => config('services.google.key')
        ]);
        $languages = $translate->localizedLanguages();

        return $languages;
    }
}
