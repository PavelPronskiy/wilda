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