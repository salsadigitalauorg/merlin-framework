<?php

/**
 * Fetcher based on multi curl.
 */

namespace Migrate\Fetcher\Fetchers\Curl;

use Migrate\Fetcher\FetcherBase;
use Migrate\Fetcher\FetcherInterface;

use Curl\MultiCurl;

/**
 * Class FetcherCurl
 * @package Migrate\Fetcher\Fetchers\Curl
 */
class FetcherCurl extends FetcherBase implements FetcherInterface
{

  /** @var MultiCurl */
  protected $multiCurl;


  /** @inheritDoc */
  public function init()
  {
    $concurrency    = ($this->config->get('fetch_options')['concurrency'] ?? 10);
    $allowRedirects = ($this->config->get('fetch_options')['allow_redirects'] ?? true);

    $timeouts       = ($this->config->get('fetch_options')['timeouts'] ?? []);
    $connectTimeout = ($timeouts['connect_timeout'] ?? 10);
    $timeout        = ($timeouts['timeout'] ?? 30);

    $curl = new MultiCurl();
    $curl->setConcurrency($concurrency);
    $curl->setConnectTimeout($connectTimeout);
    $curl->setTimeout($timeout);

    $curl->success($this->success());
    $curl->error($this->error());

    if ($allowRedirects) {
      $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
    }

    $this->multiCurl = $curl;

  }//end init()


  public function addUrl(?string $url)
  {
   $this->multiCurl->addGet($url);

  }//end addUrl()


  public function start()
  {
    $this->multiCurl->start();

  }//end start()


  private function success() {
    return function($instance) {
      $this->processContent($instance->url, $instance->response);
    };

  }//end success()


  private function error() {
    return function ($instance) {
      $this->processFailed($instance->url, $instance->errorCode, $instance->errorMessage);
    };

  }//end error()


}//end class
