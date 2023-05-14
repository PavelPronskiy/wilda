<?php

namespace app\util;

use app\core\Config;
use app\util\Encryption;


/* Класс Cache предоставляет методы для кэширования веб-страниц и очистки кэша. */
class Cache
{
	public $hash = '';
	public static $microtime = '';
	public static $instance = '';
	public static $revision_key = 'rev';

	function __construct()
	{
		$this->redisInstance();
	}

	function __destruct() {}

	/**
	 * Эта функция создает экземпляр Redis и подключает его к указанному хосту и порту.
	 */
	private function redisInstance() : void
	{
		self::$instance = new \Redis();
		self::$instance->connect(Config::$config->redis->host, Config::$config->redis->port);
	}

	/**
	 * Функция выводит сообщение вместе с хостом HTTP и завершает выполнение скрипта.
	 * 
	 * @param message Параметр сообщения — это строка, представляющая уведомление, которое будет
	 * отображаться при вызове функции.
	 */
	public static function notice($message) : void
	{
		$host_str = 'Host: ' . $_SERVER['HTTP_HOST'];
		die('<pre>' . $host_str . ', ' . $message . '</pre>');
	}


	/**
	 * Функция очищает все ключи в Redis с помощью определенного хеш-ключа и перенаправляет на домашнюю
	 * страницу с сообщением, указывающим количество очищенных элементов.
	 */
	public static function clear() : void
	{
		$pages = self::$instance->keys(Config::$hash_key . ':*');

		if (count($pages) > 0)
			foreach ($pages as $key)
				self::$instance->del($key);

		Config::render((object) [
			'body' => '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="2; url=\'' . Config::$domain->site . '\'" /></head><body><h4>Перенаправление на главную...</h4><p>Очищено элементов: ' . count($pages) . '</p></body></html>',
			'content_type' => 'text/html; charset=UTF-8'
		]);
	}

	/**
	 * Функция извлекает все ключи из кэша Redis и отображает количество кэшированных страниц или сообщение
	 * о том, что кэш пуст.
	 */
	public static function keys() : void
	{
		$pages = self::$instance->keys(Config::$hash_key . ':*');

		if (count($pages) > 0)
			self::notice('pages cached: ' . count($pages));
		else
			self::notice('cache is empty');

	}
	
	/**
	 * Эта функция PHP извлекает объект из кеша с помощью хэша и декодирует его тело из base64.
	 * 
	 * @param string hash Параметр «хэш» — это строка, представляющая уникальный идентификатор объекта,
	 * который необходимо получить.
	 * 
	 * @return object объект. Если параметр  совпадает с сохраненным хэшем, функция извлекает
	 * сохраненный объект, декодирует его свойство body из base64 и возвращает результирующий объект. Если
	 * параметр `` не соответствует сохраненному хешу, функция возвращает пустой объект.
	 */
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
	 * Эта функция устанавливает значение кэша с заданным хешем и сроком действия на основе свойств
	 * входного объекта.
	 * 
	 * @param object obj Объект, который необходимо кэшировать. Он содержит тело и тип содержимого.
	 * @param string hash Параметр hash — это строка, которая служит уникальным идентификатором
	 * кэшированного объекта. Он используется для последующего извлечения кэшированного объекта.
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
	 * Функция очищает веб-кеш, устанавливая новое пустое значение со сроком действия и отображая
	 * HTML-страницу с перенаправлением на домашнюю страницу.
	 * 
	 * @return HTML-страница с метатегом обновления, который перенаправляет пользователя на домашнюю
	 * страницу через 2 секунды. Страница также содержит хеш-значение и сообщение о том, что пользователь
	 * перенаправляется на домашнюю страницу.
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

		Config::render((object) [
			'body' => '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="2; url=\'/?clear\'" /><script>function setCookie(name,value,days) { var expires = ""; if (days) { var date = new Date(); date.setTime(date.getTime() + (days*24*60*60*1000)); expires = "; expires=" + date.toUTCString(); } document.cookie = name + "=" + (value || "")  + expires + "; path=/"; }; setCookie("' . Config::$name . '", "' . $hash . '",' . Config::$config->cache->expire . ');</script></head><body><h4>Перенаправление на главную...</h4><p>Hash: ' . $hash . '</p></body></html>',
			'content_type' => 'text/html; charset=UTF-8'
		]);
	}

	/**
	 * Функция вставляет элемент div со ссылкой для очистки содержимого веб-страницы, если установлен
	 * определенный файл cookie.
	 * 
	 * @param string html строка, содержащая код HTML, который необходимо изменить, внедрив элемент div в
	 * верхний левый угол страницы.
	 * 
	 * @return string строка, которая представляет собой либо исходный HTML-код, переданный в качестве
	 * аргумента, либо исходный HTML-код с внедренным элементом div в верхнем левом углу страницы, если
	 * выполняется определенное условие.
	 */
	public static function injectWebCleaner(string $html) : string
	{
		if (isset($_COOKIE['wilda']))
		{
			$exp = explode(':', $_COOKIE['wilda']);

			if (isset($exp[1]) && Encryption::decode($exp[1]) === Config::$config->salt)
			{
				$inject_html = '<div style="position:fixed;z-index:99999;left:0;top:0;padding:3px 6px;background-color:rgba(0,0,0,0.4"><a style="text-decoration:none;color:#fff;font-size:16pt;font-weight:normal" href="/?clear">&#10227;</a></div>';
				return str_replace('</body>', $inject_html . '</body>', (string) $html);
			}

		}

		return (string) $html;
	}

	/**
	 * Эта функция PHP вводит веб-статистику в HTML-код, если включено кэширование.
	 * 
	 * @param html Код HTML, который необходимо изменить путем внедрения веб-статистики.
	 * 
	 * @return string строка, которая представляет собой либо исходный HTML-код, переданный в качестве
	 * аргумента, либо исходный HTML-код с дополнительным комментарием, введенным перед закрывающим тегом
	 * `</body>`. Комментарий содержит время работы кеша, которое вычисляется с помощью метода
	 * `microtimeAgo` из класса `Config`. Решение вводить комментарий или нет основано на значении `stats
	 */
	public static function injectWebStats(string $html) : string
	{
		if (Config::$config->cache->stats)
		{
			$inject_html = '<!-- Cache runtime: ' . Config::microtimeAgo(self::$microtime) . ' -->';
			return str_replace('</body>', $inject_html . '</body>', (string) $html);
		}

		return (string) $html;
	}

	/**
	 * Эта функция устанавливает версию конфигурации, кодируя данные в формате JSON и сохраняя их с
	 * уникальным хэш-ключом.
	 * 
	 * @param array data  — это массив данных, которые нужно хранить в кеше с уникальным ключом.
	 * Данные кодируются в виде строки JSON с использованием функции json_encode с параметрами
	 * JSON_UNESCAPED_SLASHES и JSON_UNESCAPED_UNICODE, чтобы убедиться, что
	 * 
	 * @return string строка, представляющая собой хэш, сгенерированный объединением свойства
	 *  и текущей временной метки, а затем ее использованием в качестве ключа для сохранения
	 * массива  в кеше с использованием метода set экземпляра . ` объект.
	 */
	public static function setConfigRevision(array $data) : string
	{
		$hash = self::$revision_key . ':' . \time();
		self::$instance->set(
			(string) $hash,
			(string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
		);

		return $hash;
	}
	
	/**
	 * Эта функция извлекает все версии конфигурации, хранящиеся в Redis, и возвращает их в виде массива.
	 * 
	 * @return array Массив, содержащий все значения, хранящиеся в ключах Redis, которые соответствуют
	 * шаблону `self::. ':*'`.
	 */
	public static function getAllConfigRevisions() : array
	{
		$array = [];
		$keys = self::$instance->keys(self::$revision_key . ':*');

		if (count($keys) > 0)
			foreach ($keys as $key)
				$array[$key] = self::$instance->get($key);

		return $array;
	}

	/**
	 * Функция возвращает массив всех ключей для ревизий конфигурации, отсортированных по возрастанию.
	 * 
	 * @return array Массив, содержащий список ключей ревизий с префиксом `self::`,
	 * отсортированный по возрастанию. Массив заключен в другой массив с ключом `'revisions'`.
	 */
	public static function getAllKeysConfigRevisions() : array
	{
		$array = [];
		$keys = self::$instance->keys(self::$revision_key . ':*');
		foreach ($keys as $key)
			$array[] = str_replace(self::$revision_key . ':', '', $key);

		usort($array, function (int $a, int $b) {
			return (int) $a - (int) $b;
		});

		return [ 
			'revisions' => $array
		];
	}

	/**
	 * Эта функция PHP возвращает массив версий конфигурации на основе заданного ключа версии.
	 * 
	 * @param string revision Параметр «revision» — это строковая переменная, представляющая версию или
	 * номер редакции конфигурации. Он используется для извлечения данных конфигурации, связанных с этой
	 * конкретной версией, из кэша с использованием хранилища ключей и значений Redis.
	 * 
	 * @return array Возвращается массив. Массив является результатом декодирования строки JSON,
	 * полученной из кэша Redis с использованием указанного ключа версии.
	 */
	public static function getConfigRevision(string $revision) : array
	{
		return (array) json_decode(self::$instance->get(self::$revision_key . ':' . $revision));
	}
	
	
	/**
	 * Эта функция PHP удаляет все ключи, связанные с данной ревизией.
	 * 
	 * @param string revision Параметр «ревизия» — это строка, представляющая версию или ревизию
	 * конфигурации. Эта функция используется для удаления всех ключей, связанных с определенной версией
	 * конфигурации.
	 */
	public static function delConfigRevision(string $revision) : void
	{
		$keys = self::$instance->keys(self::$revision_key . ':' . $revision);

		if (count($keys) > 0)
			foreach ($keys as $key)
				self::$instance->del($key);

	}

}