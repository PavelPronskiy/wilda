<?php

namespace app\module;

use app\core\Tags;

/**
 * Wix Controller
 */
class Wix extends Tags
{
	public static function changeWixOptions() : void
	{
		$xpath = new \DOMXPath(self::$dom);
		$nodes = $xpath->query('//script[@id="wix-viewer-model"]');
		$route_url = str_replace('http://', 'https://', Config::$route->url);
		$base_url = str_replace('http://', 'https://', Config::$domain->site);
		$features_exclude = [
			// 'thunderboltInitializer',
		];
		foreach ($nodes as $key => $node) {
			$dec = json_decode($node->nodeValue);
			// var_dump($dec);
			if (isset($dec->siteFeaturesConfigs->platform->bootstrapData->location->domain))
				$dec->siteFeaturesConfigs->platform->bootstrapData->location->domain = Config::$route->domain;

			if (isset($dec->siteFeaturesConfigs->platform->bootstrapData->location->externalBaseUrl))
				$dec->siteFeaturesConfigs->platform->bootstrapData->location->externalBaseUrl = $base_url;
			
			if (isset($dec->site->externalBaseUrl))
				// $dec->site->externalBaseUrl = Config::$domain->project;
				$dec->site->externalBaseUrl = $base_url;
				// $dec->site->externalBaseUrl = preg_replace('#/$#', '', $route_url);

			if (isset($dec->siteFeaturesConfigs->tpaCommons->externalBaseUrl))
				$dec->siteFeaturesConfigs->tpaCommons->externalBaseUrl = $base_url;
			if (isset($dec->siteFeaturesConfigs->router->baseUrl))
				$dec->siteFeaturesConfigs->router->baseUrl = $base_url;

			if (isset($dec->siteFeaturesConfigs->seo->context->siteUrl))
				$dec->siteFeaturesConfigs->seo->context->siteUrl = $route_url;

			if (isset($dec->siteFeaturesConfigs->seo->context->defaultUrl))
				$dec->siteFeaturesConfigs->seo->context->defaultUrl = $route_url;
			
			if (isset($dec->requestUrl))
				$dec->requestUrl = $route_url;
			
			if (isset($dec->siteFeaturesConfigs->locationWixCodeSdk->baseUrl))
				$dec->siteFeaturesConfigs->locationWixCodeSdk->baseUrl = $base_url;

			if (isset($dec->siteFeaturesConfigs->siteWixCodeSdk->baseUrl))
				$dec->siteFeaturesConfigs->siteWixCodeSdk->baseUrl = $base_url;
			
			if (isset($dec->siteFeaturesConfigs->tpaCommons->requestUrl))
				$dec->siteFeaturesConfigs->tpaCommons->requestUrl = $route_url;
			
			if (isset($dec->siteAssets->modulesParams->features->externalBaseUrl))
				$dec->siteAssets->modulesParams->features->externalBaseUrl = $base_url;

			if (isset($dec->siteAssets->modulesParams->platform->externalBaseUrl))
				$dec->siteAssets->modulesParams->platform->externalBaseUrl = $base_url;

/*			foreach ($dec->siteFeatures as $key => $feature)
			{
				if (in_array($feature, $features_exclude))
					unset($dec->siteFeatures[$key]);
			}*/

			$node->nodeValue = '';
			$node->appendChild(self::$dom->createTextNode(json_encode($dec)));
			// $dec->siteFeaturesConfigs = '';
			//var_dump(Config::$route->url);

		}

		$nodes = $xpath->query('//script[@id="wix-fedops"]');

		foreach ($nodes as $key => $node) {
			$dec = json_decode($node->nodeValue);
			$dec->data->site->externalBaseUrl = $route_url;
			$dec->data->requestUrl = $route_url;

			$node->nodeValue = '';
			$node->appendChild(self::$dom->createTextNode(json_encode($dec)));
		}
	}

	/**
	 * [changeAHrefLinks description]
	 * @return [type] [description]
	 */
	public static function changeAHrefLinks() : void
	{
		// var_dump(Config::$route);
		$project_parse_url = parse_url(Config::$domain->project);
		foreach (self::$dom->getElementsByTagName('a') as $tag)
			if (isset($project_parse_url['host']))
				$tag->setAttribute(
					'href',
					str_replace(Config::$domain->project, '', $tag->getAttribute('href'))
				);

	}

	/**
	 * [changeHtmlTags description]
	 * @return [type] [description]
	 */
	public static function changeHtmlTags() : void
	{
		foreach (self::$dom->getElementsByTagName('div') as $tag)
		{
			// site-root
			if ($tag->getAttribute('id') == 'WIX_ADS') {
				$tag->setAttribute('style', 'display:none');
			}

			if ($tag->getAttribute('id') == 'site-root') {
				$tag->setAttribute('style', 'top:0px');
			}
		}
	}
} 
