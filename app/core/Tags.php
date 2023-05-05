<?php

namespace app\core;

use app\core\Config;
use app\util\Encryption;
use app\util\Cache;
use app\module\Tilda;
use app\module\Wix;
use zz\Html\HTMLMinify;
/**
 * Tags
 */
abstract class Tags
{
	public static $dom;

	/**
	 * [getElementsByClass description]
	 * @param  [type] &$parentNode [description]
	 * @param  [type] $tagName     [description]
	 * @param  [type] $className   [description]
	 * @return [type]              [description]
	 */
	public static function getElementsByClass(&$parentNode, $tagName, $className)
	{
		$nodes = [];

		$childNodeList = $parentNode->getElementsByTagName($tagName);
		for ($i = 0; $i < $childNodeList->length; $i++)
		{
			$temp = $childNodeList->item($i);
			if (stripos($temp->getAttribute('class'), $className) !== false)
			{
				$nodes[]=$temp;
			}
		}

		return $nodes;
	}


	/**
	 * [injectHTML description]
	 * @param  [type] $html [description]
	 * @return [type]       [description]
	 */
	private static function injectHTML($html)
	{
		$path_header = Config::$inject->path . '/' . Config::getSiteName() . '-header.html';
		$path_footer = Config::$inject->path . '/' . Config::getSiteName() . '-footer.html';

		if (Config::$inject->enabled)
		{
			if (Config::$inject->header)
				if (file_exists($path_header))
					$html = str_replace('</head>', file_get_contents($path_header) . '</head>', $html);

			if (Config::$inject->footer)
				if (file_exists($path_footer))
					$html = str_replace('</body>', file_get_contents($path_footer) . '</body>', $html);

		}

		return $html;
	}

	/**
	 * [injectMetrics description]
	 * @return [type] [description]
	 */
	private static function injectMetrics()
	{
		if (Config::$metrics->enabled)
		{
			if (Config::$metrics->ya)
				foreach (self::$dom->getElementsByTagName('head') as $node)
					if (file_exists(Config::$metrics->path . '/ya.js'))
						$node->appendChild(
							self::$dom->createElement('script',
								str_replace(
									'{{YANDEX_METRIKA}}',
									Config::$metrics->ya,
									file_get_contents(Config::$metrics->path . '/ya.js')
								)
							)
						);

			if (Config::$metrics->ga)
			{
				foreach (self::$dom->getElementsByTagName('head') as $node)
					if (file_exists(Config::$metrics->path . '/ga.js'))
					{
						$ga_src = self::$dom->createElement('script');
						$ga_src->setAttribute('src', 'https://www.googletagmanager.com/gtag/js?id=' . Config::$metrics->ga);
						$node->appendChild($ga_src);
						$node->appendChild(
							self::$dom->createElement('script',
								str_replace(
									'{{GOOGLE_ANALYTICS}}',
									Config::$metrics->ga,
									file_get_contents(Config::$metrics->path . '/ga.js')
								)
							)
						);
					}
			}

		}
	}

	/**
	 * [compressHTML description]
	 * @param  [type] $html [description]
	 * @return [type]       [description]
	 */
	private static function compressHTML($html) : string
	{
		if (Config::$compress)
			$html = preg_replace([
				'/\>[^\S ]+/s',
				'/[^\S ]+\</s',
				'/(\s)+/s',
				'/<!--(.|\s)*?-->/',
				'/\n+/'
			], [
				'>',
				'<',
				'\\1',
				'',
				' '
			], $html);
		
		return $html;
	}

	/**
	 * [changeBaseHref description]
	 * @return [type] [description]
	 */
	private static function changeBaseHref() : void
	{
		foreach (self::$dom->getElementsByTagName('base') as $b)
			$b->setAttribute(
				'href',
				Config::$route->url
			);
	}

	/**
	 * [changeRobotsHost description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public static function changeRobotsHost($content) : object
	{
		$proto = ['http://', 'https://'];
		switch (Config::$domain->type)
		{
			case 'tilda':
				$project = str_replace($proto , '', Config::$domain->project);
				$site = str_replace($proto , '', Config::$domain->site);

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
				
				break;
			
			default:
				break;
		}

		return $content;
	}

	/**
	 * [removeComments description]
	 * @return [type] [description]
	 */
	private static function removeComments() : void
	{
		$xpath = new \DOMXPath(self::$dom);

		while ($node = $xpath->query('//comment()')->item(0))
			$node->parentNode->removeChild($node);

	}

	/**
	 * [getRelativePath description]
	 * @param  [type] $content [description]
	 * @param  [type] $type    [description]
	 * @return [type]          [description]
	 */
	public static function getRelativePath($content, $type) : string
	{
		$path = $content;
		switch (Config::$config->{$type})
		{
			case 'relative':
				$path = Encryption::encode($content);
			break;

			case 'absolute':
			default:
				$path = $content;
			break;
		}

		return $path;
	}

	/**
	 * [changeMetaTags description]
	 * @return [type] [description]
	 */
	private static function changeMetaTags() : void
	{
		foreach (self::$dom->getElementsByTagName('meta') as $meta)
		{
			switch (strtolower($meta->getAttribute('itemprop')))
			{
				case 'image':
					$content = $meta->getAttribute('content');

					if (!empty($content))
					{
						$meta->setAttribute('content', Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($content), 'images'));
					}

				break;
			}

			switch (strtolower($meta->getAttribute('http-equiv')))
			{
				case 'x-dns-prefetch-control': break;
			}
			
			switch (strtolower($meta->getAttribute('name')))
			{
				case 'robots': $meta->parentNode->removeChild($meta); break;
				case 'generator': $meta->parentNode->removeChild($meta); break;
			}

			switch (strtolower($meta->getAttribute('property')))
			{
				case 'og:url':
					$meta->setAttribute('content', Config::$route->url);
				break;

				case 'og:image':
					$content = $meta->getAttribute('content');
					if (!empty($content))
						$meta->setAttribute('content', Config::QUERY_PARAM_IMG . self::getRelativePath($content, 'images'));

				break;
			}
		}
	}

	/**
	 * [changeLinkTags description]
	 * @return [type] [description]
	 */
	private static function changeLinkTags() : void
	{
		$xpath = new \DOMXPath(self::$dom);
		$nodes = $xpath->query('//style');
		foreach ($nodes as $node)
		{
			$attr = $node->getAttribute('data-url');
			if (!empty($attr))
				$node->setAttribute('data-url', Config::QUERY_PARAM_CSS . self::getRelativePath(self::parseURL($attr), 'styles'));

			$attr = $node->getAttribute('data-href');
			if (!empty($attr))
				$node->setAttribute('data-href', Config::QUERY_PARAM_CSS . self::getRelativePath(self::parseURL($attr), 'styles'));

			if (preg_match_all('@url\(\"?//[^/]+[^.]+\.[^.]+?\)@i', $node->nodeValue, $match))
			{
				if (count($match[0]) > 0)
				{
					$nodeValue = $node->nodeValue;

					foreach ($match[0] as $str)
					{
						$str = str_replace('url(', '', $str);
						$str = str_replace(')', '', $str);
						$str = str_replace('"', '', $str);
						$nodeValue = str_replace($str, Config::QUERY_PARAM_FONT . self::getRelativePath('https:' . $str, 'fonts'), $nodeValue);
					}

					$node->nodeValue = '';
					$node->appendChild(self::$dom->createTextNode($nodeValue));
				}
			}
		}

		foreach (self::$dom->getElementsByTagName('link') as $link)
		{
			switch (strtolower($link->getAttribute('rel')))
			{
				case 'preload':
					$src = $link->getAttribute('href');
					if (!empty($src))
						$link->setAttribute('href', Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($src), 'scripts'));
						
					break;
		
				case 'canonical':
					$link->setAttribute('href', Config::$domain->site);
					break;

				case 'icon':
				case 'shortcut icon':
				case 'apple-touch-icon':
					$src = $link->getAttribute('href');
					if (!empty($src))
						$link->setAttribute('href', Config::QUERY_PARAM_ICO . self::getRelativePath(self::parseURL($src), 'icons'));

					break;

				case 'dns-prefetch':
					$link->setAttribute('href', Config::$domain->site);
				break;

				case 'stylesheet':
					switch (Config::$config->styles)
					{
						case 'relative':
							$src = $link->getAttribute('href');
							if (!empty($src))
								$link->setAttribute('href', Config::QUERY_PARAM_CSS . self::getRelativePath(self::parseURL($src), 'styles'));

						break;
					}
				break;

			}
		}

	}

	/**
	 * [changeScriptTags description]
	 * @return [type] [description]
	 */
	private static function changeScriptTags() : void
	{
		foreach (self::$dom->getElementsByTagName('script') as $index => $script)
		{
			switch (Config::$config->scripts)
			{
				case 'relative':

					switch ($script->getAttribute('id'))
					{
						case 'sentry':
							$script->parentNode->removeChild($script);
							break;

						case 'wix-viewer-model':
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
					if (count($matched) > 0)
					{
						$script->textContent = preg_replace_callback(
							"/s\.src = \'(.*)\'/",
							function($matches)
							{
								if (isset($matches[1]))
									return "s.src = '" . Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($matches[1]), 'scripts') . "'";
							},
							$script->textContent
						);

					}


				break;
			}
		}
	}

	/**
	 * [changeImgTags description]
	 * @return [type] [description]
	 */
	private static function changeImgTags() : void
	{

		/**
		 * tag <img>
		 */
		foreach (self::$dom->getElementsByTagName('img') as $img)
			switch (Config::$config->images)
			{
				case 'relative':
					$src = $img->getAttribute('src');
					if (!empty($src))
						$img->setAttribute('src', Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($src), 'images'));

					$src = $img->getAttribute('data-original');
					if (!empty($src))
						$img->setAttribute('data-original',	Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($src), 'images'));

				break;
			}

		/**
		 * tag <style>
		 */
		foreach (self::$dom->getElementsByTagName('style') as $style)
			switch (Config::$config->images)
			{
				case 'relative':

					preg_match('/static\.tildacdn\.info/', $style->textContent, $matched);
					if (count($matched) > 0)
					{
						$style->textContent = preg_replace_callback(
							"/background\-image\: url\(\'(.*)\'\)/",
							function($matches)
							{
								if (isset($matches[1]))
								{
									return "background-image: url('" . Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($matches[1]), 'images') . "')";
								}
							},
							$style->textContent
						);

					}
				break;
			}

		/**
		 * tag image
		 */
		foreach (self::$dom->getElementsByTagName('image') as $img)
			switch (Config::$config->images)
			{
				case 'relative':
					$src = $img->getAttribute('xlink:href');
					if (!empty($src))
						$img->setAttribute('xlink:href', Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($src), 'images'));

				break;
			}

		/**
		 * tag div and attribute data-original
		 */
		foreach (self::$dom->getElementsByTagName('div') as $div)
			switch (Config::$config->images)
			{
				case 'relative':
					$data = $div->getAttribute('data-original');
					if (!empty($data))
						$div->setAttribute('data-original', Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($data), 'images'));

					$data = $div->getAttribute('data-content-cover-bg');
					if (!empty($data))
						$div->setAttribute('data-content-cover-bg', Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($data), 'images'));

					$style = $div->getAttribute('style');
					if (!empty($style))
						$div->setAttribute('style', preg_replace_callback(
							"/background\-image\:\s?url\(\'(.*)\'\)/",
							function($matches)
							{
								if (isset($matches[1]))
								{
									return "background-image: url('" . Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($matches[1]), 'images') . "')";
								}
							},
							$style
						));

				break;
			}
	}

	/**
	 * [parseURL description]
	 * @param  [type] $src [description]
	 * @return [type]      [description]
	 */
	public static function parseURL($src) : string
	{
		$url = parse_url($src);

		return isset($url['host'])
			? $src
			: Config::$domain->project . $src;
	}

	/**
	 * [jsModify description]
	 * @param  [type] $body [description]
	 * @return [type]       [description]
	 */
	public static function jsModify($body)
	{
		switch (Config::$domain->type)
		{
			case 'tilda':
				$body = Tilda::javascriptContentReplace($body);
				break;
		}
		
		return $body;
	}

	/**
	 * [htmlModify description]
	 * @param  [type] $html [description]
	 * @return [type]       [description]
	 */
	public static function htmlModify($html) : string
	{

		$dom_html5 = new \Masterminds\HTML5(['disable_html_ns' => true]);
		$html = self::injectHTML($html);
		$html = Cache::injectWebCleaner($html);
		self::$dom = $dom_html5->loadHTML($html);

		switch (Config::$domain->type)
		{
			case 'wix':
				Wix::changeWixOptions();
				Wix::changeHtmlTags();
				Wix::changeAHrefLinks();
			break;

			case 'tilda':
				Tilda::changeAHrefLinks();
				Tilda::removeTildaCopy();
				Tilda::removeCounters();
				Tilda::changeSubmitSuccessMessage();
				Tilda::changeFavicon();
			break;
		}

		self::changeBaseHref();
		self::changeImgTags();
		self::changeScriptTags();
		self::changeLinkTags();
		self::changeMetaTags();
		self::removeComments();
		self::injectMetrics();


		return self::compressHTML(self::$dom->saveHTML());
	}
}

