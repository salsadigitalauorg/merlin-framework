<?php

namespace Migrate\Crawler;

use Spatie\Crawler\CrawlObserver;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class MigrateCrawlObserver extends CrawlObserver
{
    /** @var SymfonyStyle */
    protected $io;

    /** @var Migrate\Output\Json */
    protected $json;

    /** @var integer */
    protected $count = 0;


    public function __construct($io, $json)
    {
        $this->io = $io;
        $this->json = $json;

    }//end __construct()


    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface   $url
     */
    public function willCrawl(UriInterface $url)
    {
        $this->count++;

    }//end willCrawl()


    /**
     * Called when the crawler has crawled the given url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl=null
    ) {
        $this->io->writeln($url);
        $this->json->mergeRow('crawled-urls', 'urls', [$url->__toString()], true);

    }//end crawled()


    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl=null
    ) {
        $this->json->mergeRow('crawl-error', $url->__toString(), [$requestException->getMessage()], true);
        $this->io->error("Error: ${url} -- Found on url: ${foundOnUrl}");

    }//end crawlFailed()


    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling()
    {
      $this->io->success("Done!");
      $this->io->success($this->count." total URLs.");

    }//end finishedCrawling()


}//end class
