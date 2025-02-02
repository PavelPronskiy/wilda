<?php

namespace app\module;

use app\core\Config;
use app\core\Tags;
use app\core\Router;

/**
 * Wix Controller
 */
class Taplink extends Tags
{
    public function __construct()
    {
    }

    /**
     * [changeAHrefLinks description]
     * @return [type] [description]
     */
    public static function changeAHrefLinks(): void
    {
        // var_dump(Config::$route);
        $project_parse_url = parse_url(Config::$domain->project);
        foreach (self::$dom->getElementsByTagName('a') as $tag)
        {
            if (isset($project_parse_url['host']))
            {
                $tag->setAttribute(
                    'href',
                    str_replace(Config::$domain->project, '', $tag->getAttribute('href'))
                );
            }
        }

    }

    /**
     * [changeHtmlTags description]
     * @return [type] [description]
     */
    public static function changeHtmlTags(): void
    {
        foreach (self::$dom->getElementsByTagName('div') as $tag)
        {
            // site-root
            if ($tag->getAttribute('id') == 'WIX_ADS')
            {
                $tag->setAttribute('style', 'display:none');
            }

            if ($tag->getAttribute('id') == 'site-root')
            {
                $tag->setAttribute('style', 'top:0px');
            }
        }
    }

    /**
     * [changeImgTags description]
     * @return [type] [description]
     */
    public static function changeImgTags(): void
    {

    }

    /**
     * [changeScriptTags description]
     * @return [type] [description]
     */
    public static function changeScriptTags(): void
    {

        foreach (self::$dom->getElementsByTagName('script') as $index => $script)
        {
            switch (Config::$config->scripts)
            {
                case 'relative':
                    if ($script->getAttribute('id') === 'sentry')
                    {
                        $script->parentNode->removeChild($script);
                    }

                    $src      = $script->getAttribute('src');
                    $data_url = $script->getAttribute('data-url');

                    if (!empty($src))
                    {
                        // $script->setAttribute('src', Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($src), 'scripts'));
                        $script->setAttribute('src', Router::setRouteUrl($src, 'scripts'));
                    }

                    if (!empty($data_url))
                    {
                        // $script->setAttribute('data-url', Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($data_url), 'scripts'));
                        $script->setAttribute('data-url', Router::setRouteUrl($data_url, 'scripts'));
                    }

                    break;
            }
        }
    }

    public static function changeWixOptions(): void
    {
        $xpath     = new \DOMXPath(self::$dom);
        $nodes     = $xpath->query('//script[@id="wix-viewer-model"]');
        $route_url = str_replace('http://', 'https://', Config::$route->url);
        $base_url  = str_replace('http://', 'https://', Config::$domain->site);

        foreach ($nodes as $key => $node)
        {
            $dec = json_decode($node->nodeValue);
            if (isset($dec->siteFeaturesConfigs->platform->bootstrapData->location->domain))
            {
                $dec->siteFeaturesConfigs->platform->bootstrapData->location->domain = Config::$route->domain;
            }

            if (isset($dec->siteFeaturesConfigs->platform->bootstrapData->location->externalBaseUrl))
            {
                $dec->siteFeaturesConfigs->platform->bootstrapData->location->externalBaseUrl = $base_url;
            }

            if (isset($dec->site->externalBaseUrl))
            {
                $dec->site->externalBaseUrl = $base_url;
            }

            if (isset($dec->siteFeaturesConfigs->tpaCommons->externalBaseUrl))
            {
                $dec->siteFeaturesConfigs->tpaCommons->externalBaseUrl = $base_url;
            }

            if (isset($dec->siteFeaturesConfigs->router->baseUrl))
            {
                $dec->siteFeaturesConfigs->router->baseUrl = $base_url;
            }

            if (isset($dec->siteFeaturesConfigs->seo->context->siteUrl))
            {
                $dec->siteFeaturesConfigs->seo->context->siteUrl = $route_url;
            }

            if (isset($dec->siteFeaturesConfigs->seo->context->defaultUrl))
            {
                $dec->siteFeaturesConfigs->seo->context->defaultUrl = $route_url;
            }

            if (isset($dec->requestUrl))
            {
                $dec->requestUrl = $route_url;
            }

            if (isset($dec->siteFeaturesConfigs->locationWixCodeSdk->baseUrl))
            {
                $dec->siteFeaturesConfigs->locationWixCodeSdk->baseUrl = $base_url;
            }

            if (isset($dec->siteFeaturesConfigs->siteWixCodeSdk->baseUrl))
            {
                $dec->siteFeaturesConfigs->siteWixCodeSdk->baseUrl = $base_url;
            }

            if (isset($dec->siteFeaturesConfigs->tpaCommons->requestUrl))
            {
                $dec->siteFeaturesConfigs->tpaCommons->requestUrl = $route_url;
            }

            if (isset($dec->siteAssets->modulesParams->features->externalBaseUrl))
            {
                $dec->siteAssets->modulesParams->features->externalBaseUrl = $base_url;
            }

            if (isset($dec->siteAssets->modulesParams->platform->externalBaseUrl))
            {
                $dec->siteAssets->modulesParams->platform->externalBaseUrl = $base_url;
            }

            $node->nodeValue = '';
            $node->appendChild(self::$dom->createTextNode(json_encode($dec)));
        }

        $nodes = $xpath->query('//script[@id="wix-fedops"]');

        foreach ($nodes as $key => $node)
        {
            $dec                              = json_decode($node->nodeValue);
            $dec->data->site->externalBaseUrl = $route_url;
            $dec->data->requestUrl            = $route_url;

            $node->nodeValue = '';
            $node->appendChild(self::$dom->createTextNode(json_encode($dec)));
        }
    }

    /**
     * @param  string  $content
     * @return mixed
     */
    public static function css(string $content): string
    {
        return $content;
    }

    /**
     * @param string $html
     */
    public static function html(string $html): string
    {
        self::initialize(
            self::preProcessHTML($html)
        );

        self::initialize($html);
        self::changeDomElements();
        self::changeAHrefLinks();
        self::changeScriptTags();
        self::changeImgTags();
        self::changeHtmlTags();
        self::changeWixOptions();

        return self::postProcessHTML();
    }

    /**
     * @param  string  $content
     * @return mixed
     */
    public static function javascript(string $content): string
    {
        return $content;
    }

    /**
     * @param  object  $content
     * @return mixed
     */
    public static function robots(string $content): string
    {
        $project = str_replace(self::$proto, '', Config::$domain->project);
        $site    = str_replace(self::$proto, '', Config::$domain->site);

        // replace sitemap
        $content = str_replace(
            (string) Config::getProjectName(),
            (string) Config::getSiteName(),
            (string) $content
        );

        $content = str_replace(
            self::$proto[0],
            self::$proto[1],
            $content
        );

        return $content;
    }

    /**
     * { function_description }
     *
     * @param  string $content The content
     * @return string ( description_of_the_return_value )
     */
    public static function sitemap(string $content): string
    {
        return $content;
    }
}
