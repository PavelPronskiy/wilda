<?php

namespace app\module;

use app\core\Config;
use app\core\Tags;
use app\core\Router;

/**
 * Tilda Controller
 */
class Tilda extends Tags
{
    public function __construct()
    {
        // var_dump('123');
    }

    /**
     * [changeAHrefLinks description]
     * @return [type] [description]
     */
    public static function changeAHrefLinks(): void
    {
        foreach (self::$dom->getElementsByTagName('a') as $tag)
        {
            if (str_contains($tag->getAttribute('href'), Config::getProjectName()))
            {
                $proto_url = Config::forceProto($tag->getAttribute('href'));
                $parse_url = parse_url($proto_url);
                $tag->setAttribute(
                    'href',
                    str_replace($parse_url['host'], Config::getSiteName(), $proto_url)
                );
            }
            elseif ($tag->getAttribute('href') == Config::$domain->project . Config::$route->path)
            {
                $tag->setAttribute(
                    'href',
                    Config::$route->url
                );
            }
        }
    }

    /**
     * { function_description }
     */
    public static function changeFavicon(): void
    {
        if (Config::$favicon->enabled)
        {
            foreach (self::$dom->getElementsByTagName('link') as $elem)
            {
                if (stripos($elem->getAttribute('rel'), 'shortcut icon') === 0)
                {
                    if (file_exists(Config::$favicon->path . '/' . Config::getSiteName() . '.ico'))
                    {
                        $elem->setAttribute('href', Config::$domain->site . '/' . Config::$favicon->path . '/' . Config::getSiteName() . '.ico');
                    }
                    else
                    {
                        $elem->setAttribute('href', Config::$domain->site . '/' . Config::$favicon->path . '/default.ico');
                    }
                }
            }
        }
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
        {
            switch (Config::$config->images)
            {
                case 'relative':
                    // if (str_contains('static.tildacdn', $style->textContent))
                    preg_match('/static\.tildacdn\.(info|com)/', $style->textContent, $matched);
                    if (count($matched) > 0)
                    {
                        $style->textContent = preg_replace_callback(
                            "/background\-image\:\s?url\(\'(.*)\'\)/",
                            function ($matches)
                            {
                                if (isset($matches[1]))
                                {
                                    // return "background-image: url('" . Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($matches[1]), 'images') . "')";
                                    return "background-image: url('" . Router::setRouteUrl($matches[1], 'images') . "')";
                                }
                            },
                            $style->textContent
                        );
                    }
                    break;
            }
        }
    }

    /**
     * [changeScriptTags description]
     * @return [type] [description]
     */
    public static function changeScriptTags(): void
    {
        $script_tags = self::$dom->getElementsByTagName('script');
        foreach ($script_tags as $index => $script)
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

                    /**
                     * tag <script>
                     */
                    // if (str_contains('static.tildacdn', $script->textContent))
                    preg_match('/static\.tildacdn\.(info|com)/', $script->textContent, $matched);
                    if (count($matched) > 0)
                    {
                        $script->textContent = preg_replace_callback(
                            "/s\.src = \'(.*)\'/",
                            function ($matches)
                            {
                                if (isset($matches[1]))
                                {
                                    // return "s.src = '" . Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($matches[1]), 'scripts') . "'";
                                    return "s.src = '" . Router::setRouteUrl($matches[1], 'scripts') . "'";
                                }

                            },
                            $script->textContent
                        );
                    }
                    break;
            }

            // remove tilda tracker
            // if (str_contains('window.mainTracker', $script->textContent))
            preg_match('/window\.mainTracker.*/', $script->textContent, $matched);
            if (count($matched) > 0)
            {
                $script->parentNode->removeChild($script);
            }
        }
    }

    /**
     * [changeSubmitSuccessMessage description]
     * @return [type] [description]
     */
    public static function changeSubmitSuccessMessage()
    {
        if (Config::$mail->enabled)
        {
            foreach (self::getElementsByClass(self::$dom, 'div', 'js-successbox') as $elem)
            {
                $elem->setAttribute('data-success-message', Config::$mail->success);
            }
        }
    }

    /**
     * @param  string  $content
     * @return mixed
     */
    public static function css(string $content): string
    {
        // if (str_contains('static.tildacdn', $content))
        preg_match('/static\.tildacdn\.(com|info)/', $content, $matched);
        if (count($matched) > 0)
        {
            $content = preg_replace_callback(
                "/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i",
                function ($matches)
                {
                    if (isset($matches[3]))
                    {
                        // return "url('" . Config::QUERY_PARAM_FONT . self::getRelativePath(self::parseURL($matches[3]), 'fonts') . "')";
                        return "url('" . Router::setRouteUrl($matches[3], 'fonts') . "')";
                    }
                },
                $content
            );
        }

        return $content;
    }


    /**
     * Change HTML page elements
     */
    public static function changeHTMLElements(): void
    {
        // var_dump(Config::$mail);
        $xpath = new \DOMXPath(self::$dom);
        foreach ($xpath->query("//div[@data-tilda-root-zone]") as $item)
        {
            for ( $k=0; $k < $item->attributes->length; $k++)
            {
                if ($item->attributes->item($k)->nodeName === 'data-tilda-root-zone')
                {
                    $item->removeAttributeNode(
                        $item->attributes->item($k)
                    );
                }
            }
        }

        foreach ($xpath->query("//meta") as $item)
        {
            $itemprop = $item->getAttribute('itemprop');
            $content = $item->getAttribute('content');

            if ($itemprop === 'image')
            {
                // if (str_contains('static.tildacdn', $content))
                preg_match('/static\.tildacdn\.(com|info)/', $content, $matched);
                if (count($matched) > 0)
                {
                    // $content = Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($content), 'images');
                    // $item->setAttribute('content', $content);
                    $item->setAttribute('content', Router::setRouteUrl($content, 'images'));
                }
            }
        }


        foreach ($xpath->query("//div[@data-field-imgs-value]") as $item)
        {
            $json_str = $item->getAttribute('data-field-imgs-value');
            // if (str_contains('static.tildacdn', $json_str))
            preg_match('/static\.tildacdn\.(com|info)/', $json_str, $matched);
            if (count($matched) > 0)
            {
                $json_dec = json_decode($json_str, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    foreach($json_dec as $key => $dec)
                    {
                        if (isset($dec['li_img']))
                        {
                            // $json_dec[$key]['li_img'] = Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($dec['li_img']), 'images');
                            $json_dec[$key]['li_img'] = Router::setRouteUrl($dec['li_img'], 'images');

                        }
                    }
                }

                $item->setAttribute('data-field-imgs-value', json_encode($json_dec));
            }
        }

        foreach ($xpath->query("//div[@data-img-zoom-url]") as $item)
        {
            $data_img_zoom_url = $item->getAttribute('data-img-zoom-url');
            // $item->setAttribute('data-img-zoom-url', Config::QUERY_PARAM_IMG . self::getRelativePath($data_img_zoom_url, 'images'));
            $item->setAttribute('data-img-zoom-url', Router::setRouteUrl($data_img_zoom_url, 'images'));

            $data_img_style_background = $item->getAttribute('style');

            // if (str_contains('static.tildacdn', $data_img_style_background))
            preg_match('/static\.tildacdn\.(com|info)/', $data_img_style_background, $matched);
            if (count($matched) > 0)
            {
                $data_img_style_background = preg_replace_callback(
                    "/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i",
                    function ($matches)
                    {
                        if (isset($matches[3]))
                        {
                            // return "url('" . Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($matches[3]), 'images') . "')";
                            return "url('" . Router::setRouteUrl($matches[3], 'images') . "')";
                        }
                    },
                    $data_img_style_background
                );

                $item->setAttribute('style', $data_img_style_background);
            }
        }
    }

    /**
     * @param string $html
     */
    public static function html(string $html): string
    {
        self::initialize(
            self::preProcessHTML($html)
        );

        parent::changeDomElements();
        self::changeHTMLElements();
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
     * @param  $body
     * @return mixed
     */
    public static function javascript($body)
    {
        if (Config::$mail->enabled)
        {
            $body = str_replace([
                'forms.tildacdn.com',
                'forms2.tildacdn.com'
            ], Config::getSiteName(), $body);
        }

        return $body;
    }

    /**
     * [removeCounters description]
     * @return [type] [description]
     */
    public static function removeCounters(): void
    {
        foreach (self::$dom->getElementsByTagName('script') as $script)
        {
            if (strlen($script->textContent) == 564)
            {
                $script->parentNode->removeChild($script);
            }
        }
    }

    /**
     * [removeTildaCopy description]
     * @return [type] [description]
     */
    public static function removeTildaCopy(): void
    {
        $tildacopy = self::$dom->getElementById('tildacopy');
        if ($tildacopy)
        {
            $tildacopy->parentNode->removeChild($tildacopy);
        }
    }

    /**
     * { function_description }
     *
     * @param  string $content The content
     * @return string ( description_of_the_return_value )
     */
    public static function robots(string $content): string
    {
        $project = str_replace(self::$proto, '', Config::$domain->project);
        $site    = str_replace(self::$proto, '', Config::$domain->site);

        // change host
        if (preg_match('/project/', $project))
        {
            $content = preg_replace(
                '/Host:.*/',
                'Host: ' . Config::$route->domain,
                $content
            );
        }
        else
        {
            $content = str_replace(
                $project,
                $site,
                $content
            );
        }

        // remove disallow directives
        $content = str_replace(
            'Disallow: /',
            '',
            $content
        );

        // replace sitemap
        $content = str_replace(
            (string) Config::getProjectName(),
            (string) Config::getSiteName(),
            $content
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
        // var_dump($content);
        return str_replace(
            self::$proto[0],
            self::$proto[1],
            str_replace(
                (string) Config::getProjectName(),
                (string) Config::getSiteName(),
                $content
            )
        );
    }
}
