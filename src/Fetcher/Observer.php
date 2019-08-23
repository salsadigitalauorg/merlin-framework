<?php

namespace Migrate\Fetcher;

use GuzzleHttp\Exception\RequestException;
use Migrate\Command\GenerateCommand;
use Migrate\Exception\ElementNotFoundException;
use Migrate\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObserver;
use Symfony\Component\DomCrawler\Crawler;

class Observer  extends CrawlObserver
{
  /** @var \Symfony\Component\Console\Style\SymfonyStyle */
  protected $io;

  /** @var \Migrate\Output\OutputBase */
  protected $json;

  /** @var \Migrate\Parser\ParserInterface */
  protected $config;


// TODO: MOVE CONTENT HASH INTO HERE?
  /** @var \Migrate\Output\ContentHash */
  protected $hashes;



  /** @var integer */
  protected $countSuccess = 0;

  /** @var integer */
  protected $countFailed = 0;


  public function __construct($io, $json, $config)
  {
    $this->io = $io;
    $this->json = $json;
    $this->config = $config;

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

    $this->processHtml($urlString, $html, $this->config, $this->io, $this->json);


    $this->countSuccess++;

  }//end crawled()


  // TODO: REFACTOR WHERE THIS LIVES, PROB TURN INTO NEW CLASS THAT YOU HAVE TO PASS INTO STUFF.

  public static function processHtml($url, $html, $config, $io, $output) {
    $row = new \stdClass;

//    $io = $this->io;
//    $output = $this->json;
//    $parser = $this->config;
    $parser = $config;

    $io->write('Parsing: '.$url);

    $duplicate = false;
//    if ($hashes instanceof ContentHash) {
//      $duplicate = $hashes->put($request);
//    }

    if ($duplicate === false) {

      // Add to cache if we are doing that.
      $useCache = ($config->get('url_options')['cache_enabled'] ?? false);
      if ($useCache) {
        $cache = new Cache($config->get('domain'));
        $cache->put($url, $html);
      }

      while ($field = $parser->getMapping()) {
        $crawler = new Crawler($html, $url);
        $type = GenerateCommand::TypeFactory($field['type'], $crawler, $output, $row, $field);
        try {
          $type->process();
        } catch (ElementNotFoundException $e) {
          $output->mergeRow($e::FILE, $url, [$e->getMessage()], true);
        } catch (ValidationException $e) {
          $output->mergeRow($e::FILE, $url, [$e->getMessage()], true);
        } catch (\Exception $e) {
          $output->mergeRow('error-unhandled', $url, [$e->getMessage()], true);
        }
      }//end while
    }

    // Reset the parser so we have mappings back at 0.
    $parser->reset();
    $io->writeln(' <info>(Done!)</info>');

    if (!empty((array) $row)) {
      $output->addRow($parser->get('entity_type'), $row);
    }



  }




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

    // The exception code is the http status code if the response is set.
    $statusCode = $requestException->getCode();

    // For some common http statuses we want to store in separate error files.
    switch ($statusCode) {
      case 500:
      case 404:
      case 400:
        $msg = "{$url} -- {$requestException->getMessage()}";
        $type = "error-{$statusCode}";
        break;

      default:
        $msg = "{$url} -- {$requestException->getMessage()}";
        $type = "error-fetch";
    }

    $this->json->mergeRow($type, 'urls', [$msg], true);
    $this->io->error($msg);

    $this->countFailed++;

  }//end crawlFailed()


  /**
   * Called when the crawl has ended.
   */
  public function finishedCrawling()
  {
    $this->io->success("Done!");
    $this->io->success("{$this->countSuccess } URLs successfully fetched, {$this->countFailed} failed.");


  }//end finishedCrawling()


}//end class
