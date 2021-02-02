<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Translator;
use stdClass;

class TranslatorController extends Controller
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
        $sentences = array_map(function($obj) {
            $o = new \stdClass;
            $o->Text = $obj['sentence'];
            return $o;
        }, $arr);

        $resultArray = [];
        
        $translate = new Translator;
        $to = $request->input('language');
        $result = $translate->make($to, $sentences);

        $n = 0;
        foreach($arr as $obj) {
            $o = new stdClass();
            $o->index = $obj['index'];
            $o->start_time = $obj['start_time'];
            $o->end_time = $obj['end_time'];
            if ($obj['sentence']) {
                $o->sentence = $result[$n]->translations[0]->text;
                $n++;
            } else {
                $o->sentence = $obj['sentence'];
            }

            array_push($resultArray, $o);
        }

        return $resultArray;
    }

    public function getLanguages(Request $request) {
        $translate = new Translator;
        $result = $translate->languages();

        return $result;
    }
}
