<?php

namespace App;

use Exception;

class Paddle
{
	/**
     * @var \App\Paddle
     */
    public static $instance;

    protected $base_url;

    protected $vendor_auth_code;

    protected $vendor_id;

	protected $product_id;

	/**
     * Initialize the class
     */
    public function __construct()
    {
		$this->base_url =  config('paddle.endpoint');
		$this->vendor_auth_code  = config('paddle.vendor_auth_code');
		$this->vendor_id  = config('paddle.vendor_id');
		$this->product_id  = config('paddle.product_id');
    }

	/**
     * Initialize Translator class through static init method
     */
    public static function init()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	public function jwt_request_post($endpoint, $post)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post)); // Set the posted fields
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/json"
		));
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

		$response = curl_exec($ch);
		curl_close($ch);

		return json_decode($response);
	}

	# -- PAYMENT LINK
	public function generate_payment_link($data) {
		$uri = $this->base_url . '2.0/product/generate_pay_link';
		$data['vendor_id'] = $this->vendor_id;
		$data['vendor_auth_code'] = $this->vendor_auth_code;
		$data['product_id'] = (Integer) $this->product_id;
		$result = $this->jwt_request_post($uri, $data);
		return $result;
	}

	# -- TRANSACTIONS
	# todo

	# -- CANCEL PLAN/USER
	public function cancel_subscription($data) {
		$uri = $this->base_url . '2.0/subscription/users_cancel';
		$data['vendor_id'] = $this->vendor_id;
		$data['vendor_auth_code'] = $this->vendor_auth_code;
		$result = $this->jwt_request_post($uri, $data);
		return $result;
	}

	# -- LIST USERS
	public function list_users($data) {
		$uri = $this->base_url . '2.0/subscription/users';
		$data['vendor_id'] = $this->vendor_id;
		$data['vendor_auth_code'] = $this->vendor_auth_code;
		$result = $this->jwt_request_post($uri, $data);
		return $result;
	}
}