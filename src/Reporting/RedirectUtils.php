<?php

namespace Merlin\Reporting;

use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;


/***
 * Class RedirectUtils
 * @package Merlin\Reporting
 */
class RedirectUtils
{


  /**
   * Checks if the url is a redirect and if so returns the status code
   * source url and effective destination url.  Also returns the raw
   * headers, regardless of if the url was a redirect or not.
   *
   * @param string                                   $url
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *
   * @return array|null
   */
  public static function checkForRedirect(string $url, ResponseInterface $response=null) {

    if (empty($url)) {
      return null;
    }


    $redirect = false;

    if ($response) {

      // We have a response object from Guzzle passed in so use that to figure things out.
      if ($response->getHeader('X-Guzzle-Redirect-History')) {
        $redirect = true;
        $history = $response->getHeader('X-Guzzle-Redirect-History');
        $historyCode = $response->getHeader('X-Guzzle-Redirect-Status-History');
        $redirectCount = count($history);
        $statusCodeOrigin = intval($historyCode[0]);
        $destUri = end($history);
      }

      $statusCodeDestination = $response->getStatusCode();

      // This is not exactly the "raw headers" here because it's been Guzzlified.
      $rawHeaders = "";
      foreach ($response->getHeaders() as $name => $values) {
        $rawHeaders .= $name.': '.implode(', ', $values)."\r\n";
      }

    } else {
        // Do a curl and check if its a redirect.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        $rawHeaders = curl_exec($ch);
        $info = curl_getinfo($ch);
        $destUri = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);


        $redirectCount = ($info['redirect_count'] ?? 0);
        $redirect = $redirectCount > 0;

        $statusCodeDestination = intval($info['http_code']);

        $statusCodeOrigin = null;

        // Find the first redirect in raw headers.
        $headers = (explode("\r\n", $rawHeaders));
        foreach ($headers as $key => $r) {
          if (stripos($r, 'HTTP/1.1') === 0) {
            list(, $statusCodeOrigin, $status) = explode(' ', $r, 3);
            $statusCodeOrigin = intval($statusCodeOrigin);
            if ($statusCodeOrigin >= 300 && $statusCodeOrigin < 400) {
              break;
            } else {
              // Let it be the last found code.. this would be weird.
            }
          }
        }



    }//end if

    if ($redirect) {
      $ret = [
          'status_code_original' => $statusCodeOrigin,
          'status_code'          => $statusCodeDestination,
          'redirect'             => true,
          'redirect_count'       => $redirectCount,
          'url_original'         => $url,
          'url_effective'        => $destUri,
          'raw_headers'          => $rawHeaders,
      ];
    } else {
      $ret = [
          'status_code'   => $statusCodeDestination,
          'redirect'      => false,
          'raw_headers'   => $rawHeaders,
          'url_effective' => $url,
      ];
    }//end if

    return $ret;

  }//end checkForRedirect()


  /**
   * Checks if this Multicurl request ended up being a redirect.
   *
   * @param $instance
   *
   * @return array|bool
   */
  public static function checkForRedirectMulticurl($instance) {

    $ch = $instance->curl;

    $url = $instance->url;
    $rawHeaders = $instance->rawResponseHeaders;

    $info = curl_getinfo($ch);

    $redirectCount = ($info['redirect_count'] ?? 0);
    $redirect = $redirectCount > 0;

    $statusCodeDestination = intval($info['http_code']);

    if ($redirect) {
      $destUri = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

      // Fish out the original redirect header to get the status code. Note
      // this is not smart - it looks for the initial redirect only and
      // isn't checking for [Ll]ocation: or keeping track of multiple redirects etc.
      // If you need a more robust way, maybe consider pecl_http has parse_http_headers().
      $statusCodeOrigin = null;
      $headers = (explode("\r\n", $rawHeaders));
      foreach ($headers as $key => $r) {
        if (stripos($r, 'HTTP/1.1') === 0) {
          list(,$statusCodeOrigin, $status) = explode(' ', $r, 3);
          $statusCodeOrigin = intval($statusCodeOrigin);
          if ($statusCodeOrigin >= 300 && $statusCodeOrigin < 400) {
            break;
          } else {
            // Let it be the last found code.. this would be weird.
          }
        }
      }

      $ret = [
          'status_code_original' => $statusCodeOrigin,
          'status_code'          => $statusCodeDestination,
          'redirect'             => $redirect,
          'redirect_count'       => $redirectCount,
          'url_original'         => $url,
          'url_effective'        => $destUri,
          'raw_headers'          => $instance->rawResponseHeaders,
      ];
    } else {
      $ret = [
          'status_code'   => $statusCodeDestination,
          'redirect'      => false,
          'raw_headers'   => $rawHeaders,
          'url_effective' => $url,
      ];
    }//end if

    return $ret;

}//end checkForRedirectMulticurl()


}//end class
