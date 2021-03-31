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
		$pages = $this->redis->keys(\Config\Controller::$hash);

		if (count($pages) > 0)
		{
			$this->redis->del($pages);
			$this->notice('deleted cache pages: ' . count($pages));
		}
		else
		{
			$this->notice('cache is empty');
		}
	}

	public function keys($cache) : void
	{
		$pages = $this->redis->keys(\Config\Controller::$hash);

		if (count($pages) > 0)
		{
			$this->notice('pages cached: ' . count($pages));
		}
		else
		{
			$this->notice('cache is empty');
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