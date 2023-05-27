<?php

namespace app\core;

use app\core\Config;
use app\util\Cache;
use app\util\Encryption;

// use zz\Html\HTMLMinify;
/**
 * Tags
 */
abstract class Tags
{
    /**
     * @var mixed
     */
    public static $dom;

    /**
     * [changeBaseHref description]
     * @return [type] [description]
     */
    public static function changeBaseHref(): void
    {
        foreach (self::$dom->getElementsByTagName('base') as $b)
        {
            $b->setAttribute(
                'href',
                Config::$route->url
            );
        }

    }

    public static function changeDomElements(): void
    {
        self::changeBaseHref();
        self::changeImgTags();
        self::changeLinkTags();
        self::changeMetaTags();
        self::removeComments();
        self::injectMetrics();
    }

    /**
     * [changeImgTags description]
     * @return [type] [description]
     */
    public static function changeImgTags(): void
    {

        /**
         * tag <img>
         */
        foreach (self::$dom->getElementsByTagName('img') as $img)
        {
            switch (Config::$config->images)
            {
                case 'relative':
                    $src = $img->getAttribute('src');
                    if (!empty($src))
                    {
                        $img->setAttribute('src', Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($src), 'images'));
                    }

                    $src = $img->getAttribute('data-original');
                    if (!empty($src))
                    {
                        $img->setAttribute('data-original', Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($src), 'images'));
                    }

                    break;
            }
        }

        /**
         * tag image
         */
        foreach (self::$dom->getElementsByTagName('image') as $img)
        {
            switch (Config::$config->images)
            {
                case 'relative':
                    $src = $img->getAttribute('xlink:href');
                    if (!empty($src))
                    {
                        $img->setAttribute('xlink:href', Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($src), 'images'));
                    }

                    break;
            }
        }

        /**
         * tag div and attribute data-original
         */
        foreach (self::$dom->getElementsByTagName('div') as $div)
        {
            switch (Config::$config->images)
            {
                case 'relative':
                    $data = $div->getAttribute('data-original');
                    if (!empty($data))
                    {
                        $div->setAttribute('data-original', Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($data), 'images'));
                    }

                    $data = $div->getAttribute('data-content-cover-bg');
                    if (!empty($data))
                    {
                        $div->setAttribute('data-content-cover-bg', Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($data), 'images'));
                    }

                    $style = $div->getAttribute('style');
                    if (!empty($style))
                    {
                        $div->setAttribute('style', preg_replace_callback(
                            "/background\-image\:\s?url\(\'(.*)\'\)/",
                            function ($matches)
                            {
                                if (isset($matches[1]))
                                {
                                    return "background-image: url('" . Config::QUERY_PARAM_IMG . self::getRelativePath(self::parseURL($matches[1]), 'images') . "')";
                                }
                            },
                            $style
                        ));
                    }

                    break;
            }
        }
    }

    /**
     * [changeLinkTags description]
     * @return [type] [description]
     */
    public static function changeLinkTags(): void
    {
        $xpath = new \DOMXPath (self::$dom);
        $nodes = $xpath->query('//style');
        foreach ($nodes as $node)
        {
            $attr = $node->getAttribute('data-url');
            if (!empty($attr))
            {
                $node->setAttribute('data-url', Config::QUERY_PARAM_CSS . self::getRelativePath(self::parseURL($attr), 'styles'));
            }

            $attr = $node->getAttribute('data-href');
            if (!empty($attr))
            {
                $node->setAttribute('data-href', Config::QUERY_PARAM_CSS . self::getRelativePath(self::parseURL($attr), 'styles'));
            }

            if (preg_match_all('@url\(\"?//[^/]+[^.]+\.[^.]+?\)@i', $node->nodeValue, $match))
            {
                if (count($match[0]) > 0)
                {
                    $nodeValue = $node->nodeValue;

                    foreach ($match[0] as $str)
                    {
                        $str       = str_replace('url(', '', $str);
                        $str       = str_replace(')', '', $str);
                        $str       = str_replace('"', '', $str);
                        $nodeValue = str_replace($str, Config::QUERY_PARAM_FONT . self::getRelativePath('https:' . $str, 'fonts'), $nodeValue);
                    }

                    $node->nodeValue = '';
                    $node->appendChild(self::$dom->createTextNode($nodeValue));
                }
            }
        }

        foreach (self::$dom->getElementsByTagName('link') as $link)
        {
            $src = $link->getAttribute('href');
            switch (strtolower($link->getAttribute('rel')))
            {
                case 'preload':
                    if (!empty($src))
                    {
                        $link->setAttribute('href', Config::QUERY_PARAM_JS . self::getRelativePath(self::parseURL($src), 'scripts'));
                    }

                    break;

                case 'canonical':
                    $link->setAttribute('href', Config::$domain->site);
                    break;

                case 'icon':
                case 'shortcut icon':
                case 'apple-touch-icon':
                    if (!empty($src))
                    {
                        $link->setAttribute('href', Config::QUERY_PARAM_ICO . self::getRelativePath(self::parseURL($src), 'icons'));
                    }

                    break;

                case 'dns-prefetch':
                    $link->setAttribute('href', Config::$domain->site);
                    break;
                case 'stylesheet':
                    if (Config::$config->styles === 'relative')
                    {
                        if (!empty($src))
                        {
                            $link->setAttribute('href', Config::QUERY_PARAM_CSS . self::getRelativePath(self::parseURL($src), 'styles'));
                        }
                    }
                    break;
            }
        }
    }

    /**
     * [changeMetaTags description]
     * @return [type] [description]
     */
    public static function changeMetaTags(): void
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

            switch (strtolower($meta->getAttribute('name')))
            {
                case 'robots':
                    $meta->parentNode->removeChild($meta);
                    break;
                case 'generator':
                    $meta->parentNode->removeChild($meta);
                    break;
            }

            switch (strtolower($meta->getAttribute('property')))
            {
                case 'og:url':
                    $meta->setAttribute('content', Config::$route->url);
                    break;

                case 'og:image':
                    $content = $meta->getAttribute('content');
                    if (!empty($content))
                    {
                        $meta->setAttribute('content', Config::QUERY_PARAM_IMG . self::getRelativePath($content, 'images'));
                    }

                    break;
            }
        }
    }

    /**
     * [getElementsByClass description]
     * @param  [type] &$parentNode    [description]
     * @param  [type] $tagName        [description]
     * @param  [type] $className      [description]
     * @return [type] [description]
     */
    public static function getElementsByClass(
               $parentNode,
        string $tagName,
        string $className
    )
    {
        $nodes = [];

        $childNodeList = $parentNode->getElementsByTagName($tagName);
        for ($i = 0; $i < $childNodeList->length; $i++)
        {
            $temp = $childNodeList->item($i);
            if (stripos($temp->getAttribute('class'), $className) !== false)
            {
                $nodes[] = $temp;
            }
        }

        return $nodes;
    }

    /**
     * [getRelativePath description]
     * @param  [type] $content        [description]
     * @param  [type] $type           [description]
     * @return [type] [description]
     */
    public static function getRelativePath(
        string $content,
        string $type
    ): string
    {
        if (Config::$config->{$type} === 'relative')
        {
            $content = Encryption::encode($content);
        }

        return $content;
    }

    /**
     * @param string $html
     */
    public static function initialize(string $html): void
    {
        $dom_html5 = new \Masterminds\HTML5 (['disable_html_ns' => true]);
        self::$dom = $dom_html5->loadHTML($html);
    }

    /**
     * [injectMetrics description]
     * @return [type] [description]
     */
    public static function injectMetrics()
    {
        if (Config::$metrics->enabled)
        {
            if (isset(Config::$metrics->ya))
            {
                foreach (self::$dom->getElementsByTagName('head') as $node)
                {
                    if (file_exists(Config::$metrics->path . '/ya.js'))
                    {
                        $node->appendChild(
                            self::$dom->createElement('script',
                                str_replace(
                                    '{{YANDEX_METRIKA}}',
                                    Config::$metrics->ya,
                                    file_get_contents(Config::$metrics->path . '/ya.js')
                                )
                            )
                        );
                    }
                }
            }

            if (isset(Config::$metrics->ga))
            {
                foreach (self::$dom->getElementsByTagName('head') as $node)
                {
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
    }

    /**
     * [parseURL description]
     * @param  [type] $src            [description]
     * @return [type] [description]
     */
    public static function parseURL($src): string
    {
        $url = parse_url($src);

        return isset($url['host']) ? $src : Config::$domain->project . $src;
    }

    public static function postProcessHTML(): string
    {
        return self::compressHTML(
            self::render()
        );
    }

    /**
     * @param string $html
     */
    public static function preProcessHTML(string $html): string
    {
        return self::injectHTML(
            Cache::injectWebCleaner(
                Cache::injectWebStats($html)
            )
        );
    }

    /**
     * [removeComments description]
     * @return [type] [description]
     */
    public static function removeComments(): void
    {
        $xpath = new \DOMXPath (self::$dom);

        while ($node = $xpath->query('//comment()')->item(0))
        {
            $node->parentNode->removeChild($node);
        }

    }

    public static function render(): string
    {
        return self::$dom->saveHTML();
    }

    /**
     * [compressHTML description]
     * @param  [type] $html           [description]
     * @return [type] [description]
     */
    private static function compressHTML($html): string
    {
        if (Config::$compress->enabled)
        {
            $html = preg_replace([
                '/\>[^\S ]+/s',
                '/[^\S ]+\</s',
                '/(\s)+/s',
                '/<!--(.|\s)*?-->/',
                '/\n+/',
            ], [
                '>',
                '<',
                '\\1',
                '',
                ' ',
            ], $html);
        }

        return $html;
    }

    /**
     * [injectHTML description]
     * @param  [type] $html           [description]
     * @return [type] [description]
     */
    private static function injectHTML(string $html): string
    {
        $path_header = Config::$inject->path . '/' . Config::getSiteName() . '-header.html';
        $path_footer = Config::$inject->path . '/' . Config::getSiteName() . '-footer.html';

        if (Config::$inject->enabled)
        {
            if (Config::$inject->header)
            {
                if (file_exists($path_header))
                {
                    return str_replace('</head>', file_get_contents($path_header) . '</head>', $html);
                }
            }

            if (Config::$inject->footer)
            {
                if (file_exists($path_footer))
                {
                    return str_replace('</body>', file_get_contents($path_footer) . '</body>', $html);
                }
            }
        }

        return $html;
    }
}
