<?php

namespace Migrate\Fetcher\Fetchers\SpatieCrawler;

use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\CrawlQueue\CrawlQueue;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Exception\InvalidUrl;
use Spatie\Crawler\Exception\UrlNotFoundByIndex;

class FetcherSpatieCrawlerQueue implements CrawlQueue
{

  /**
   * All known URLs, indexed by URL string.
   *
   * @var CrawlUrl[]
   */
  protected $urls = [];

  /**
   * Pending URLs, indexed by URL string.
   *
   * @var CrawlUrl[]
   */
  protected $pendingUrls = [];


  /**
   * @param \Spatie\Crawler\CrawlUrl $url
   *
   * @return \Migrate\Fetcher\Fetchers\SpatieCrawler\CrawlQueue
   */
  public function add(CrawlUrl $url): CrawlQueue
  {
    $urlString = (string) $url->url;
    if (!isset($this->urls[$urlString])) {
      $url->setId($urlString);
      $this->urls[$urlString] = $url;
      $this->pendingUrls[$urlString] = $url;
    }

    return $this;

  }//end add()


  /**
   * @return bool
   *
   */
  public function hasPendingUrls(): bool
  {
    return (bool) $this->pendingUrls;

  }//end hasPendingUrls()


  /**
   * @param $id
   *
   * @return \Spatie\Crawler\CrawlUrl
   */
  public function getUrlById($id): CrawlUrl
  {
    if (!isset($this->urls[$id])) {
      throw new UrlNotFoundByIndex("Crawl url {$id} not found in collection.");
    }

    return $this->urls[$id];

  }//end getUrlById()


  /**
   * @param \Spatie\Crawler\CrawlUrl $url
   *
   * @return bool
   */
  public function hasAlreadyBeenProcessed(CrawlUrl $url): bool
  {
    $url = (string) $url->url;
    if (isset($this->pendingUrls[$url])) {
      return false;
    }

    if (isset($this->urls[$url])) {
      return true;
    }

    return false;

  }//end hasAlreadyBeenProcessed()


  /**
   * @param \Spatie\Crawler\CrawlUrl $crawlUrl
   */
  public function markAsProcessed(CrawlUrl $crawlUrl)
  {
    $url = (string) $crawlUrl->url;
    unset($this->pendingUrls[$url]);

  }//end markAsProcessed()


  /**
   * @param CrawlUrl|UriInterface $crawlUrl
   *
   * @return bool
   * @throws \Spatie\Crawler\Exception\InvalidUrl
   */
  public function has($crawlUrl): bool
  {
    if ($crawlUrl instanceof CrawlUrl) {
      $url = (string) $crawlUrl->url;
    } else if ($crawlUrl instanceof UriInterface) {
      $url = (string) $crawlUrl;
    } else {
      throw InvalidUrl::unexpectedType($crawlUrl);
    }

    return isset($this->urls[$url]);

  }//end has()


  /**
   * @return \Spatie\Crawler\CrawlUrl|null
   */
  public function getFirstPendingUrl(): ?CrawlUrl
  {
    foreach ($this->pendingUrls as $pendingUrl) {
      return $pendingUrl;
    }

    return null;

  }//end getFirstPendingUrl()


}//end class



{

}
