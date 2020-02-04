<?php

/**
 * Fetcher based on multi curl.
 */

namespace Merlin\Fetcher\Fetchers\Curl;

use Merlin\Fetcher\FetcherBase;
use Merlin\Fetcher\FetcherDefaults;
use Merlin\Fetcher\FetcherInterface;

use Curl\MultiCurl;

/**
 * Class FetcherCurl
 * @package Merlin\Fetcher\Fetchers\Curl
 */
class FetcherCurl extends FetcherBase implements FetcherInterface
{

  /** @var MultiCurl */
  protected $multiCurl;


  /** @inheritDoc */
  public function init()
  {
    $concurrency    = ($this->config->get('fetch_options')['concurrency'] ?? FetcherDefaults::CONCURRENCY);
    $allowRedirects = ($this->config->get('fetch_options')['follow_redirects'] ?? FetcherDefaults::FOLLOW_REDIRECTS);
    $ignoreSSL      = ($this->config->get('fetch_options')['ignore_ssl_errors'] ?? FetcherDefaults::IGNORE_SSL_ERRORS);
    $userAgent      = ($this->config->get('fetch_options')['user_agent'] ?? FetcherDefaults::USER_AGENT);

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

    $curl->setOpt(CURLOPT_USERAGENT, $userAgent);

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
