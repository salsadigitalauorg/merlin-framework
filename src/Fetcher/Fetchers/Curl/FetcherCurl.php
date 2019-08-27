<?php

/**
 * Fetcher based on multi curl.
 */

namespace Migrate\Fetcher\Fetchers\Curl;

use Migrate\Fetcher\FetcherBase;
use Migrate\Fetcher\FetcherDefaults;
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
    $concurrency    = ($this->config->get('fetch_options')['concurrency'] ?? FetcherDefaults::CONCURRENCY);
    $allowRedirects = ($this->config->get('fetch_options')['allow_redirects'] ?? FetcherDefaults::ALLOW_REDIRECTS);
    $ignoreSSL      = ($this->config->get('fetch_options')['ignore_ssl_errors'] ?? FetcherDefaults::IGNORE_SSL_ERRORS);

    $timeouts       = ($this->config->get('fetch_options')['timeouts'] ?? []);
    $connectTimeout = ($timeouts['connect_timeout'] ?? FetcherDefaults::TIMEOUT_CONNECT);
    $timeout        = ($timeouts['timeout'] ?? FetcherDefaults::TIMEOUT);

    $curl = new MultiCurl();
    $curl->setConcurrency($concurrency);
    $curl->setConnectTimeout($connectTimeout);
    $curl->setTimeout($timeout);

    $curl->success($this->success());
    $curl->error($this->error());

    if ($allowRedirects) {
      $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
    }

    if ($ignoreSSL) {
      $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
      $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
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
