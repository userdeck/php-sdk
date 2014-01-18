<?php namespace Userdeck;

/**
 * Main API client.
 */
class Client
{
	public $api_url = 'https://api.userdeck.com';
	protected $consumer_key;
	protected $consumer_secret;
	protected $access_token;
	
	/**
	 * Create a new Userdeck Client.
	 *
	 * @param string $consumer_key
	 * @param string $consumer_secret
	 * @param string $api_url
	 * 
	 * @return Client
	 */
	public function __construct($consumer_key, $consumer_secret, $api_url = null)
	{
		$this->consumer_key    = $consumer_key;
		$this->consumer_secret = $consumer_secret;
		
		if ($api_url) {
			$this->api_url = $api_url;
		}
	}
	
	/**
	 * Setter for the OAuth access token.
	 *
	 * @param string $access_token
	 * 
	 * @return Client
	 */
	public function setAccessToken($access_token)
	{
		$this->access_token = $access_token;
		
		return $this;
	}
	
	/**
	 * Fetch an OAuth access token.
	 *
	 * @param array  $params  The parameters to send with the request.
	 * @param array  $options The options to use to build the request.
	 * 
	 * @return array
	 * @throws Exception
	 */
	public function getAccessToken(array $params = array(), array $options = array())
	{
		$params = array_merge(array(
			'client_id'     => $this->consumer_key,
			'client_secret' => $this->consumer_secret,
		), $params);
		
		return $this->api('oauth/access_token', 'post', $params, $options);
	}
	
	/**
	 * Perform a GET API request to the given resource.
	 *
	 * @param string $resource The API resource endpoint to call.
	 * @param array  $params   The parameters to send with the request.
	 * @param array  $options  The options to use to build the request.
	 * 
	 * @return array
	 * @throws Exception
	 */
	public function get($resource = '', array $params = array(), $options = array())
	{
		return $this->api($resource, 'get', $params, $options);
	}
	
	/**
	 * Perform a POST API request to the given resource.
	 *
	 * @param string $resource The API resource endpoint to call.
	 * @param array  $params   The parameters to send with the request.
	 * @param array  $options  The options to use to build the request.
	 * 
	 * @return array
	 * @throws Exception
	 */
	public function post($resource = '', array $params = array(), $options = array())
	{
		return $this->api($resource, 'post', $params, $options);
	}
	
	/**
	 * Perform a PUT API request to the given resource.
	 *
	 * @param string $resource The API resource endpoint to call.
	 * @param array  $params   The parameters to send with the request.
	 * @param array  $options  The options to use to build the request.
	 * 
	 * @return array
	 * @throws Exception
	 */
	public function put($resource = '', array $params = array(), $options = array())
	{
		return $this->api($resource, 'put', $params, $options);
	}
	
	/**
	 * Perform a DELETE API request to the given resource.
	 *
	 * @param string $resource The API resource endpoint to call.
	 * @param array  $params   The parameters to send with the request.
	 * @param array  $options  The options to use to build the request.
	 * 
	 * @return array
	 * @throws Exception
	 */
	public function delete($resource = '', array $params = array(), $options = array())
	{
		return $this->api($resource, 'delete', $params, $options);
	}
	
	/**
	 * Perform an API request to the given resource.
	 *
	 * @param string $resource The API resource endpoint to call.
	 * @param string $method   The http method to use for the request.
	 * @param array  $params   The parameters to send with the request.
	 * @param array  $options  The options to use to build the request.
	 * 
	 * @return array
	 * @throws Exception
	 */
	public function api($resource = '', $method = 'get', array $params = array(), array $options = array())
	{
		return $this->makeRequest($resource, $method, $params, $options);
	}
	
	/**
	 * Make a cURL request.
	 *
	 * @param string $resource The API resource endpoint to call.
	 * @param string $method   The http method to use for the request.
	 * @param array  $params   The parameters to send with the request.
	 * @param array  $options  The options to use to build the request.
	 * 
	 * @return array
	 * @throws Exception
	 */
	protected function makeRequest($resource = '', $method = 'get', array $params = array(), array $options = array())
	{
		if (!function_exists('curl_init')) {
			throw new Exception(
				'Your PHP installation doesn\'t have cURL enabled. Rebuild PHP with --with-curl'
			);
		}
		
		$method = strtoupper($method);
		
		$url = rtrim($this->api_url, '/') . '/' . trim($resource, '/');
		
		if (!empty($params)) {
			if ($method == 'GET') {
				$char = strpos($url, '?') === false ? '?' : '&';
				$url .= $char;
				
				if (is_string($params)) {
					$url .= $params;
				}
				else {
					$url .= http_build_query($params);
				}
			}
			else {
				$params = http_build_query($params, null, '&');
			}
		}
		
		$connection = curl_init($url);
		
		$headers = array(
			'Accept' => 'application/json',
		);
		
		if (!empty($this->access_token)) {
			$headers['Authorization'] = "Bearer {$this->access_token}";
		}
		
		if (!isset($options[CURLOPT_TIMEOUT])) {
			$options[CURLOPT_TIMEOUT] = 30;
		}
		if (!isset($options[CURLOPT_RETURNTRANSFER])) {
			$options[CURLOPT_RETURNTRANSFER] = true;
		}
		if (!isset($options[CURLOPT_FAILONERROR])) {
			$options[CURLOPT_FAILONERROR] = false;
		}
		
		if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
			if (!isset($options[CURLOPT_FOLLOWLOCATION])) {
				$options[CURLOPT_FOLLOWLOCATION] = true;
			}
		}
		
		if ($method != 'GET') {
			$options[CURLOPT_CUSTOMREQUEST] = $method;
		}
		
		switch ($method) {
			case 'GET':
				break;
				
			case 'POST':
				$options[CURLOPT_POST] = true;
				$options[CURLOPT_POSTFIELDS] = $params;
				break;
				
			case 'PUT':
				$options[CURLOPT_POSTFIELDS] = $params;
				$headers['X-HTTP-Method-Override'] = $method;
				break;
				
			case 'DELETE':
				$options[CURLOPT_POSTFIELDS] = $params;
				$headers['X-HTTP-Method-Override'] = $method;
				break;
		}
		
		$format_headers = array();
		
		foreach ($headers as $key => $value) {
			$format_headers[] = is_int($key) ? $value : $key . ': ' . $value;
		}
		
		$headers = $format_headers;
		
		if (!empty($headers)) {
			dar($headers);
			$options[CURLOPT_HTTPHEADER] = $headers;
		}
		
		curl_setopt_array($connection, $options);
		
		// Execute the request to the API.
		$response = curl_exec($connection);
		$response_info = curl_getinfo($connection);
		
		$headers = array();
		if (isset($options[CURLOPT_HEADER]) && $options[CURLOPT_HEADER]) {
			$raw_headers = explode("\n", str_replace("\r", "", substr($response, 0, $response_info['header_size'])));
			$response = $response_info['header_size'] >= strlen($response) ? '' : substr($response, $response_info['header_size']);
			
			// Convert the header data.
			foreach ($raw_headers as $header) {
				$header = explode(':', $header, 2);
				
				if (isset($header[1])) {
					$headers[trim($header[0])] = trim($header[1]);
				}
			}
		}
		
		if ($response === false) {
			throw new Exception(curl_error($connection), curl_errno($connection), $response, $response_info);
		}
		
		// Get the response body from the request.
		$response = json_decode(trim($response), true);
		
		// Make sure there were no errors with the API call.
		if ($response_info['http_code'] >= 400) {
			$message_parts = array();
			if (!empty($response['error'])) {
				$message_parts[] = $response['error'];
			}
			if (!empty($response['error_description'])) {
				$message_parts[] = $response['error_description'];
			}
			if (!empty($response['message'])) {
				$message_parts[] = $response['message'];
			}
			if (empty($message_parts)) {
				$message_parts = array('Userdeck API error.');
			}
			
			throw new Exception(implode(': ', $message_parts), $response_info['http_code'], $response, $response_info);
		}
		
		curl_close($connection);
		
		return $response;
	}
}
