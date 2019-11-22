<?php

/**
 * Fetcher based on multi curl.
 */

namespace Migrate\Fetcher\Fetchers\Curl;

use Migrate\Fetcher\FetcherBase;
use Migrate\Fetcher\FetcherDefaults;
use Migrate\Fetcher\FetcherInterface;

use Curl\MultiCurl;
use Migrate\Reporting\RedirectUtils;

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
    $allowRedirects = ($this->config->get('fetch_options')['follow_redirects'] ?? FetcherDefaults::FOLLOW_REDIRECTS);
    $maxRedirects   = ($this->config->get('fetch_options')['max_redirects'] ?? FetcherDefaults::MAX_REDIRECTS);

    $ignoreSSL      = ($this->config->get('fetch_options')['ignore_ssl_errors'] ?? FetcherDefaults::IGNORE_SSL_ERRORS);
    $userAgent      = ($this->config->get('fetch_options')['user_agent'] ?? FetcherDefaults::USER_AGENT);

    $timeouts       = ($this->config->get('fetch_options')['timeouts'] ?? []);
    $connectTimeout = ($timeouts['connect_timeout'] ?? FetcherDefaults::TIMEOUT_CONNECT);
    $timeout        = ($timeouts['timeout'] ?? FetcherDefaults::TIMEOUT);

    $curl = new MultiCurl();
    $curl->setConcurrency($concurrency);
    $curl->setConnectTimeout($connectTimeout);
    $curl->setTimeout($timeout);

    $curl->success($this->onSuccess());
    $curl->error($this->onError());
    $curl->complete($this->onComplete());

    if ($allowRedirects) {
      $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
      $curl->setOpt(CURLOPT_MAXREDIRS, $maxRedirects);
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


  private function onSuccess() {
    return function($instance) {
      $redirect = RedirectUtils::checkForRedirectMulticurl($instance);
      $this->processContent($instance->url, $instance->response, $redirect);
    };

  }//end onSuccess()


  private function onError() {
    return function ($instance) {
      /*
          // We could add failed redirects to the results too but not sure this is useful.
          $redirect = RedirectUtils::checkForRedirectMulticurl($instance);
          $isRedirect = ($redirect['redirect'] ?? false);
          if ($isRedirect) {
          $entity_type = $this->config->get('entity_type');
          $this->output->mergeRow("{$entity_type}-redirects", 'redirects', [$redirect], true);
          }
      */

      $this->processFailed($instance->url, $instance->errorCode, $instance->errorMessage);
    };

  }//end onError()


  private function onComplete() {
    return function ($instance) {
    };

  }//end onComplete()


}//end class
