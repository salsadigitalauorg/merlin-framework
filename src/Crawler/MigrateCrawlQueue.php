<?php

namespace Merlin\Crawler;

use Spatie\Crawler\CrawlUrl;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Exception\InvalidUrl;
use Spatie\Crawler\Exception\UrlNotFoundByIndex;
use Spatie\Crawler\CrawlQueue\CrawlQueue;


class MigrateCrawlQueue implements CrawlQueue
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

  /** @var array */
  protected $config;


  /**
   * MigrateCrawlQueue constructor.
   *
   * @param $config
   */
  public function __construct($config)
  {
    $this->config = $config;

  }//end __construct()


  /**
   * @param \Spatie\Crawler\CrawlUrl $url
   *
   * @return \Spatie\Crawler\CrawlQueue\CrawlQueue
   */
  public function add(CrawlUrl $url): CrawlQueue
  {

    $urlString = (string) $url->url;

    // Standardise URL to consistent base domain.
    if ($this->config['options']['rewrite_domain']) {
      if (!preg_match("#^{$this->config['domain']}#", $urlString)) {
        $query = parse_url($urlString, PHP_URL_QUERY);
        $return_url = !empty($query) ? $url->url->getPath()."?{$query}" : $url->url->getPath();

        $url->url = new \GuzzleHttp\Psr7\Uri(rtrim($this->config['domain'], '/').$return_url);
      }
    }

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
   * @param $crawlUrl
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
