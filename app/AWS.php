<?php

namespace App;

use stdClass;

class AWS{
	public static function credentials()
    {
        $obj = new stdClass;
        $obj->region = getenv('AWS_DEFAULT_REGION');
        $obj->access_key = getenv('AWS_ACCESS_KEY_ID');
        $obj->secret_access_key = getenv('AWS_SECRET_ACCESS_KEY');
        $obj->bucketName = getenv('AWS_BUCKET');

        return $obj;
    }
}