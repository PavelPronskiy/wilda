<?php

namespace Cache;

class Controller
{
	public $hash = '';

	function __construct()
	{
		$this->config = \Tilda\Config::getConfig();
		$this->redisInstance();
	}

	function __destruct() {}

	private function redisInstance() {
		$this->redis = new \Redis();
		$this->redis->connect($this->config->redis->host, $this->config->redis->port);
	}

	public function notice($message)
	{
		$host_str = 'Host: ' . $_SERVER['HTTP_HOST'];
		die('<pre>' . $host_str . ', ' . $message . '</pre>');
	}

	public function flush($cache)
	{
		$keys = $_SERVER['HTTP_HOST'] . '*';

		switch ($cache) {
			case 'keys':
				$pages = $this->redis->keys($keys);
				if (count($pages) > 0) {
					$this->notice('pages cached: ' . count($pages));
				} else {
					$this->notice('cache is empty');
				}

				break;
			
			case 'flush':
				$pages = $this->redis->keys($keys);
				$this->redis->delete($pages);
				$this->notice('deleted cache pages: ' . count($pages));
				break;
			
			default:
				$this->notice('<pre>cache=keys - view all cached items' . PHP_EOL . 'cache=flush - delete all cached items</pre>');
				# code...
				break;
		}

	}
	
	public function get($hash = '')
	{
		$hash = !empty($hash) ? $hash : $this->hash;
		return base64_decode($this->redis->get($hash));
	}

	public function set($body, $hash = '', $expire = '')
	{
		$expire = !empty($expire) ? $expire : $this->config->cache->expire;
		$hash = !empty($hash) ? $hash : $this->hash;
		$this->redis->set($hash, base64_encode($body));
		$this->redis->expire($this->hash, $expire * 60);
	}
}