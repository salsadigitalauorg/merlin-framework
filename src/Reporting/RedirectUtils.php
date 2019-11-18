<?php

namespace Migrate\Reporting;


class RedirectUtils
{


  /**
   * Checks if the url is a redirect and if so returns the status code
   * source url and effective destination url.  Also returns the raw
   * headers, regardless of if the url was a redirect or not.
   *
   * @param string $url
   *
   * @return array|null
   */
  public static function checkRedirect(string $url) {

    if (empty($url)) {
      return null;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $rawHeaders = curl_exec($ch);
    $info = curl_getinfo($ch);

    $redirectCount = ($info['redirect_count'] ?? 0);
    $redirect = $redirectCount > 0;

    $destUri = null;
    if ($redirect > 0) {
      $destUri = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    }

    $statusCodeOrigin = null;
    $statusCodeDestination = null;

    $headers = (explode("\r\n", $rawHeaders));
    foreach ($headers as $key => $r) {
      if (stripos($r, 'HTTP/1.1') === 0) {
        list(,$statusCodeOrigin, $status) = explode(' ', $r, 3);
        break;
      }
    }

    $statusCodeOrigin = intval($statusCodeOrigin);
    $statusCodeDestination = intval($info['http_code']);

    $ret = [
        'status_code_origin'      => $statusCodeOrigin,
        'status_code_destination' => $statusCodeDestination,
        'redirect'                => $redirect,
        'redirect_count'          => $redirectCount,
        'url_origin'              => $url,
        'url_destination'         => $destUri,
        'raw_headers'             => $rawHeaders,
    ];

    curl_close($ch);

    return $ret;

  }//end checkRedirect()


}//end class
