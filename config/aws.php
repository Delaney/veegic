<?php

return [

	'region' => env('AWS_DEFAULT_REGION', 'null'),
	'access_key' => env('AWS_ACCESS_KEY_ID', 'null'),
	'secret_access_key' => env('AWS_SECRET_ACCESS_KEY', 'null'),
	'bucket_name' => env('AWS_BUCKET', 'null'),

];