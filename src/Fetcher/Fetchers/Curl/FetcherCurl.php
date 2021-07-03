<?php

/**
 * Fetcher based on multi curl.
 */

namespace Merlin\Fetcher\Fetchers\Curl;

use Merlin\Fetcher\FetcherBase;
use Merlin\Fetcher\FetcherDefaults;
use Merlin\Fetcher\FetcherInterface;
use Curl\MultiCurl;
use Merlin\Reporting\RedirectUtils;

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

    $fetch_options = $this->config->get('fetch_options');

    $concurrency    = ($fetch_options['concurrency'] ?? FetcherDefaults::CONCURRENCY);
    $allowRedirects = ($fetch_options['follow_redirects'] ?? FetcherDefaults::FOLLOW_REDIRECTS);
    $maxRedirects   = ($fetch_options['max_redirects'] ?? FetcherDefaults::MAX_REDIRECTS);
    $ignoreSSL      = ($fetch_options['ignore_ssl_errors'] ?? FetcherDefaults::IGNORE_SSL_ERRORS);
    $userAgent      = ($fetch_options['user_agent'] ?? FetcherDefaults::USER_AGENT);
	  $referer        = ($fetch_options['referer'] ?? null);
	  $ipResolve      = ($fetch_options['ip_resolve'] ?? 'whatever');

    $timeouts       = ($fetch_options['timeouts'] ?? []);
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

    if ($referer) {
      $curl->setOpt(CURLOPT_REFERER, $referer);
    }

    $curl->setOpt(CURLOPT_USERAGENT, $userAgent);

    switch ($ipResolve) {
      case 'v6':
        $curl->setOpt(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
        break;
      case 'v4':
        $curl->setOpt(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        break;
      default:
        $curl->setOpt(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_WHATEVER);
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
