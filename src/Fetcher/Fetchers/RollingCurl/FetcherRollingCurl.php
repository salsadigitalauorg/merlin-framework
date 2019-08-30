<?php
/**
 * Fetcher based on RollingCurl.
 * @deprecated
 */

namespace Migrate\Fetcher\Fetchers\RollingCurl;

use Migrate\Fetcher\FetcherBase;
use Migrate\Fetcher\FetcherDefaults;
use Migrate\Fetcher\FetcherInterface;
use RollingCurl\Request;
use RollingCurl\RollingCurl;

/**
 * Class FetcherRollingCurl
 * @deprecated
 * @package Migrate\Fetcher\Fetchers\RollingCurl
 */
class FetcherRollingCurl extends FetcherBase implements FetcherInterface
{

  /** @var RollingCurl */
  protected $rollingCurl;


  /** @inheritDoc */
  public function init() {
    $concurrency    = ($this->config->get('fetch_options')['concurrency'] ?? FetcherDefaults::CONCURRENCY);
    $allowRedirects = ($this->config->get('fetch_options')['allow_redirects'] ?? FetcherDefaults::ALLOW_REDIRECTS);
    $ignoreSSL      = ($this->config->get('fetch_options')['ignore_ssl_errors'] ?? FetcherDefaults::IGNORE_SSL_ERRORS);
    $userAgent      = ($this->config->get('fetch_options')['user_agent'] ?? FetcherDefaults::USER_AGENT);

    $timeouts       = ($this->config->get('fetch_options')['timeouts'] ?? []);
    $connectTimeout = ($timeouts['connect_timeout'] ?? FetcherDefaults::TIMEOUT_CONNECT);
    $timeout        = ($timeouts['timeout'] ?? FetcherDefaults::TIMEOUT);

    $this->rollingCurl = new RollingCurl();
    $this->rollingCurl->setSimultaneousLimit($concurrency);

    $options = [
        CURLOPT_FOLLOWLOCATION => $allowRedirects,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_SSL_VERIFYHOST => $ignoreSSL,
        CURLOPT_SSL_VERIFYPEER => $ignoreSSL,
        CURLOPT_USERAGENT      => $userAgent,
    ];

    $this->rollingCurl->addOptions($options);
    $this->rollingCurl->setCallback($this->requestCallback());

  }//end init()


  /** @inheritDoc */
  public function addUrl(?string $url)
  {
    $this->rollingCurl->get($url);

  }//end addUrl()


  /** @inheritDoc */
  public function start() {
    $this->rollingCurl->execute();

  }//end start()


  private function requestCallback()
  {

    return function (Request $request, RollingCurl $curl) {
      // Handle HTTP statuses.
      $status = $request->getResponseInfo()["http_code"];

      switch ($status) {
        case 500:
        case 404:
        case 400:
          // Note: getResponseError() doesn't appear to contain the error.
          $this->processFailed($request->getUrl(), $status, $request->getResponseError());
          return;
      }

      $this->processContent($request->getUrl(), $request->getResponseText());

      // Clear list of completed requests to avoid memory growth.
      $curl->clearCompleted();
    };

  }//end requestCallback()


}//end class
