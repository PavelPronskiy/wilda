<?php

namespace app\module;


use app\core\Tags;
use app\core\Config;

/**
 * Tilda Controller
 */
class Tilda extends Tags
{

	/**
	 * [changeSubmitSuccessMessage description]
	 * @return [type] [description]
	 */
	public static function changeSubmitSuccessMessage()
	{
		if (Config::$mail->enabled)
			foreach (self::getElementsByClass(self::$dom, 'div', 'js-successbox') as $elem)
				$elem->setAttribute('data-success-message', Config::$mail->success);
	}

	/**
	 * [changeFavicon description]
	 * @return [type] [description]
	 */
	public static function changeFavicon()
	{
		if (Config::$favicon->enabled)
			foreach (self::$dom->getElementsByTagName('link') as $elem)
				if (stripos($elem->getAttribute('rel'), 'shortcut icon') === 0)
					if (file_exists(Config::$favicon->path . '/' . Config::getSiteName() . '.ico'))
						$elem->setAttribute('href', Config::$domain->site . '/' . Config::$favicon->path . '/' . Config::getSiteName() . '.ico');
					else
						$elem->setAttribute('href', Config::$domain->site . '/' . Config::$favicon->path . '/' . Config::$favicon->default);

	}

	/**
	 * [javascriptContentReplace description]
	 * @param  [type] $body [description]
	 * @return [type]       [description]
	 */
	public static function javascriptContentReplace($body)
	{
		if (Config::$mail->enabled)
			$body = str_replace('forms.tildacdn.com', Config::getSiteName(), $body);
		
		return $body;
	}

	/**
	 * [removeTildaCopy description]
	 * @return [type] [description]
	 */
	public static function removeTildaCopy() : void
	{
		$tildacopy = self::$dom->getElementById('tildacopy');
		if ($tildacopy)
			$tildacopy->parentNode->removeChild($tildacopy);
	}

	/**
	 * [removeCounters description]
	 * @return [type] [description]
	 */
	public static function removeCounters() : void
	{
		foreach (self::$dom->getElementsByTagName('script') as $script)
			if (strlen($script->textContent) == 564)
				$script->parentNode->removeChild($script);
	}

	/**
	 * [changeAHrefLinks description]
	 * @return [type] [description]
	 */
	public static function changeAHrefLinks() : void
	{
		foreach (self::$dom->getElementsByTagName('a') as $tag)
			if ($tag->getAttribute('href') == Config::$domain->project . Config::$route->path)
				$tag->setAttribute(
					'href',
					Config::$route->url
				);

	}


}

