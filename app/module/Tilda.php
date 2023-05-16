<?php

namespace app\module;


use app\core\Tags;
use app\core\Config;

/**
 * Tilda Controller
 */
class Tilda extends Tags
{

    function __construct()
    {
		// var_dump('123');
	}

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
						$elem->setAttribute('href', Config::$domain->site . '/' . Config::$favicon->path . '/default.ico');

	}

	public static function javascript($body)
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
		$project_name = Config::getProjectName();
		$site_name = Config::getSiteName();

		foreach (self::$dom->getElementsByTagName('a') as $tag)
			if (str_contains($tag->getAttribute('href'), $project_name))
			{
				$proto_url = Config::forceProto($tag->getAttribute('href'));
				$parse_url = parse_url($proto_url);
				$tag->setAttribute(
					'href',
					str_replace($parse_url['host'], $site_name, $proto_url)
				);
			}
			elseif ($tag->getAttribute('href') == Config::$domain->project . Config::$route->path)
				$tag->setAttribute(
					'href',
					Config::$route->url
				);

	}

	/**
	 * [changeScriptTags description]
	 * @return [type] [description]
	 */
	public static function changeScriptTags(): void
	{
		foreach (self::$dom->getElementsByTagName('script') as $index => $script) {
			switch (Config::$config->scripts) {
				case 'relative':

					switch ($script->getAttribute('id')) {
						case 'sentry':
							$script->parentNode->removeChild($script);
							break;

					}

					$src = $script->getAttribute('src');
					$data_url = $script->getAttribute('data-url');
					
					if (!empty($src))
						$script->setAttribute('src', Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($src), 'scripts'));

					if (!empty($data_url))
						$script->setAttribute('data-url', Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($data_url), 'scripts'));


					/**
					 * tag <script>
					 */
					preg_match('/static\.tildacdn\.info/', $script->textContent, $matched);
					if (count($matched) > 0) {
						$script->textContent = preg_replace_callback(
							"/s\.src = \'(.*)\'/",
							function ($matches) {
								if (isset($matches[ 1 ]))
									return "s.src = '" . Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($matches[ 1 ]), 'scripts') . "'";
							},
							$script->textContent
						);

					}


					break;
			}
		}
	}


	public static function robots(object $content): object
	{
		$proto   = [ 'http://', 'https://' ];
		$project = str_replace($proto, '', Config::$domain->project);
		$site    = str_replace($proto, '', Config::$domain->site);

		// change host
		if (preg_match('/project/', $project))
			$content->body = preg_replace(
				'/Host:.*/',
				'Host: ' . Config::$route->domain,
				$content->body
			);
		else
			$content->body = str_replace(
				$project,
				$site,
				$content->body
			);

		// remove disallow directives
		$content->body = str_replace(
			'Disallow: /',
			'',
			$content->body
		);

		return $content;
	}

	public static function html(string $html): string
	{
		self::initialize(
			self::preProcessHTML($html)
		);

		self::initialize($html);
		self::changeDomElements();
		self::changeAHrefLinks();
		self::changeScriptTags();
		self::removeTildaCopy();
		self::removeCounters();
		self::changeSubmitSuccessMessage();
		self::changeFavicon();
		self::changeImgTags();

		return self::postProcessHTML();
	}


	/**
	 * [changeImgTags description]
	 * @return [type] [description]
	 */
	public static function changeImgTags(): void
	{

		/**
		 * tag <style>
		 */
		foreach (self::$dom->getElementsByTagName('style') as $style)
			switch (Config::$config->images) {
				case 'relative':

					preg_match('/static\.tildacdn\.info/', $style->textContent, $matched);
					if (count($matched) > 0) {
						$style->textContent = preg_replace_callback(
							"/background\-image\: url\(\'(.*)\'\)/",
							function ($matches) {
								if (isset($matches[ 1 ])) {
									return "background-image: url('" . Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($matches[ 1 ]), 'images') . "')";
								}
							},
							$style->textContent
						);

					}
					break;
			}

	}


	public static function css(string $content): string
	{
		preg_match('/static\.tildacdn\.com/', $content, $matched);
		if (count($matched) > 0) {
			$content = preg_replace_callback(
				"/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i",
				function ($matches) {
					if (isset($matches[ 3 ]))
					{
						// var_dump($matches[3]);
						return "url('" . Config::QUERY_PARAM_FONT . self::getRelativePath(self::parseURL($matches[ 3 ]), 'fonts') . "')";
					}
				},
				$content
			);
			// var_dump($matched);
		}

		// $content = str_replace('', '', $content);
		return $content;
	}

}

