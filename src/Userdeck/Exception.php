<?php namespace Userdeck;

class Exception extends \Exception
{
	protected $response;
	protected $response_info;
	
	public function __construct($message, $code, $response = null, $response_info = null)
	{
		parent::__construct($message, $code);
		
		$this->response      = $response;
		$this->response_info = $response_info;
	}
	
	public function getResponse()
	{
		return $this->response;
	}
	
	public function getResponseInfo()
	{
		return $this->response_info;
	}
}
