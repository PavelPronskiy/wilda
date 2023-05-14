<?php

namespace app\core;

/**
 * Configuration variables and functions
 */
class Config
{
	/**
	 * domain
	 *
	 * @var [type]
	 */
	public static $domain;

	/**
	 * config
	 *
	 * @var [type]
	 */
	public static $config;

	public static $route;
	public static $hash;
	public static $crypt;
	public static $hash_key;
	public static $editor;
	public static $compress;
	public static $request_uri;
	public static $name = 'wilda';
	public static $lang = [];
	public static $mail = [];
	public static $metrics = [];
	public static $favicon = [];
	public static $inject = [];
	public static $access = [];
	const CONFIG_ACCESS = PATH . '/app/config/access.json';
	const CONFIG_GLOBAL = PATH . '/app/config/global.json';
	const CONFIG_HOSTS = PATH . '/app/config/hosts.json';

	const QUERY_PARAM_IMG = '/?img=';
	const QUERY_PARAM_ICO = '/?ico=';
	const QUERY_PARAM_JS = '/?js=';
	const QUERY_PARAM_CSS = '/?css=';
	const QUERY_PARAM_FONT = '/?font=';

	const URI_QUERY_TYPES = [ 'ico', 'img', 'js', 'css', 'font' ];
	const URI_QUERY_ADMIN = [ 'cleaner', 'flush', 'keys' ];

	function __construct()
	{
		self::initialize();
	}

	
	public static function initialize(): void
	{
		$array            = [];
		$config_json      = [];
		$config_user_json = [];

		if (RUN_METHOD == 'web') {
			$request_uri = parse_url(
				preg_replace('{^//}', '/', $_SERVER[ 'REQUEST_URI' ])
			);

			self::$route = (object) [ 
				'domain' => $_SERVER[ 'HTTP_HOST' ],
				'path' => $request_uri[ 'path' ],
				'site' => isset($_SERVER[ 'HTTP_X_FORWARDED_PROTO' ])
				? $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] . '://' . $_SERVER[ 'HTTP_HOST' ]
				: $_SERVER[ 'REQUEST_SCHEME' ] . '://' . $_SERVER[ 'HTTP_HOST' ],
				'url' => isset($_SERVER[ 'HTTP_X_FORWARDED_PROTO' ])
				? $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] . '://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ]
				: $_SERVER[ 'REQUEST_SCHEME' ] . '://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ]
			];

			if (isset($request_uri[ 'query' ])) {
				parse_str($request_uri[ 'query' ], $query);
				self::$route->query = (object) $query;
			}
			else {
				self::$route->query = (object) [];
			}

			if (isset($_POST) && count($_POST) > 0)
				self::$route->post = (object) $_POST;

		}

		self::$config = (object) array_merge(
			(array) self::getGlobalConfig(),
			(array) self::getHostsConfig()
		);

		if (RUN_METHOD == 'web') {
			$device_type   = self::isMobile() ? 'mobile' : 'desktop';
			self::$domain  = self::getDomainConfig();
			self::$mail    = (object) [];
			self::$favicon = (object) [];
			self::$inject  = (object) [];
			self::$metrics = (object) [];
			self::$editor  = (object) [];
			self::$access  = (array) Access::getAccessConfig();


			/**
			 * set lang translations
			 * @var [type]
			 */
			self::$lang = isset(self::$domain->lang)
				? self::$config->translations->{self::$domain->lang}
				: self::$config->translations->{self::$config->lang};


			if (!isset(self::$domain->type))
				\app\util\Curl::curlErrorHandler(500);

			if (isset(self::$domain->styles))
				self::$config->styles = self::$domain->styles;

			if (isset(self::$domain->scripts))
				self::$config->scripts = self::$domain->scripts;

			if (isset(self::$domain->images))
				self::$config->images = self::$domain->images;

			if (
				is_array(
					self::$domain->site
				) && in_array(
					self::$route->site,
					self::$domain->site
				)
			) {
				self::$config->site = self::$route->site;
				self::$domain->site = self::$route->site;
			}


			/**
			 * set privoxy variables
			 */
			if (isset(self::$domain->privoxy)) {
				if (isset(self::$domain->privoxy->enabled))
					self::$config->privoxy->enabled = self::$domain->privoxy->enabled;

				if (isset(self::$domain->privoxy->host))
					self::$config->privoxy->host = self::$domain->privoxy->host;

				if (isset(self::$domain->privoxy->port))
					self::$config->privoxy->port = self::$domain->privoxy->port;
			}

			/**
			 * set cache variables
			 */
			if (isset(self::$domain->cache->enabled))
				self::$config->cache->enabled = self::$domain->cache->enabled;

			if (isset(self::$domain->cache->expire))
				self::$config->cache->expire = self::$domain->cache->expire;

			if (isset(self::$domain->cache->stats))
				self::$config->cache->stats = self::$domain->cache->stats;

			/**
			 * set mail submit variables
			 */
			if (isset(self::$domain->mail->enabled))
				self::$mail->enabled = self::$domain->mail->enabled;
			else
				self::$mail->enabled = self::$config->mail->enabled;

			if (isset(self::$domain->mail->subject))
				self::$mail->subject = self::$domain->mail->subject;
			else
				self::$mail->subject = self::$config->mail->subject;

			if (isset(self::$domain->mail->name))
				self::$mail->name = self::$domain->mail->name;
			else
				self::$mail->name = self::$config->mail->name;

			if (isset(self::$domain->mail->from))
				self::$mail->from = self::$domain->mail->from;
			else
				self::$mail->from = self::$config->mail->from;

			if (isset(self::$domain->mail->to))
				self::$mail->to = self::$domain->mail->to;
			else
				self::$mail->to = self::$config->mail->to;

			if (isset(self::$domain->mail->success))
				self::$mail->success = self::$domain->mail->success;
			else
				self::$mail->success = self::$config->mail->success;

			if (isset(self::$domain->mail->error))
				self::$mail->error = self::$domain->mail->error;
			else
				self::$mail->error = self::$config->mail->error;


			/**
			 * set favicon variables
			 */
			if (isset(self::$domain->favicon->enabled))
				self::$favicon->enabled = self::$domain->favicon->enabled;
			else
				self::$favicon->enabled = self::$config->favicon->enabled;

			self::$favicon->path = "app/favicon";

			/**
			 * set compress variables
			 */
			if (isset(self::$domain->compress))
				self::$compress = self::$domain->compress;
			else
				self::$compress = self::$config->compress;


			/**
			 * set metrics variables
			 */
			if (isset(self::$domain->metrics->enabled))
				self::$metrics->enabled = self::$domain->metrics->enabled;
			else
				self::$metrics->enabled = self::$config->metrics->enabled;

			self::$metrics->path = APP_PATH . "tpl/metrics";

			if (isset(self::$domain->metrics->ga))
				self::$metrics->ga = self::$domain->metrics->ga;

			if (isset(self::$domain->metrics->ya))
				self::$metrics->ya = self::$domain->metrics->ya;

			/**
			 * set inject variables
			 */
			if (isset(self::$domain->inject->enabled))
				self::$inject->enabled = self::$domain->inject->enabled;
			else
				self::$inject->enabled = self::$config->inject->enabled;

			if (isset(self::$domain->inject->header))
				self::$inject->header = self::$domain->inject->header;
			else
				self::$inject->header = self::$config->inject->header;

			if (isset(self::$domain->inject->path))
				self::$inject->path = APP_PATH . 'inject';
			else
				self::$inject->path = APP_PATH . 'inject';

			if (isset(self::$domain->inject->footer))
				self::$inject->footer = self::$domain->inject->footer;
			else
				self::$inject->footer = self::$config->inject->footer;

			/**
			 * set editor variables
			 */
			if (isset(self::$domain->editor->enabled))
				self::$editor->enabled = self::$domain->editor->enabled;
			else
				self::$editor->enabled = self::$config->editor->enabled;

			self::$editor->path = 'tpl/editor';



			self::$hash_key = self::$name . ':' . self::$route->domain . ':' . self::$domain->type;
			self::$hash     = self::$hash_key . ':' . $device_type . ':' . self::getURIEncryptHash();


		}
		else
			self::$lang = self::$config->translations->ru;

	}

	/**
	 * Эта функция извлекает глобальные параметры конфигурации из файла JSON и добавляет имя текущего
	 * объекта в конфигурацию перед ее возвратом.
	 * 
	 * @return глобальные параметры конфигурации в виде объекта JSON с добавленным свойством «имя».
	 */
	public static function getGlobalConfig()
	{
		$config_json = [];
		if (file_exists(self::CONFIG_GLOBAL)) {
			$config_json = json_decode(file_get_contents(self::CONFIG_GLOBAL));
			if (json_last_error() > 0)
				die(json_last_error_msg() . ' ' . self::CONFIG_GLOBAL);

		}
		else
			die('Global config: ' . self::CONFIG_GLOBAL . ' not found');


		$config_json->name = self::$name;

		return $config_json;
	}

	/**
	 * Функция проверяет, является ли данная строка допустимой конфигурацией JSON, и возвращает логическое
	 * значение.
	 * 
	 * @param string data Параметр `` представляет собой строку, представляющую данные конфигурации
	 * JSON. Функция `validateConfig` принимает этот параметр и проверяет, является ли он допустимым JSON,
	 * используя функцию `json_decode`. Если декодирование не удается, возвращается false, в противном
	 * случае возвращается true.
	 * 
	 * @return bool логическое значение. Он вернет «true», если входная строка «» является допустимой
	 * строкой JSON, и «false» в противном случае.
	 */
	public static function validateConfig(string $data): bool
	{
		json_decode($data);
		return json_last_error() > 0 ? false : true;
	}

	public static function getHostsConfig()
	{
		$config_json = [];
		if (file_exists(self::CONFIG_HOSTS)) {
			$config_json = json_decode(file_get_contents(self::CONFIG_HOSTS));
			if (json_last_error() > 0) {
				die(json_last_error_msg() . ' ' . self::CONFIG_HOSTS);
			}
		}
		else
			die('User config: ' . self::CONFIG_HOSTS . ' not found');

		return $config_json;
	}

	/**
	 * Эта функция устанавливает данные конфигурации пользователя, записывая их в файл JSON, если файл
	 * существует.
	 * 
	 * @param array data  — это массив, содержащий данные конфигурации пользователя, которые
	 * необходимо записать в файл.
	 */
	public static function setUserConfig(array $data): void
	{
		if (file_exists(self::CONFIG_HOSTS))
			file_put_contents(
				self::CONFIG_HOSTS,
				(string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
			);
	}

	/**
	 * [getURIEncryptHash description]
	 * @return [type] [description]
	 */
	public static function getURIEncryptHash(): string
	{
		foreach (Config::URI_QUERY_TYPES as $type) {
			if (isset(self::$route->query->{$type}))
				return $type . ':' . self::$route->query->{$type};
		}

		return 'html' . ':' . self::$route->url;
	}

	/**
	 * [render description]
	 * @param  [type] $response [description]
	 * @return [type]           [description]
	 */
	public static function render($response): void
	{
		if (RUN_METHOD == 'web')
		{

			if (isset($response->error) && isset($response->code))
				http_response_code($response->code);

				
			header("Content-type: " . $response->content_type);
			die($response->body);
		}
	}

	/**
	 * [isMobile description]
	 * @return boolean [description]
	 */
	public static function isMobile(): bool
	{
		if (isset($_SERVER[ 'HTTP_USER_AGENT' ]) and !empty($_SERVER[ 'HTTP_USER_AGENT' ])) {
			$bool = false;
			if (preg_match('/(Mobile|Android|Tablet|GoBrowser|[0-9]x[0-9]*|uZardWeb\/|Mini|Doris\/|Skyfire\/|iPhone|Fennec\/|Maemo|Iris\/|CLDC\-|Mobi\/)/uis', $_SERVER[ 'HTTP_USER_AGENT' ])) {
				$bool = true;
			}
		}

		return $bool;
	}

	/**
	 * [getSiteName description]
	 * @return [type] [description]
	 */
	public static function getSiteName()
	{
		return str_replace([ 'http://', 'https://' ], '', self::$domain->site);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function getProjectName() : string
	{
		return str_replace([ 'http://', 'https://' ], '', self::$domain->project);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function removeProtoUrl($url) : string
	{
		return str_replace([ 'http://', 'https://' ], '', $url);
	}

	public static function forceProto($url) : string
	{
		if (self::$config->forceSSL)
			return str_replace('http://', 'https://', $url);
		else
			return $url;
	}

	/**
	 * Undocumented function
	 *
	 * @param float $microtime
	 * @return string
	 */
	public static function microtimeAgo(float $microtime): string
	{
		return round((microtime(true) - $microtime) * 1000, 2) . 's';
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $time
	 * @param boolean $reverse
	 * @return void
	 */
	public static function timeAgo($time, $reverse = false) : string
	{
		$s             = 0;
		$estimate_time = $reverse === false
			? (int) $time - time()
			: time() - (int) $time;
		// var_dump($estimate_time);

		if ($estimate_time < 1) {
			return $s;
		}

		$condition = [ 
			24 * 60 * 60 => 'd',
			60 * 60 => 'h',
			60 => 'm',
			1 => 's'
		];

		foreach ($condition as $secs => $str) {
			$d = $estimate_time / $secs;

			if ($d >= 1) {
				$r = round($d);
				return $r . $str;
			}
		}
	}


	/**
	 * [getDomainConfig description]
	 * @return [type]        [description]
	 */
	public static function getDomainConfig() : object
	{

		foreach (self::$config->hosts as $host) {
			if (is_array($host->site))
				foreach ($host->site as $site) {
					$parse_host_site = (object) parse_url($site);
					if (self::$route->domain === $parse_host_site->host) {
						$host->site = $site;
						return $host;
					}
				}
			else {
				$parse_host_site = (object) parse_url($host->site);
				if (self::$route->domain === $parse_host_site->host)
					return $host;
			}
		}

		return (object) [];
	}
}