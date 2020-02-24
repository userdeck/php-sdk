<?php

/**
 * Main UserDeck API client.
 */
class UserDeck
{
	public $api_url       = 'https://api.userdeck.com';
	public $authorize_url = 'https://app.userdeck.com/oauth/authorize';
	
	protected $client_id;
	protected $client_secret;
	protected $access_token;
	protected $account_id;
	protected $session;
	protected $headers;
	
	/**
	 * Create a new UserDeck Client.
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * 
	 * @return UserDeck
	 */
	public function __construct($client_id = null, $client_secret = null)
	{
		$this->client_id    = $client_id;
		$this->client_secret = $client_secret;
		
		$driver = new UserDeck\Cookie();
		$driver->setPrefix('ud_');
		$this->setSessionDriver($driver);
	}
	
	/**
	 * Setter for the OAuth access token.
	 *
	 * @param string $access_token
	 * 
	 * @return UserDeck
	 */
	public function setAccessToken($access_token)
	{
		$this->access_token = $access_token;
		
		return $this;
	}
	
	/**
	 * Getter for the OAuth access token.
	 *
	 * @return string|null
	 */
	public function getAccessToken()
	{
		if (null === $this->access_token) {
			if ($token = $this->session->get('token')) {
				$this->setAccessToken($token['access_token']);
			}
		}
		
		return $this->access_token;
	}
	
	/**
	 * Getter for the OAuth access token info array.
	 *
	 * @return array|null
	 */
	public function getAccessTokenInfo()
	{
		return $this->session->get('token');
	}
	
	/**
	 * Set the active account_id for API requests.
	 *
	 * @param integer $account_id
	 * 
	 * @return UserDeck
	 */
	public function setAccount($account_id)
	{
		$this->account_id = $account_id;
		
		return $this;
	}
	
	/**
	 * Get the active account_id for API requests, if set.
	 *
	 * @return integer|null
	 */
	public function getAccount()
	{
		return $this->account_id;
	}
	
	/**
	 * Fetch an OAuth access token.
	 * NOTE: Only available to clients with the 'password' grant type enabled.
	 *
	 * @param string $email    User email address.
	 * @param string $password User password.
	 * @param array  $params   Optional parameters to use to build the request.
	 * 
	 * @return UserDeck
	 * @throws UserDeck\Exception
	 */
	public function login($email, $password, array $params = array())
	{
		$this->logout();
		
		$token = $this->post('oauth/access_token', array_merge(array(
			'grant_type' => 'password',
			'username'   => $email,
			'password'   => $password,
		), $params), array(
			'no_access_token' => true,
		));
		
		$this->session->put('token', $token);
		
		return $this;
	}
	
	/**
	 * Log the current user out. Remove from session.
	 *
	 * @return void
	 * @throws UserDeck\Exception
	 */
	public function logout()
	{
		$this->session->forget('token');
		$this->access_token = null;
	}
	
	/**
	 * Generate a redirect url to initiate an authorization code
	 * oauth login attempt.
	 *
	 * @param array $params Optional parameters to use to build the redirect url.
	 *                      - redirect_uri
	 *                      - scope
	 *                      - state
	 * 
	 * @return string
	 */
	public function getAuthorizationUrl(array $params = array())
	{
		$params['response_type'] = 'code';
		$params['client_id'] = $this->client_id;
		
		if (!empty($params['redirect_uri'])) {
			$params['redirect_uri'] = urlencode($params['redirect_uri']);
		}
		if (!empty($params['scope'])) {
			$params['scope'] = urlencode($params['scope']);
		}
		if (!empty($params['state'])) {
			$params['state'] = urlencode($params['state']);
		}
		
		return $this->authorize_url.'?'.http_build_query($params);
	}
	
	/**
	 * Fetch an OAuth access token from an authorization code.
	 *
	 * @param string $code         The authorization code.
	 * @param string $redirect_uri The endpoint associated with your client.
	 * @param array  $options      The options to use to build the request.
	 * 
	 * @return UserDeck
	 * @throws UserDeck\Exception
	 */
	public function loginWithCode($code, $redirect_uri, array $options = array())
	{
		$this->logout();
		$options['no_access_token'] = true;
		
		$token = $this->post('oauth/access_token', array(
			'grant_type'   => 'authorization_code',
			'code'         => $code,
			'redirect_uri' => $redirect_uri,
		), $options);
		
		$this->session->put('token', $token);
		
		return $this;
	}
	
	/**
	 * Attempt to refresh the current login's access_token.
	 *
	 * @param array $options The options to use to build the request.
	 * 
	 * @return bool
	 */
	public function refreshLoginToken(array $options = array())
	{
		$token = $this->session->get('token');
		if (!$token) {
			return false;
		}
		
		if (!isset($token['refresh_token'])) {
			return false;
		}
		
		$refresh_token = $token['refresh_token'];
		$options['no_access_token'] = true;
		
		try {
			$new_token = $this->post('oauth/access_token', array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $refresh_token,
			), $options);
		} catch (Exception $e) {
			return false;
		}
		
		if (!isset($new_token['refresh_token'])) {
			$new_token['refresh_token'] = $refresh_token;
		}
		
		$this->access_token = null;
		$this->session->put('token', $new_token);
		
		return true;
	}
	
	/**
	 * Set the session driver for this instance.
	 *
	 * @param UserDeck\SessionInterface $driver Session driver instance.
	 * 
	 * @return UserDeck
	 */
	public function setSessionDriver(UserDeck\SessionInterface $driver)
	{
		$this->session = $driver;
		
		return $this;
	}
	
	/**
	 * Getter for the current session driver instance.
	 *
	 * @return UserDeck\SessionInterface
	 */
	public function getSessionDriver()
	{
		return $this->session;
	}
	
	/**
	 * Return the headers recieved from the last API request.
	 *
	 * @return array|null
	 */
	public function getHeaders()
	{
		return $this->headers;
	}
	
	/**
	 * Perform a GET API request to the given resource.
	 *
	 * @param string $resource The API resource endpoint to call.
	 * @param array  $params   The parameters to send with the request.
	 * @param array  $options  The options to use to build the request.
	 * 
	 * @return array
	 * @throws UserDeck\Exception
	 */
	public function get($resource = '', array $params = array(), array $options = array())
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
	 * @throws UserDeck\Exception
	 */
	public function post($resource = '', array $params = array(), array $options = array())
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
	 * @throws UserDeck\Exception
	 */
	public function put($resource = '', array $params = array(), array $options = array())
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
	 * @throws UserDeck\Exception
	 */
	public function delete($resource = '', array $params = array(), array $options = array())
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
	 * @throws UserDeck\Exception
	 */
	public function api($resource = '', $method = 'get', array $params = array(), array $options = array())
	{
		try {
			$response = $this->makeRequest($resource, $method, $params, $options);
		} catch (Exception $e) {
			// Check for token expiration. Refresh and try again, if possible.
			if ($this->session->has('token') && 401 == $e->getCode()) {
				if ($this->refreshLoginToken()) {
					return $this->makeRequest($resource, $method, $params, $options);
				}
			}
			
			throw $e;
		}
		
		return $response;
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
	 * @throws UserDeck\Exception
	 */
	protected function makeRequest($resource = '', $method = 'get', array $params = array(), array $options = array())
	{
		if (!function_exists('curl_init')) {
			throw new UserDeck\Exception(
				'Your PHP installation doesn\'t have cURL enabled. Rebuild PHP with --with-curl',
				500
			);
		}
		
		$method = strtoupper($method);
		
		$url = rtrim($this->api_url, '/') . '/' . trim($resource, '/');
		
		$headers = array(
			'Accept' => 'application/json',
		);
		
		$access_token = $this->getAccessToken();
		if (!empty($access_token) && empty($options['no_access_token'])) {
			$headers['Authorization'] = "Bearer {$access_token}";
		}
		else if (!empty($this->client_id) && !empty($this->client_secret)) {
			$params['client_id'] = $this->client_id;
			$params['client_secret'] = $this->client_secret;
		}
		
		if ($this->account_id) {
			$headers['Account'] = $this->account_id;
		}
		
		if (isset($options['no_access_token'])) {
			unset($options['no_access_token']);
		}
		
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
			$options[CURLOPT_HTTPHEADER] = $headers;
		}
		
		curl_setopt_array($connection, $options);
		
		// Execute the request to the API.
		$response = curl_exec($connection);
		$response_info = curl_getinfo($connection);
		
		$this->headers = null;
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
			
			$this->headers = $headers;
		}
		
		if ($response === false) {
			throw new UserDeck\Exception(curl_error($connection), curl_errno($connection), $response, $response_info);
		}
		
		// Get the response body from the request.
		$response = json_decode(trim($response), true);
		
		// Make sure there were no errors with the API call.
		if ($response_info['http_code'] >= 400) {
			$message_parts = array();
			if (!empty($response['error']) && is_string($response['error'])) {
				$message_parts[] = $response['error'];
			}
			if (!empty($response['error_description']) && is_string($response['error_description'])) {
				$message_parts[] = $response['error_description'];
			}
			if (!empty($response['message']) && is_string($response['message'])) {
				$message_parts[] = $response['message'];
			}
			if (empty($message_parts)) {
				$message_parts = array('UserDeck API error.');
			}
			throw new UserDeck\Exception(implode(': ', $message_parts), $response_info['http_code'], $response, $response_info);
		}
		
		curl_close($connection);
		
		return $response;
	}
}
