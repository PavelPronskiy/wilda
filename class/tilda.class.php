<?php

namespace Tilda;

use Masterminds\HTML5;

class Controller
{
	function __construct()
	{
		$this->curl = \curl_init();
		\libxml_use_internal_errors(true);
		$this->config = Config::getConfig();
		$this->cache = new \Cache\Controller;
		$this->encrypt = new Encryption();
		$this->etag = '';
		$this->route();
	}

	function __destruct()
	{
		curl_close($this->curl);
	}

	private function headersPrepare($headers, $tag)
	{
		foreach(explode(PHP_EOL, $headers) as $index => $header)
		{
			$h = explode(':', trim($header));
			if ($h[0] == $tag) {
				return str_replace('W/', '', str_replace('"', '', $h[1]));
			}
		}
	}

	private function get($url)
	{
	
		if ($this->config->privoxy->enabled) {
			curl_setopt($this->curl, CURLOPT_PROXY,
				$this->config->privoxy->host . ':' . $this->config->privoxy->port);
		}
		
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->config->headers);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_setopt($this->curl, CURLOPT_ENCODING, "gzip");

		$response = curl_exec($this->curl);
		$header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
		$http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

		if ($this->curlErrorHandler($http_code)) {
			$header = substr($response, 0, $header_size);
			return $response;
		} else {
			return false;
		}
	}

	private function curlErrorHandler($http_code)
	{
		switch($http_code)
		{
			case 503:
				return false;
			break;
			
			case 200:
				return true;
			break;
			
			default:
				return false;
			break;
		}
	}

	private function removeCopyrights($html)
	{
		$this->dom->loadHTML($html);
		$tildacopy = $this->dom->getElementById('tildacopy');
		$tildacopy->parentNode->removeChild($tildacopy);
		return $this->dom->saveHTML();
	}
	
	private function encryptUrl($string)
	{
		return $this->encrypt->encode($string);
	}

	private function decryptUrl($string)
	{
		return $this->encrypt->decode($string);
	}

	private function parseURL($src)
	{
		$url = parse_url($src);
		// $results = '';

		return isset($url['host'])
			? $src
			: 'http://' . $this->tilda . $src;

	}

	private function removeTags($html)
	{
		$dom_html5 = new \Masterminds\HTML5([
			'disable_html_ns' => true
		]);

		$dom = $dom_html5->loadHTML($html);

		$tildacopy = $dom->getElementById('tildacopy');
		$metatags = $dom->getElementsByTagName('meta');
		$scripts = $dom->getElementsByTagName('script');
		$linktags = $dom->getElementsByTagName('link');
		$imgs = $dom->getElementsByTagName('img');
		$xpath = new \DOMXPath($dom);

		if ($tildacopy) {
			$tildacopy->parentNode->removeChild($tildacopy);
		}

		while ($node = $xpath->query('//comment()')->item(0))
		{
			$node->parentNode->removeChild($node);
		}

		foreach ($linktags as $link)
		{
			switch (strtolower($link->getAttribute('rel'))) {
				case 'canonical': $link->setAttribute('href', $this->site); break;
				case 'shortcut icon':
					// $link->setAttribute('href', $this->site . '/favicon.ico');
					$src = $link->getAttribute('href');
					if (!empty($src)) {
						$parse_src = $this->parseURL($src);
						$link->setAttribute('href', '/?ico=' . $this->encrypt->encode($parse_src));
					}
					break;

				case 'dns-prefetch':
					$link->setAttribute('href', $this->site);
				break;
				case 'stylesheet':
					switch ($this->config->styles) {
						case 'relative':
							$src = $link->getAttribute('href');
							if (!empty($src)) {
								$parse_src = $this->parseURL($src);
								$link->setAttribute('href', '/?css=' . $this->encrypt->encode($parse_src));
							}
						break;
					}

				break;
			}
		}

		foreach ($scripts as $index => $script)
		{
			if (preg_match('/\bwindow.mainTracker\b/', $script->nodeValue)) {
				$script->parentNode->removeChild($script);
			}

			switch ($this->config->scripts) {
				case 'relative':
					$src = $script->getAttribute('src');

					if (!empty($src)) {
						$parse_src = $this->parseURL($src);
						$script->setAttribute('src', '/?js=' . $this->encrypt->encode($parse_src));
					}
				break;
			}
		}
		
		foreach ($metatags as $meta)
		{
			switch (strtolower($meta->getAttribute('itemprop'))) {
				case 'image':
					switch ($this->config->images) {
						case 'relative':
							$content = $meta->getAttribute('content');
							if (!empty($content)) {
								$meta->setAttribute('content', '/?img=' . $this->encrypt->encode($content));
							}
						break;
					}
				break;
			}

			switch (strtolower($meta->getAttribute('http-equiv'))) {
				case 'x-dns-prefetch-control':
				break;
			}
			
			switch (strtolower($meta->getAttribute('name'))) {
				case 'robots': $meta->parentNode->removeChild($meta); break;
			}

			switch (strtolower($meta->getAttribute('property'))) {
				case 'og:url': $meta->setAttribute('content', $this->site); break;
				case 'og:image':
					switch ($this->config->images) {
						case 'relative':
							$content = $meta->getAttribute('content');
							if (!empty($content)) {
								// $relative = '/?img=' . $this->encrypt->encode($content);
								$meta->setAttribute('content', '/?img=' . $this->encrypt->encode($content));
							}
						break;
					}
				break;
			}
		}
		
		foreach ($imgs as $img)
		{
			switch ($this->config->images) {
				case 'relative':
					$src = $img->getAttribute('src');
					if (!empty($src)) {
						$parse_src = $this->parseURL($src);
						// $relative = '/?img=' . $this->encrypt->encode($src);
						$img->setAttribute('src', '/?img=' . $this->encrypt->encode($parse_src));
					}
				break;
			}

		}
		
		return $dom->saveHTML();
	}

	private function getImageContentType($img)
	{
		$mimes = new \Mimey\MimeTypes;
		foreach (['svg', 'png', 'jpg', 'gif'] as $type) {
			if(@end(explode(".", $img)) == $type) {
				return $mimes->getMimeType($type);
			}
		}
	}

	private function route()
	{
		$request_uri = (object)parse_url($_SERVER['REQUEST_URI']);

		if (isset($request_uri->query)) {
			parse_str($request_uri->query, $query);
			$query = (object)$query;

			if (isset($query->cache)) {
				return $this->cache->flush($query->cache);
			}

			foreach (['css', 'js', 'img', 'ico'] as $type) {
				if (isset($query->{$type})) {
					switch ($type) {
						case 'css': $this->getItem($query->{$type}, 'text/css'); break;
						case 'js': $this->getItem($query->{$type}, 'application/javascript'); break;
						default: $this->getItem($query->{$type}); break;
					}
				}
			}

		} else {
			$this->tildaInstance($request_uri);
		}
	}

	private function getItem($query, $type = '')
	{
		$hash = $_SERVER['HTTP_HOST'] . ':' . $query;

		$type = !empty($type)
			? $type
			: $this->getImageContentType($this->decryptUrl($query));

		if ($this->config->cache->enabled) {
			$data = $this->cache->get($hash);
			if (empty($data)) {
				$data = $this->get($this->decryptUrl($query));
				$this->cache->set($data, $hash);
			}
		} else {
			$data = $this->get($this->decryptUrl($query));
		}
		
		$this->render($data, $type);
	}

	private function render($data, $mime)
	{
		header("Content-type: " . $mime);
		die($data);
	}

	private function tildaInstance($request_uri)
	{
		$host = $this->getTildaHost();
		
		if (!isset($host->site)) {
			die('Hosts not defined');
		}

		
		$req_path = isset($request_uri->path) ? $request_uri->path : '';
		
		$this->site = $host->proto . '://' . $host->site . $req_path;
		$this->cache->hash = $host->site . $req_path;
		$this->tilda = $host->tilda;
		$tilda = 'http://' . $host->tilda . $req_path;


		if ($this->config->cache->enabled) {
			$cache = $this->cache->get();
		} else {
			$cache = false;
		}

		if ($this->config->cache->enabled && !empty($cache)) {
			die($cache);
		} else {
			$body = $this->get($tilda);
			if ($body !== false) {
				$body = $this->removeTags($body);
				if ($this->config->cache->enabled) {
					$this->cache->set($body);
				}
				
				die($body);
			} else {
				die('Host: ' . $host->tilda . ' unavailable');
			}
		}
	}

	private function getTildaHost()
	{
		foreach ($this->config->hosts as $host) {
			if ($_SERVER['HTTP_HOST'] == $host->site) {
				return $host;
			}
		}
	}
}