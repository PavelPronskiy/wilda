<?php


namespace app\util;

use app\core\Config;
use app\util\Encryption;


class Cache
{
	public $hash = '';
	public static $instance = '';

	function __construct()
	{
		$this->redisInstance();
	}

	function __destruct() {}

	private function redisInstance() : void
	{
		self::$instance = new \Redis();
		self::$instance->connect(Config::$config->redis->host, Config::$config->redis->port);
	}

	public static function notice($message) : void
	{
		$host_str = 'Host: ' . $_SERVER['HTTP_HOST'];
		die('<pre>' . $host_str . ', ' . $message . '</pre>');
	}

	public static function clear()
	{
		$pages = self::$instance->keys(Config::$hash_key . ':*');

		if (count($pages) > 0)
			foreach ($pages as $key)
				self::$instance->del($key);

		return Config::render((object) [
			'body' => '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="2; url=\'' . $_SERVER['HTTP_REFERER'] . '\'" /></head><body><h4>Перенаправление на главную...</h4><p>Очищено элементов: ' . count($pages) . '</p></body></html>',
			'content_type' => 'text/html; charset=UTF-8'
		]);

	}

	public static function keys() : void
	{
		$pages = self::$instance->keys(Config::$hash_key . ':*');

		if (count($pages) > 0)
			self::notice('pages cached: ' . count($pages));
		else
			self::notice('cache is empty');

	}
	
	public static function get(string $hash) : object
	{
		$res = self::$instance->get($hash);
		if ($res)
		{
			$obj = json_decode($res);
			$obj->body = base64_decode($obj->body);
		}
		else
		{
			$obj = (object) [];
		}

		return $obj;
	}

	/**
	 * [set description]
	 * @param object $obj  [description]
	 * @param string $hash [description]
	 */
	public static function set(object $obj, string $hash) : void
	{
		self::$instance->set(
			(string) $hash,
			(string) json_encode([
				'body' => base64_encode($obj->body),
				'content_type' => $obj->content_type
			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
		);

		self::$instance->expire(
			(string) $hash,
			(int) Config::$config->cache->expire * 60
		);

		$obj = (object) [];
	}
	
	/**
	 * [webCacheCleaner description]
	 * @return [type] [description]
	 */
	public static function webCacheCleaner()
	{
		$hash = Config::$name . ':' . Encryption::encode(Config::$config->salt);

		self::$instance->set(
			(string) $hash,
			''
		);

		self::$instance->expire(
			(string) $hash,
			(int) Config::$config->cache->expire * 60
		);

		return Config::render((object) [
			'body' => '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="2; url=\'' . Config::$domain->site . '/\'" /><script>function setCookie(name,value,days) { var expires = ""; if (days) { var date = new Date(); date.setTime(date.getTime() + (days*24*60*60*1000)); expires = "; expires=" + date.toUTCString(); } document.cookie = name + "=" + (value || "")  + expires + "; path=/"; }; setCookie("' . Config::$name . '", "' . $hash . '",' . Config::$config->cache->expire . ');</script></head><body><h4>Перенаправление на главную...</h4><p>Hash: ' . $hash . '</p></body></html>',
			'content_type' => 'text/html; charset=UTF-8'
		]);
	}

	public static function injectWebCleaner($html) : string
	{
		if (isset($_COOKIE['wilda']))
		{
			$exp = explode(':', $_COOKIE['wilda']);

			if (isset($exp[1]) && Encryption::decode($exp[1]) === Config::$config->salt)
			{
				$inject_html = '<div style="position:fixed;z-index:99999;left:0;top:0;padding:3px 6px;background-color:rgba(0,0,0,0.4"><a style="text-decoration:none;color:#fff;font-size:16pt;font-weight:normal" href="/?clear">&#10227;</a></div>';
				return str_replace('</body>', $inject_html . '</body>', $html);
			}

		}

		return $html;
	}
}