<?php

/**
 * Fetcher based on the Spatie Crawler.
 */

namespace Migrate\Fetcher\Fetchers\SpatieCrawler;

use GuzzleHttp\RequestOptions;
use Migrate\Fetcher\FetcherDefaults;
use Spatie\Browsershot\Browsershot;
use Spatie\Crawler\Crawler as SpatieCrawler;
use Spatie\Crawler\CrawlUrl;
use Migrate\Fetcher\FetcherBase;
use Migrate\Fetcher\FetcherInterface;

/**
 * Class FetcherSpatieCrawler
 * @package Migrate\Fetcher\Fetchers\SpatieCrawler
 */
class FetcherSpatieCrawler extends FetcherBase implements FetcherInterface
{

  /** @var \Spatie\Crawler\Crawler */
  private $crawler;

  /** @var \Migrate\Fetcher\Fetchers\SpatieCrawler\FetcherSpatieCrawlerQueue */
  private $queue;

  /** @var \Migrate\Fetcher\Fetchers\SpatieCrawler\FetcherSpatieCrawlerObserver */
  private $observer;


  /** @inheritDoc */
  public function init() {

    // Options from fetch_options.
    $concurrency    = ($this->config->get('fetch_options')['concurrency'] ?? FetcherDefaults::CONCURRENCY);
    $requestDelay   = ($this->config->get('fetch_options')['delay'] ?? FetcherDefaults::DELAY);
    $executeJs      = ($this->config->get('fetch_options')['execute_js'] ?? FetcherDefaults::EXECUTE_JS);
    $allowRedirects = ($this->config->get('fetch_options')['allow_redirects'] ?? FetcherDefaults::ALLOW_REDIRECTS);
    $ignoreSSL      = ($this->config->get('fetch_options')['ignore_ssl_errors'] ?? FetcherDefaults::IGNORE_SSL_ERRORS);

    $timeouts       = ($this->config->get('fetch_options')['timeouts'] ?? []);
    $connectTimeout = ($timeouts['connect_timeout'] ?? FetcherDefaults::TIMEOUT_CONNECT);
    $readTimeout    = ($timeouts['read_timeout'] ?? FetcherDefaults::TIMEOUT_READ);
    $timeout        = ($timeouts['timeout'] ?? FetcherDefaults::TIMEOUT);

    $clientOptions = [
        RequestOptions::COOKIES         => true,
        RequestOptions::CONNECT_TIMEOUT => $connectTimeout,
        RequestOptions::READ_TIMEOUT    => $readTimeout,
        RequestOptions::TIMEOUT         => $timeout,
        RequestOptions::ALLOW_REDIRECTS => $allowRedirects,
        RequestOptions::HEADERS         => ['User-Agent' => FetcherDefaults::USER_AGENT],
        RequestOptions::VERIFY          => !$ignoreSSL,
    ];

    $crawler = SpatieCrawler::create($clientOptions);
    $queue    = new FetcherSpatieCrawlerQueue();
    $observer = new FetcherSpatieCrawlerObserver($this);

    $crawler->setCrawlQueue($queue);
    $crawler->setCrawlObserver($observer);
    $crawler->setMaximumDepth(0);
    $crawler->setConcurrency($concurrency);
    $crawler->setDelayBetweenRequests($requestDelay);
    $crawler->ignoreRobots();

    if ($executeJs) {
      $browserShot = new Browsershot();
      $crawler->setBrowsershot($browserShot);
      $crawler->executeJavaScript();
      $browserShot->addChromiumArguments(['disk-cache-dir' => '/tmp/merlin_browser_cache']);

      if ($ignoreSSL) {
        $browserShot->setOption('ignoreHttpsErrors', true);
      }
    }

    $this->crawler = $crawler;
    $this->queue = $queue;
    $this->observer = $observer;

  }//end init()


  /** @inheritDoc */
  public function addUrl(?string $url) {
    $uri = new \GuzzleHttp\Psr7\Uri($url);
    $this->queue->add(CrawlUrl::create($uri));

  }//end addUrl()


  /** @inheritDoc */
  public function start() {
    if ($this->queue->hasPendingUrls()) {
      $this->crawler->startCrawling($this->queue->getUrlById(0)->url->__toString());
    }

  }//end start()


}//end class
