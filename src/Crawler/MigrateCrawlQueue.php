<?php

namespace Migrate\Crawler;

use Migrate\Fetcher\Cache;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Exception\UrlNotFoundByIndex;
use Spatie\Crawler\CrawlQueue\CrawlQueue;

class MigrateCrawlQueue implements CrawlQueue
{
    /** @var \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection */
    protected $urls;

    /** @var \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection */
    protected $pendingUrls;

    /** @var array */
    protected $config;

    /** @var \Migrate\Fetcher\Cache */
    protected $cache;


    public function __construct($config)
    {
        $this->urls = collect();
        $this->config = $config;
        $this->pendingUrls = collect();

        $this->cache = new Cache($config['domain']);

    }//end __construct()


    public function getUrls()
    {
        return $this->urls;

    }//end getUrls()


    public function getPendingUrls()
    {
        return $this->pendingUrls;

    }//end getPendingUrls()


    public function add(CrawlUrl $url): CrawlQueue
    {

        // Standardise URL to consistent base domain.
        if ($this->config['options']['rewrite_domain']) {
          if (!preg_match("#^{$this->config['domain']}#", $url->url->__toString())) {
            $query = parse_url($url->url->__toString(), PHP_URL_QUERY);
            $return_url = !empty($query) ? $url->url->getPath()."?{$query}" : $url->url->getPath();

            $url->url = new \GuzzleHttp\Psr7\Uri(rtrim($this->config['domain'], '/').$return_url);
          }
        }

        if ($this->has($url)) {
            return $this;
        }

        $this->urls->push($url);

        $url->setId($this->urls->keys()->last());
        $this->pendingUrls->push($url);

        return $this;

    }//end add()


    public function hasPendingUrls(): bool
    {
        return (bool) $this->pendingUrls->count();

    }//end hasPendingUrls()


    /**
     * @param mixed $id
     *
     * @return \Spatie\Crawler\CrawlUrl|null
     */
    public function getUrlById($id): CrawlUrl
    {
        if (! isset($this->urls->values()[$id])) {
            throw new UrlNotFoundByIndex("#{$id} crawl url not found in collection");
        }

        return $this->urls->values()[$id];

    }//end getUrlById()


    public function hasAlreadyBeenProcessed(CrawlUrl $url): bool
    {
        return ! $this->contains($this->pendingUrls, $url) && $this->contains($this->urls, $url);

    }//end hasAlreadyBeenProcessed()


    public function markAsProcessed(CrawlUrl $crawlUrl)
    {
        $this->pendingUrls = $this->pendingUrls
            ->reject(
                function (CrawlUrl $crawlUrlItem) use ($crawlUrl) {
                return (string) $crawlUrlItem->url === (string) $crawlUrl->url;
                }
            );

    }//end markAsProcessed()


    /**
     * @param CrawlUrl|\Psr\Http\Message\UriInterface $crawlUrl
     *
     * @return bool
     */
    public function has($crawlUrl): bool
    {
        if (! $crawlUrl instanceof CrawlUrl) {
            $crawlUrl = CrawlUrl::create($crawlUrl);
        }

        if ($this->contains($this->urls, $crawlUrl)) {
            return true;
        }

        return false;

    }//end has()


    /** @return \Spatie\Crawler\CrawlUrl|null */
    public function getFirstPendingUrl()
    {
        return $this->pendingUrls->first();

    }//end getFirstPendingUrl()


    /**
     * @param \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection $collection
     * @param \Spatie\Crawler\CrawlUrl                                             $searchCrawlUrl
     *
     * @return bool
     */
    protected function contains($collection, CrawlUrl $searchCrawlUrl): bool
    {
        foreach ($collection as $crawlUrl) {
            if ((string) $crawlUrl->url === (string) $searchCrawlUrl->url) {
                return true;
            }
        }

        return false;

    }//end contains()


}//end class
