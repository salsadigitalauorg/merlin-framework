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
    $allowRedirects = ($this->config->get('fetch_options')['follow_redirects'] ?? FetcherDefaults::FOLLOW_REDIRECTS);
    $maxRedirects   = ($this->config->get('fetch_options')['follow_redirects'] ?? FetcherDefaults::MAX_REDIRECTS);

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
      $this->processContent($instance->url, $instance->response);
    };

  }//end onSuccess()


  private function onError() {
    return function ($instance) {
      $this->processFailed($instance->url, $instance->errorCode, $instance->errorMessage);
    };

  }//end onError()


  private function onComplete() {
    return function ($instance) {
      $this->checkForRedirect($instance);
    };

  }//end onComplete()


  /**
   * Checks if this curl request ended up being a redirect.
   * @param $instance
   */
  private function checkForRedirect($instance) {

    $ch = $instance->curl;
    $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT) > 0;
    if ($redirect) {
      $destUri = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
      $originUri = $instance->url;

      // Fish out the original redirect header to get the status code. Note
      // this is not smart - it looks for the initial status only, not even 3xx, and
      // isn't checking for [Ll]ocation: or keeping track of multiple redirects etc.
      $statusCode = null;
      $headers = (explode("\r\n", $instance->rawResponseHeaders));
      foreach ($headers as $key => $r) {
        if (stripos($r, 'HTTP/1.1') === 0) {
          list(,$statusCode, $status) = explode(' ', $r, 3);
          break;
        }
      }

      /*
          // If you need a more robust way, you can use pecl_http has parse_http_headers()
          // function, or if you don't want to install that, another option is to make
          // a new curl request and let it parse the headers for you.
          // Check Reporting/RedirectUtils::
      */

      $redirect = [];
      $redirect[] = [
          'origin'      => $originUri,
          'destination' => $destUri,
          'status_code' => $statusCode,
      ];

      $entity_type = $this->config->get('entity_type');

      $this->output->mergeRow("{$entity_type}-redirects", 'redirects', $redirect, true);
    }//end if

}//end checkForRedirect()


}//end class
