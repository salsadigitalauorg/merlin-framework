<?php

namespace Migrate\Crawler;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfile;

class CrawlInternalUrls extends CrawlProfile
{
    /**
     * The base URL of the crawler.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Extensions to skip as part of the URL list.
     *
     * @var array
     */
    protected $skipExtensions = [];

    /**
     * The crawler configuration array.
     *
     * @var array
     */
    protected $config;


    public function __construct($config)
    {

        $this->config = $config;
        $baseUrl = $this->config['domain'];

        if (! $baseUrl instanceof UriInterface) {
            $baseUrl = new Uri($baseUrl);
        }

        $this->baseUrl = $baseUrl;

        $this->skipExtensions = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'svg',
            'doc',
            'docx',
            'ppt',
            'pptx',
            'xls',
            'xlsx',
            'pdf',
            'mov',
            'mp4',
            'mpg',
            'mpeg',
            'zip',
            'gz',
            'rtf',
            'xml',
            'tiff',
            'eps',
            'docm',
            'dot',
            'pub',
            'indd',
            'ppsx',
        ];

    }//end __construct()


    public function shouldCrawl(UriInterface $url): bool
    {

        $ext = strtolower(pathinfo($url->__toString(), PATHINFO_EXTENSION));
        if (in_array($ext, $this->skipExtensions)) {
          return FALSE;
        }

        foreach ($this->config['options']['crawler_exclude'] as $exclude) {
          if (preg_match($exclude, $url->__toString())) {
            return FALSE;
          }
        }

        foreach ($this->config['options']['crawler_include'] as $include) {
          if (!preg_match($include, $url->__toString())) {
            return FALSE;
          }
        }

        // Internal paths only.
        return $this->baseUrl->getHost() === $url->getHost();

    }//end shouldCrawl()


}//end class
