<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        $this->region = getenv('AWS_DEFAULT_REGION');
        $this->access_key = getenv('AWS_ACCESS_KEY_ID');
        $this->secret_access_key = getenv('AWS_SECRET_ACCESS_KEY');
        $this->bucketName = getenv('AWS_BUCKET');
    }
}
