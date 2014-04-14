<?php namespace UserDeck;

/**
 * UserDeck session interface.
 */
interface SessionInterface
{
	/**
	 * Set a session key prefix for this instance.
	 *
	 * @param string $prefix Session key prefix.
	 * 
	 * @return SessionInterface
	 */
	public function setPrefix($prefix);
	
	/**
	 * Get the session key prefix for this instance.
	 *
	 * @return string
	 */
	public function getPrefix();
	
	/**
	 * Save an item to the session.
	 *
	 * @param string $name  Name of key.
	 * @param mixed  $value Value to save.
	 * 
	 * @return SessionInterface
	 */
	public function put($name, $value);
	
	/**
	 * Get an item from the session.
	 *
	 * @param string $name Name of key.
	 * 
	 * @return mixed|null
	 */
	public function get($name);
	
	/**
	 * Check to see if an item is in session.
	 *
	 * @param string $name Name of key.
	 * 
	 * @return bool
	 */
	public function has($name);
	
	/**
	 * Remove an item from the session.
	 *
	 * @param string $name Name of key.
	 * 
	 * @return SessionInterface
	 */
	public function forget($name);
}
