<?php

namespace Tilda;

class Controller
{
	function __construct()
	{
		$this->curl = \curl_init();
		//$this->dom = new \DOMDocument;
		//$this->dom->validateOnParse = false;
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
		curl_setopt($this->curl, CURLOPT_HEADER, true);
		curl_setopt($this->curl, CURLOPT_ENCODING, "gzip");

		$response = curl_exec($this->curl);
		$header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
		$http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

		if ($this->curlErrorHandler($http_code)) {
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);
			$this->etag = $this->headersPrepare($header, 'ETag');
			return $body;
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

	private function removeCopyrights($body)
	{
		$this->dom->loadHTML($body);
		$tildacopy = $this->dom->getElementById('tildacopy');
		$tildacopy->parentNode->removeChild($tildacopy);
		return $this->dom->saveHTML();
	}
	
	private function encrypt_url($string)
	{
		return $this->encrypt->encode($string);
	}

	private function decrypt_url($string)
	{
		return $this->encrypt->decode($string);
	}

	private function removeTags($body)
	{
		$dom = new \DOMDocument;
		$dom->loadHTML($body);
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
				case 'shortcut icon': $link->setAttribute('href', $this->site . '/favicon.ico'); break;
				case 'dns-prefetch':
					$link->setAttribute('href', $this->site);
				break;
				case 'stylesheet':
					switch ($this->config->styles) {
						case 'relative':
							$relative = '/?css=' . $this->encrypt_url($link->getAttribute('href'));
							$link->setAttribute('href', $relative);
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
						$relative = '/?js=' . $this->encrypt_url($src);
						$script->setAttribute('src', $relative);
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
								$relative = '/?img=' . $this->encrypt_url($content);
								$meta->setAttribute('content', $relative);
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
								$relative = '/?img=' . $this->encrypt_url($content);
								$meta->setAttribute('content', $relative);
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
						$relative = '/?img=' . $this->encrypt_url($src);
						$img->setAttribute('src', $relative);
					}
				break;
			}

		}
		
		return $dom->saveHTML();
	}

	private function getImageContentType($img)
	{
		if(@end(explode(".", $img)) == "svg") {
			return 'image/svg+xml';
		}
		
		if(@end(explode(".",$img)) == "png") {
			return 'image/png';
		}

		if(@end(explode(".",$img)) == "jpg") {
			return 'image/jpeg';
		}
		if(@end(explode(".",$img)) == "gif") {
			return 'image/gif';
		}
	}

	private function route()
	{
		$expire_files = 1440;
		$request_uri = (object)parse_url($_SERVER['REQUEST_URI']);
		//var_dump(parse_url($_SERVER['REQUEST_URI']));
		if (isset($request_uri->query)) {
			parse_str($request_uri->query, $query);
			$query = (object)$query;
			if (isset($query->css)) {
				$hash = $_SERVER['HTTP_HOST'] . ':' . $query->css;
				header("Content-type: text/css", true);
				if ($this->config->cache->enabled) {
					$cache = $this->cache->get($hash);
					if (!empty($cache)) {
						die($cache);
					} else {
						$data = $this->get($this->decrypt_url($query->css));
						$this->cache->set($data, $hash, $expire_files);
						die($data);
					}
				}
				
				die($this->get($this->decrypt_url($query->css)));
			}
			if (isset($query->js)) {
				$hash = $_SERVER['HTTP_HOST'] . ':' . $query->js;
				header("Content-type: application/javascript", true);
				if ($this->config->cache->enabled) {
					$cache = $this->cache->get($hash);
					if (!empty($cache)) {
						die($cache);
					} else {
						$data = $this->get($this->decrypt_url($query->js));
						$this->cache->set($data, $hash, $expire_files);
						die($data);
					}
				}
				
				die($this->get($this->decrypt_url($query->js)));
			}
			if (isset($query->img)) {
				$hash = $_SERVER['HTTP_HOST'] . ':' . $query->img;
				$content_type = $this->getImageContentType($this->decrypt_url($query->img));
				header("Content-type: " . $content_type);
				if ($this->config->cache->enabled) {
					$cache = $this->cache->get($hash);
					if (!empty($cache)) {
						die($cache);
					} else {
						$data = $this->get($this->decrypt_url($query->img));
						//var_dump($this->decrypt_url($query->js));
						$this->cache->set($data, $hash, $expire_files);
						die($data);
					}
				}
				
				die($this->get($this->decrypt_url($query->img)));
			}
		} else {
			$this->tildaInstance();
		}
	}

	private function tildaInstance()
	{
		$host = $this->getTildaHost();
		
		if (!isset($host->site)) {
			die('Hosts not defined');
		}
		
		$this->site = $host->proto . '://' . $host->site;
		$this->cache->hash = $host->site;
		$tilda = 'http://' . $host->tilda;
		
		if ($this->config->cache->enabled) {
			$cache = $this->cache->get();
		} else {
			$cache = false;
		}
		
		if ($cache) {
			die($cache);
		} else {
			$body = $this->get($tilda);
			if ($body !== false) {
				$body = $this->removeTags($body);
				if ($this->config->cache->enabled) {
					$this->cache->set($body);
				} else {
					die($body);
				}
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