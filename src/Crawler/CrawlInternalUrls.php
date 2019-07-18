<?php

namespace Migrate\Crawler;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfile;

class CrawlInternalUrls extends CrawlProfile
{
    protected $baseUrl;
    protected $skipExtensions = [];
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
        ];
    }

    public function shouldCrawl(UriInterface $url): bool
    {

        $ext = strtolower(pathinfo($url->__toString(), PATHINFO_EXTENSION));
        if (in_array($ext, $this->skipExtensions)) {
          return FALSE;
        }

        foreach ($this->config['options']['exclude'] as $exclude) {
          if (preg_match($exclude, $url->__toString())) {
            return FALSE;
          }
        }

        // Internal paths only.
        return $this->baseUrl->getHost() === $url->getHost();
    }
}
