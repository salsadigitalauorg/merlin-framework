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
        $url_string = $url->__toString();
        $return_url = $url_string;

        if (!empty($this->json->getConfig()->get('options')['path_only'])) {
          $query = parse_url($url_string, PHP_URL_QUERY);
          $return_url = !empty($query) ? parse_url($url_string, PHP_URL_PATH) . "?{$query}" : parse_url($url_string, PHP_URL_PATH);
        }

        if (empty($return_url)) {
            $return_url = '/';
        }

        $this->io->writeln($url);

        $groups = isset($this->json->getConfig()->get('options')['group_by']) ? $this->json->getConfig()->get('options')['group_by'] : [];

        foreach ($groups as $config) {
            if (empty($config['type'])) {
                // Invalid group definition.
                continue;
            }

            $class_name = str_replace('_', '', ucwords($config['type'], '_'));
            $class = "\\Migrate\\Crawler\\Group\\".ucfirst($class_name);

            if (!class_exists($class)) {
                // An unknown type.
                continue;
            }

            $type = new $class($config);

            if ($type->match($url_string, $response)) {
                // Only match on the first option.
                return $this->json->mergeRow("crawled-urls-{$type->getId()}", $type->getId(), [$return_url], true);
            }
        }//end foreach

        // Add this to the default group if it doesn't match.
        $this->json->mergeRow('crawled-urls-default', 'default', [$return_url], true);

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
