<?php

namespace Merlin\Fetcher\Fetchers\SpatieCrawler;

use GuzzleHttp\Exception\RequestException;
use Merlin\Reporting\RedirectUtils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObserver;
use Merlin\Fetcher\FetcherBase;

class FetcherSpatieCrawlerObserver  extends CrawlObserver
{
  /** @var \Symfony\Component\Console\Style\SymfonyStyle */
  protected $io;

  /** @var \Merlin\Fetcher\FetcherBase */
  protected $fetcher;


  /**
   * FetcherSpatieCrawlerObserver constructor.
   *
   * @param \Merlin\Fetcher\FetcherBase $fetcher
   */
  public function __construct(FetcherBase $fetcher)
  {
    $this->fetcher = $fetcher;

    // Convenience access to some fetcher objects.
    $this->io     = $fetcher->getIo();

  }//end __construct()


  /**
   * Called when the crawler will crawl the url.
   *
   * @param \Psr\Http\Message\UriInterface   $url
   */
  public function willCrawl(UriInterface $url)
  {

  }//end willCrawl()


  /**
   * Called when the crawler has crawled the given url.
   *
   * @param \Psr\Http\Message\UriInterface      $url
   * @param \Psr\Http\Message\ResponseInterface $response
   * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
   *
   * @throws \Exception
   */
  public function crawled(
    UriInterface $url,
    ResponseInterface $response,
    ?UriInterface $foundOnUrl=null
  ) {
    $urlString = $url->__toString();
    $status = $response->getStatusCode();
    $html = $response->getBody()->__toString();

    $this->io->writeln("Fetched ({$status}): {$urlString}");

    // Get raw headers and redirect info.
    // TODO: Determine if it is possible to pass in the original data into crawled() somehow.
    $redirect = RedirectUtils::checkForRedirect($urlString, $response);

    if (!empty($urlString) && !empty($html)) {
      $this->fetcher->processContent($urlString, $html, $redirect);
    }

    $this->fetcher->incrementCount('fetched');

  }//end crawled()


  /**
   * Called when the crawler had a problem crawling the given url.
   *
   * @param \Psr\Http\Message\UriInterface         $url
   * @param \GuzzleHttp\Exception\RequestException $requestException
   * @param \Psr\Http\Message\UriInterface|null    $foundOnUrl
   */
  public function crawlFailed(
    UriInterface $url,
    RequestException $requestException,
    ?UriInterface $foundOnUrl=null
  ) {

    $urlString = $url->__toString();

    // The exception code is the http status code if the response is set.
    $status = $requestException->getCode();
    $msg = $requestException->getMessage();
    $this->fetcher->processFailed($urlString, $status, $msg);
    $this->fetcher->incrementCount('failed');

  }//end crawlFailed()


  /**
   * Called when the crawl has ended.
   */
  public function finishedCrawling()
  {

  }//end finishedCrawling()


}//end class
