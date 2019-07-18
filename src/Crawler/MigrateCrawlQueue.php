<?php

namespace Migrate\Crawler;

use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\Exception\UrlNotFoundByIndex;
use Spatie\Crawler\CrawlQueue\CrawlQueue;

class MigrateCrawlQueue implements CrawlQueue
{
    /** @var \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection */
    protected $urls;

    /** @var \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection */
    protected $pendingUrls;

    /** @var Array */
    protected $config;

    public function __construct($config)
    {
        $this->urls = collect();
        $this->config = $config; 
        $this->pendingUrls = collect();
    }

    public function getUrls()
    {
        return $this->urls;
    }

    public function getPendingUrls()
    {
        return $this->pendingUrls;
    }

    public function add(CrawlUrl $url): CrawlQueue
    {

        // Standardise URL to consistent base domain.
        if ($this->config['options']['rewrite_domain']) {
          if (!preg_match("#^{$this->config['domain']}#", $url->url->__toString())) {
            $url->url = new \GuzzleHttp\Psr7\Uri(rtrim($this->config['domain'], '/') . $url->url->getPath());
          }
        }

        if ($this->has($url)) {
            return $this;
        }

        $this->urls->push($url);

        $url->setId($this->urls->keys()->last());
        $this->pendingUrls->push($url);

        return $this;
    }

    public function hasPendingUrls(): bool
    {
        return (bool) $this->pendingUrls->count();
    }

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
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url): bool
    {
        return ! $this->contains($this->pendingUrls, $url) && $this->contains($this->urls, $url);
    }

    public function markAsProcessed(CrawlUrl $crawlUrl)
    {
        $this->pendingUrls = $this->pendingUrls
            ->reject(function (CrawlUrl $crawlUrlItem) use ($crawlUrl) {
                return (string) $crawlUrlItem->url === (string) $crawlUrl->url;
            });
    }

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
    }

    /** @return \Spatie\Crawler\CrawlUrl|null */
    public function getFirstPendingUrl()
    {
        return $this->pendingUrls->first();
    }

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
    }
}
