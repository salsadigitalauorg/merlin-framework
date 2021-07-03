<?php

namespace Merlin\Processor;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Merlin\Fetcher\Cache;
use Merlin\Fetcher\FetcherBase;
use Merlin\Parser\ArrayConfig;
use \Exception;
use GuzzleHttp\Psr7;
use League\Uri\UriString;


class SubFetch extends ProcessorOutputBase
{


  private function getUrl($url, $options=[]) {
    $headers = $options['headers'];

  }//end getUrl()


  public function process($value)
  {

    // We expect a link to fetch be passed into this guy.
    $url = $value;

    if (empty($url)) {
      // TODO: add warning empty url
      return;
    }

    $options = ($this->config['options'] ?? []);

    // HMMMMMM. TODO: From cli --no-cache doesn't seem to get set right here?
// $mainConfig = $this->output->getConfig();
    $useCache     = ($options['cache_enabled'] ?? true);
    $cacheDir     = ($options['cache_dir'] ?? "/tmp/merlin_cache");
    $headers      = ($options['headers'] ?? []);

    $useCache = TRUE;

    $config = $options['config'] ?? null;
    $config_file = $options['config_file'] ?? null;
    if (empty($config) && empty($config_file)) {
      throw new Exception('Either parser "config" array or "config_file" required to use sub_fetch.');
    }

    if (!empty($config)) {
      $config_data = $config;
    } else {
      $config_data = \Spyc::YAMLLoad($config_file);
    }

    // We save the fetched output in a derivative of
    // specified config entity (for ease of checking)
    // the data is also returned to the main caller.
    $config_data['entity_type'] .= "_fetched";

    // Example Array config
    // $config_data = [
// 'entity_type' => "review_summary",
//
// 'mappings' => [
// [
// 'field' => 'zz_title',
// 'selector' => '//*[@id="page"]//h1[1]',
// 'type' => 'text'
// ]
// ]
// ];
    $config = new ArrayConfig($config_data);

    // Guzzle Uri hates Unicode chars and will ruin your day, here
    // we make sure any unicode chars are urlencoded before going in.
    $url = preg_replace_callback(
        '/[^\x20-\x7f]/',
        function($match) {
          return urlencode($match[0]);
        },
        $url
    );

    // Resolve URL.
    $uri = Psr7\Utils::uriFor($url);
    $uri = Psr7\UriResolver::resolve(Psr7\Utils::uriFor($this->crawler->getUri()), $uri);
    $url = (string) $uri;

    // Get the domain name for Guzzle.
    $u = UriString::parse($url);
    $u['path'] = '';
    $u['query'] = null;
    $u['fragment'] = null;
    $base_uri = UriString::build($u);

    $io = $this->output->getIo();

    $fetcher = new FetcherBase($this->output->getIo(), $this->output, $config);

    // Use cache?
    $cache = null;
    if ($useCache) {
      $cache = new Cache($base_uri, $cacheDir);
      $fetcher->setCache($cache);
    }

    // Return data
    $data = null;

    $io->writeln("\n--------- START SUB FETCH ---------");

    if ($cache instanceof Cache) {
      if ($cacheJson = $cache->get($url)) {
        $cacheData = json_decode($cacheJson, true);
        if (is_array($cacheData) && key_exists('contents', $cacheData) && !empty($cacheData['contents'])) {
          $contents = $cacheData['contents'];
          $redirect = ($cacheData['redirect'] ?? []);
          $io->writeln("Fetched (cache): {$url}");
          $data = $fetcher->processContent($url, $contents, $redirect);
        }
      }
    }

    // TODO: CHECK IF PUT INTO CACHE, MIGHT NOT BE RIGHT NOW.
    // No cache hit or didn't work out, try slurp
    if (empty($data)) {
      $url_domainless = str_replace($base_uri, "", $url);

      echo "\n\nTYRING: $url_domainless \n\n";

      $default_headers = [
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36',
          'Referer'    => $base_uri,
      ];

      $headers = array_merge($default_headers, $headers);

    $client = new \GuzzleHttp\Client(
        [
            'base_uri' => $base_uri,
            'timeout'  => 120.0,
            'cookies'  => true,
            'headers'  => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36',
                'Referer'    => "https://www.ipart.nsw.gov.au",
            ],
        ]
    );

      try {
// $response = $client->request(
// 'GET',
// $url_domainless,
// [
// 'query' => $query,
//
// ]
// );
        $response = $client->get(
            $url_domainless,
            [
                'curl' => [
                    CURLOPT_REFERER        => $base_uri,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                ],
            ]
        );

        // Check headers are content html...
        $content_type = strtolower($response->getHeader('content-type')[0]);
        $ct_html = "text/html";
        if (substr($content_type, 0, strlen($ct_html)) === $ct_html) {
          // HTML GOOD TIMES
          echo "** YES GOT HTML!\n\n";
          $body = (string) $response->getBody();
// $json_body = $body->getContents();
          $data = $fetcher->processContent($url, $body);

// file_put_contents("/tmp/zzrah.html", $body);
        } else {
          // TODO: ADD TO SUBFETCH ERROR OUTPUT SAY IT'S A FILE.
          echo "ERROR: DIDNT RECEIVE TEXT/HTML!\n\n";
        }
      } catch (RequestException $e) {
        var_dump($e->getMessage());

        $code = null;
        $reason = null;

        if ($e->hasResponse()) {
          $response = $e->getResponse();
          $code = $response->getStatusCode();
// HTTP status code;
          $reason = $response->getReasonPhrase();
// Response message;
        }

        $d = [
            'error'     => $e->getMessage(),
            'http_code' => $code,
            'reason'    => $reason,
            'url'       => $url,
            'found_on'  => $this->crawler->getUri(),
        ];
        $this->output->addRow("subfetch-http-errors", (object) $d);
      } catch (\Exception $e) {
        var_dump($e->getMessage());
        $d = [
            'error'    => $e->getMessage(),
            'url'      => $url,
            'found_on' => $this->crawler->getUri(),
        ];
        $this->output->addRow("subfetch-http-errors", (object) $d);
      }//end try
    }//end if

    $io->writeln("\n--------- END SUB FETCH ---------");

    $ret = [
        'type'     => 'fetched',
        'url'      => $url,
        'found_on' => $this->crawler->getUri(),
        'data'     => $data,
    ];
    return $ret;

}//end process()


}//end class
