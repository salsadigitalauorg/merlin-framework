<?php

namespace Migrate\Fetcher;

use Migrate\Command\GenerateCommand;
use Migrate\Exception\ElementNotFoundException;
use Migrate\Exception\ValidationException;
use Migrate\Parser\ParserInterface;
use Migrate\Reporting\RedirectUtils;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use function DeepCopy\deep_copy;


class FetcherBase implements FetcherInterface
{

  /** @var \Migrate\Parser\ParserInterface */
  protected $config;

  /** @var \Migrate\Output\OutputInterface */
  protected $output;

  /** @var \Symfony\Component\Console\Output\OutputInterface */
  protected $io;

  /** @var \Migrate\Fetcher\ContentHash */
  protected $hashes;

  /** @var \Migrate\Fetcher\Cache */
  protected $cache;

  /** @var array */
  protected $counts;


  /**
   * FetcherBase constructor.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $io
   * @param \Migrate\Output\OutputInterface                   $json
   * @param \Migrate\Parser\ParserInterface                   $config
   */
  public function __construct(OutputInterface $io, \Migrate\Output\OutputInterface $json, ParserInterface $config)
  {
    $this->io = $io;
    $this->output = $json;
    $this->config = $config;

    $this->counts = [
        'fetched'       => 0,
        'fetched_cache' => 0,
        'failed'        => 0,
        'total'         => 0,
    ];

    if (($config->get('url_options')['find_content_duplicates'] ?? true)) {
      $this->hashes = new ContentHash($config);
    }

    $this->init();

  }//end __construct()


  /** @inheritDoc */
  public function init() {
    /*
     *  Implement in your Fetcher class, basically a __construct() substitute.
     */

  }//end init()


  /** #@inheritDoc */
  public function addUrl(?string $url) {
    throw new \Exception('Your Fetcher class does not implement the addUrl() method!');

  }//end addUrl()


  /** @inheritDoc */
  public function start() {
    throw new \Exception('Your Fetcher class does not implement the start() method!');

  }//end start()


  /**
   * Sets the cache instance for this fetcher to use to cache fetched content.
   * @param \Migrate\Fetcher\Cache $cache
   */
  public function setCache(Cache $cache) {
    $this->cache = $cache;

  }//end setCache()


  /**
   * @return \Migrate\Parser\ParserInterface
   */
  public function getConfig() {
    return $this->config;

  }//end getConfig()


  /**
   * @return \Symfony\Component\Console\Output\OutputInterface
   */
  public function getIo() {
    return $this->io;

  }//end getIo()


  /**
   * @return \Migrate\Output\OutputInterface
   */
  public function getOutput() {
    return $this->output;

  }//end getOutput()


  /**
   * Returns the count data
   * @return array
   */
  public function getCounts() {
    return $this->counts;

  }//end getCounts()


  /**
   * Increments a fetch counter.
   * @param $countKey
   */
  public function incrementCount($countKey) {
    if (key_exists($countKey, $this->counts)) {
      $this->counts[$countKey]++;
    }

  }//end incrementCount()


  /**
   * Processes a URL and its respective html content according to the config map.
   *
   * @param string     $url
   * @param string     $html
   *
   * @param array|null $redirect
   *
   * @throws \Exception
   */
  public function processContent(string $url, string $html, array $redirect=[]) {

    $row = new \stdClass;

    $io = $this->io;
    $output = $this->output;
    $parser = $this->config;
    $entity_type = $parser->get('entity_type');

    $io->write('Parsing: '.$url);

    // Strips any script tags, which can be problematic when parsed by DOMDocument.
    $stripScriptTags = ($this->config->get('url_options')['raw_strip_script_tags'] ?? false);
    if ($stripScriptTags !== false) {
      $pattern = null;
      if (filter_var($stripScriptTags, FILTER_VALIDATE_BOOLEAN)) {
        $pattern = '#<script(.*?)>(.*?)</script>#is';
      }

      if (!empty($pattern)) {
        $html = preg_replace($pattern, '', $html);
      }
    }

    // Similarly, if we have a raw pattern replace specified, do that.
    $raw_prp = ($this->config->get('url_options')['raw_pattern_replace']['pattern'] ?? null);
    $raw_prr = ($this->config->get('url_options')['raw_pattern_replace']['replace'] ?? null);
    if (is_string($raw_prp) && !empty($raw_prr)) {
      $html = preg_replace($raw_prp, $raw_prr, $html);
    }


    // Get raw headers and redirect info.
    $isRedirect = ($redirect['redirect'] ?? false);
    if ($isRedirect) {
      $this->output->mergeRow("{$entity_type}-redirects", 'redirects', [$redirect], true);
    }

    // Add to cache if we are doing that.
    if ($this->cache instanceof Cache && !$this->cache->exists($url)) {
      // Check for malformed UTF-8 encoding.
      // NOTE: Only checking content, not $url which assuming are OK (!).
      $testJson = json_encode($html);
      if (json_last_error() === JSON_ERROR_UTF8) {
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
      }

      $data = [
          'url'      => $url,
          'contents' => $html,
      ];

      if ($isRedirect) {
        $data['redirect'] = $redirect;
      }

      $cacheJson = json_encode($data);

      // Check for any more strange happenings and record it.
      if (json_last_error()) {
        $jsonErrMsg = json_last_error_msg();
        $output->mergeRow("error-json-cache-fail", 'urls', ["{$url} -- json_error: {$jsonErrMsg}"], true);
      }

      $this->cache->put($url, $cacheJson);
    }//end if

    // Check if duplicate if we are doing that.
    $duplicate = false;
    if ($this->hashes instanceof ContentHash) {
      $duplicate = $this->hashes->put($url, $html);
    }

    if ($duplicate === false) {
        $crawler = new Crawler($html, $url);
        while ($field = $parser->getMapping()) {
        // $crawler = new Crawler($html, $url);
          $type = GenerateCommand::TypeFactory($field['type'], $crawler, $output, $row, $field);
          try {
            $type->process();
          } catch (ElementNotFoundException $e) {
            $output->mergeRow("{$entity_type}-".$e::FILE, $url, [$e->getMessage()], true);
          } catch (ValidationException $e) {
            $output->mergeRow("{$entity_type}-".$e::FILE, $url, [$e->getMessage()], true);
          } catch (\Exception $e) {
            $output->mergeRow("{$entity_type}-error-unhandled", $url, [$e->getMessage()], true);
          }
        }//end while
    }

    // Reset the parser so we have mappings back at 0.
    $parser->reset();
    $io->writeln(' <info>(Done!)</info>');

    if (!empty((array) $row)) {
      $output->addRow($entity_type, $row);
    }

  }//end processContent()


  /**
   * Processes a http status code that is counted as a URL failure (e.g. 500, 404 etc).
   * @param string|null $url
   * @param int|null    $status
   * @param string|null $errorMessage
   */
  public function processFailed(?string $url, ?int $status, ?string $errorMessage) {

    $entity_type = $this->config->get('entity_type');

    // For some common http statuses we want to store in separate error files.
    switch ($status) {
      case 500:
      case 404:
      case 400:
        $msg = "{$url} -- {$errorMessage}";
        $type = "{$entity_type}-error-{$status}";
        break;

      default:
        $msg = "{$url} -- {$errorMessage}";
        $type = "{$entity_type}-error-fetch";
    }

    $this->output->mergeRow($type, 'urls', [$msg], true);
    $this->io->error($msg);

  }//end processFailed()


  /**
   * Called when fetching is completed.
   */
  public function complete() {

    // Print some basic stats.
    $fetched       = $this->counts['fetched'];
    $fetched_cache = $this->counts['fetched_cache'];
    $failed        = $this->counts['failed'];
    $total         = $this->counts['total'];

    $msg = "{$total} URLs processed ({$fetched} fetched, {$fetched_cache} from cache), {$failed} failed.";

    if ($failed === 0) {
      $this->io->success($msg);
    } else {
      $this->io->error($msg);
    }

    // Build the duplicates file.
    if ($this->hashes instanceof ContentHash) {
      $duplicateUrls = $this->hashes->getDuplicates();
      if (!empty($duplicateUrls)) {
        $config = $this->getConfig();
        $this->output->mergeRow($config->get('entity_type').'-content-duplicates', 'duplicates', $duplicateUrls, true);
      }
    }

  }//end complete()


  /**
   * Returns an instance of a valid Fetcher.
   * @param string                                            $fetcherClass
   * @param \Symfony\Component\Console\Output\OutputInterface $io
   * @param \Migrate\Output\OutputInterface                   $json
   * @param \Migrate\Parser\ParserInterface                   $config
   *
   * @return \Migrate\Fetcher\FetcherBase
   * @throws \Exception
   */
  public static function FetcherFactory(string $fetcherClass, OutputInterface $io,
                                        \Migrate\Output\OutputInterface $json, ParserInterface $config) {

    if (!class_exists($fetcherClass)) {
      throw new \Exception("Specified Fetcher class: $fetcherClass does not exist!");
    }

    if (!is_subclass_of($fetcherClass, '\\Migrate\\Fetcher\\FetcherBase')) {
      throw new \Exception("Specified Fetcher class does not extend FetcherBase!");
    }

    $fetcher  = new $fetcherClass($io, $json, $config);

    return $fetcher;

  }//end FetcherFactory()


}//end class
