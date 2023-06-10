<?php

namespace app\util;

// namespace Utils;
use app\core\Config;

class Booster
{
    public function __construct()
    {
        self::run();
    }

    /**
     * { function_description }
     *
     * @param array $links The links
     */
    public static function boostPages(
        array $links,
        array $results = []
    ): array
    {
        foreach ($links as $link)
        {
            foreach (Curl::$devices as $device)
            {
                $microtime = microtime(true);
                $res       = Curl::get($link, $device);
                $time      = (microtime(true) - $microtime);
                $time      = number_format($time, 2, ',', '.');
                if (isset($res->status) && $res->status === 200)
                {
                    self::view($link . ', device: ' . $device . ', status: ' . $res->status . ', runtime: ' . $time);
                    $bool = true;
                }
                else
                {
                    $bool = false;
                    self::view($link . ', device: ' . $device . ', runtime: ' . $time);
                }

                $results[] = [
                    'link'   => $link,
                    'device' => $device,
                    'status' => $bool,
                    'time'   => $time,
                ];
            }
        }

        return $results;
    }

    /**
     * @param $microtime
     */
    public static function mtime($microtime)
    {
        return number_format((microtime(true) - $microtime), 2, ',', '.');
    }

    /**
     * { function_description }
     *
     * @param <type> $host The host
     */
    public static function robotsParser($host): void
    {
        $hosts = [];

        foreach ($host->site as $site)
        {
            $links     = [];
            $robotsUrl = $site . '/robots.txt';

            // get all urls from host site
            if ($robotsData = Curl::get($robotsUrl, 'Crawler'))
            {
                $links = [$site];
                if (preg_match('/^Sitemap:\s(.*)/m', $robotsData->body, $matches))
                {
                    if (isset($matches[1]))
                    {
                        $sitemapUrl = $matches[1];
                        $sitemaps   = Curl::get($sitemapUrl, 'Crawler');
                        if (isset($sitemaps->body))
                        {
                            $doc = new \DOMDocument();
                            $doc->loadXML($sitemaps->body);
                            foreach ($doc->getElementsByTagName('loc') as $key => $node)
                            {
                                $links[] = $node->textContent;
                            }
                        }
                    }
                }
            }
            else
            {
                self::view($site, false);
            }

            // boost all pages from host site

            if (count($links) > 0)
            {
                $hosts[$site] = self::boostPages($links);
            }
        }
    }

    /**
     * { function_description }
     */
    public static function run(): void
    {
        $time = microtime(true);

        Config::$config->curl->timeout    = 1;
        Config::$config->privoxy->enabled = false;

        foreach (Config::$config->hosts as $host)
        {
            $hosts[] = self::robotsParser($host);
        }

        $mtime = self::mtime($time);
        self::view('Boosted sites: ' . count($hosts) . ', runtime: ' . $mtime);
    }

    /**
     * @param $msg
     * @param $bool
     */
    public static function view(
        $msg,
        $bool = true
    ): void
    {
        $status = $bool ? '[OK]' : '[ERROR]';
        echo $status . ' ' . $msg . PHP_EOL;
    }
}
