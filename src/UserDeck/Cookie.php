<?php namespace UserDeck;

/**
 * UserDeck cookie session driver.
 */
class Cookie implements SessionInterface
{
	/**
	 * Session key prefix.
	 *
	 * @var string
	 */
	protected $prefix = '';
	
	/**
	 * Local cache of cookies.
	 *
	 * @var array
	 */
	protected $cookies = [];
	
	/**
	 * Set a session key prefix for this instance.
	 *
	 * @param string $prefix Session key prefix.
	 * 
	 * @return Cookie
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
		
		return $this;
	}
	
	/**
	 * Get the session key prefix for this instance.
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}
	
	/**
	 * Save an item to the session.
	 *
	 * @param string $name  Name of key.
	 * @param mixed  $value Value to save.
	 * 
	 * @return Cookie
	 */
	public function put($name, $value)
	{
		setcookie(
			"{$this->prefix}$name",
			json_encode($value),
			time() + 157680000, // +5 years
			'/'
		);
		
		$this->cookies[$name] = $value;
		
		return $this;
	}
	
	/**
	 * Get an item from the session.
	 *
	 * @param string $name Name of key.
	 * 
	 * @return mixed|null
	 */
	public function get($name)
	{
		if ($this->has($name)) {
			if (isset($this->cookies[$name])) {
				return $this->cookies[$name];
			}
			
			return json_decode($_COOKIE["{$this->prefix}$name"], true);
		}
		
		return null;
	}
	
	/**
	 * Check to see if an item is in session.
	 *
	 * @param string $name Name of key.
	 * 
	 * @return bool
	 */
	public function has($name)
	{
		if (isset($this->cookies[$name])) {
			return true;
		}
		
		if (isset($_COOKIE["{$this->prefix}$name"])) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Remove an item from the session.
	 *
	 * @param string $name Name of key.
	 * 
	 * @return Cookie
	 */
	public function forget($name)
	{
		if (!$this->has($name)) {
			return $this;
		}
		
		setcookie(
			"{$this->prefix}$name",
			'',
			time() - 2592000, // -30 days
			'/'
		);
		
		if (isset($this->cookies[$name])) {
			unset($this->cookies[$name]);
		}
		
		return $this;
	}
}
