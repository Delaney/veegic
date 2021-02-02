<?php

namespace App;

use Exception;
use GuzzleHttp\Client;

/**
 * Paystack service class
 */
class Translator
{
    /**
     * @var \App\Translator
     */
    public static $instance;

    /**
     * Microsoft Azure API base Url
     * @var string
     */
    protected $base_url;

    /**
     * Paystack API secret key
     * @var string
     */
    protected $api_key;

    /**
     * Instance of http client
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * Initialize the class
     */
    public function __construct()
    {
        $this->set_base_url();
        $this->set_api_key();
		$this->set_http_request_options();
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

    /**
     * Set the Azure base url
     */
    private function set_base_url()
    {
        $this->base_url =  config('azure.endpoint');
    }

    /**
     * Set the Azure API key from configuration
     */
    private function set_api_key()
    {
        $this->api_key  = config('azure.api_key');
    }

    /**
     * Set http client request options
     */
    private function set_http_request_options()
    {
		$this->client = new Client(
            [
				'headers'  => [
					'Content-Type'  			=> 'application/json',
					'Ocp-Apim-Subscription-Key'	=> $this->api_key,
					'Ocp-Apim-Subscription-Region'	=> 'global',
					'Accept'  			=> 'application/json',
				]
            ]
        );
    }

    public function jwt_request_post($endpoint, $post)
	{
        $ch = curl_init($endpoint); // Initialise cURL
        $post = json_encode($post); // Encode the data array into a JSON string
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Ocp-Apim-Subscription-Key: $this->api_key")); // Inject the token into the header
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
		$result = curl_exec($ch); // Execute the cURL statement
		curl_close($ch); // Close the cURL connection

		return json_decode($result); // Return the received data
	}
	
	/**
     * Make the http get request
     * 
     * @param string $path | paystack relative path like '/transaction'
     * @param array $query
     * 
     * @return App\Services\PaystackService
     * @return Exception
     */
    private function make_http_get_request($path, $query = [])
    {
        $this->response = $this->client->request(
            'GET',
            $this->base_url . $path,
            ["query" => $query]
        );

        return $this;
    }

    /**
     * Get response body
     * 
     * @return json
     */
    private function get_response()
    {
        return json_decode($this->response->getBody(), true);
	}
	
	/**
     * Fetch all languages 
     * 
     * @param array $params
     * 
     * @return mixed | json object
     */
    public function languages($params = null)
    {
        return $this->make_http_get_request('/languages?api-version=3.0', $params)->get_response();
	}
	
    /**
     * Make translation
     * 
     * @param array $data
     * 
     * @return mixed | json object
     */
    public function make($language, $data)
    {
        $url = $this->base_url . '/translate?api-version=3.0&to=' . $language;
		return $this->jwt_request_post($url, $data);
	}
}
