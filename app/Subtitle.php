<?php

namespace App;

use Carbon\Carbon;
use stdClass;

class Subtitle
{
	public function createSRT($items)
    {
        $result = '';
        $start_time = '';
        $end_time = '';
        $sentence = '';
        $n = 1;
        $t = 1;
        $wtb = 7;
        $len = count($items);

        $res = [];
        $obj = new stdClass();

        for ($i = 0; $i < $len; $i++) {
            if ($items[$i]['type'] == 'pronunciation') {
                if ($start_time == '') {
                    $start_time = $items[$i]['start_time'];
                }
                $end_time = $items[$i]['end_time'];
                $sentence = $sentence . $items[$i]['alternatives'][0]['content'] . ' ';

                $obj->index = $n;
                $obj->start_time = $start_time;
                $obj->end_time = $end_time;
                $obj->sentence = $sentence;

                $t++;
            } else if (
                $items[$i]['type'] == 'punctuation' &&
                    $items[$i]['alternatives'][0]['content'] == '.'
            ) {
                $result = $result . $n . "\n";
                $result = $result . $this->formatTime($start_time) . ' --> ' . $this->formatTime($end_time) . "\n" . $sentence . "\n\n";

                $obj->index = $n;
                $obj->start_time = $start_time;
                $obj->end_time = $end_time;
                $obj->sentence = $sentence;

                $sentence = '';
                $start_time = '';
                $n++;
                $t = 1;
                array_push($res, $obj);
                $obj = new stdClass();
            }
            
            if ($t > $wtb) {
                $result = $result . $n . "\n";
                $result = $result . $this->formatTime($start_time) . ' --> ' . $this->formatTime($end_time) . "\n" . $sentence . "\n\n";

                $obj->index = $n;
                $obj->start_time = $start_time;
                $obj->end_time = $end_time;
                $obj->sentence = $sentence;

                $sentence = "";
                $start_time = '';
                $n++;
                $t = 1;
                array_push($res, $obj);
                $obj = new stdClass();
            }
        }

        return [
            'json'  => $res,
            'srt'   => $result
        ];
	}
	
	public function formatTime($t) {
		try {
			$a = explode('.', $t);
			$date = new Carbon(0);
			$date->second = $a[0];
			$result = substr($date->toISOString(), 11, 8);
			return $result . ',' . $a[1];
		} catch (\Exception $ex) {
			return '';
		}
	}
}