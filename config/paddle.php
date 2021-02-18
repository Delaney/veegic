<?php

return [
	'endpoint'	=> env('PADDLE_URL', 'null'),
	'vendor_id' => env('PADDLE_VENDOR_ID', 'null'),
	'product_id' => env('PADDLE_PRODUCT_ID', 'null'),
	'vendor_auth_code' => env('PADDLE_API_KEY', 'null')
];