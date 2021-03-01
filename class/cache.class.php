<?php

namespace Cache;

class Controller
{
	public $hash = '';

	function __construct()
	{
		$this->redisInstance();
	}

	function __destruct() {}

	private function redisInstance() : void
	{
		$this->redis = new \Redis();
		$this->redis->connect(\Config\Controller::$config->redis->host, \Config\Controller::$config->redis->port);
	}

	public function notice($message) : void
	{
		$host_str = 'Host: ' . $_SERVER['HTTP_HOST'];
		die('<pre>' . $host_str . ', ' . $message . '</pre>');
	}

	public function flush($cache) : void
	{
		$keys = $_SERVER['HTTP_HOST'] . '*';

		switch ($cache) {
			case 'keys':
				$pages = $this->redis->keys($keys);
				if (count($pages) > 0)
				{
					$this->notice('pages cached: ' . count($pages));
				}
				else
				{
					$this->notice('cache is empty');
				}

				break;
			
			case 'flush':
				$pages = $this->redis->keys($keys);
				$this->redis->del($pages);
				$this->notice('deleted cache pages: ' . count($pages));
				break;
			
			default:
				$this->notice('<pre>cache=keys - view all cached items' . PHP_EOL . 'cache=flush - delete all cached items</pre>');
				# code...
				break;
		}

	}
	
	public function get($hash) : object
	{
		$res = $this->redis->get($hash);

		return $res
			? json_decode($res)
			: (object) [];
	}

	public function set($body, $hash) : void
	{
		// $expire = \Config\Controller::$config->cache->expire;
		$this->redis->set(
			(string) $hash,
			(string) json_encode($body)
		);

		$this->redis->expire(
			(string) $hash,
			(int) \Config\Controller::$config->cache->expire * 60
		);
	}
}