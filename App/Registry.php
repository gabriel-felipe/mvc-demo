<?php
namespace Mvc\App;

final class Registry {
	private static $data = array();
	private static $instance = null;
	public static function get($key)
	{
		return (isset(self::$data[$key]) ? self::$data[$key] : FALSE);
	}

	public static function getInstance()
	{
		if (!self::$instance instanceof Registry) {
			self::$instance = new Registry();
		}
		return self::$instance;
	}

	public static function set($key, $value)
	{
		self::$data[$key] = $value;
	}

	public function has($key)
	{
    	return isset($this->data[$key]);
  	}

 	public function __get($key)
	{
			return $this->get($key);
	}

}
?>
