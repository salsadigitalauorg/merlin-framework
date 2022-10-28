<?php

namespace Merlin\Processor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Merlin\Fetcher\Cache;
use Merlin\Fetcher\FetcherBase;
use Merlin\Fetcher\FetcherDefaults;
use Merlin\Parser\ArrayConfig;
use \Exception;
use GuzzleHttp\Psr7;
use League\Uri\UriString;


/**
 * Fetches a URL, processes it according to config and returns data.
 *
 * You can use sub_fetch either with an existing yaml config
 * file or specify a config inline.
 *
 * Sub fetch expects the input value from your field to be a link.
 *
 * @usage:
 *   processor: sub_fetch
 *   options:
 *     config_file: 'content_config.yml'
 *
 * @usage:
 *   processor: sub_fetch
 *   options:
 *     config:
 *       entity_type: 'content'
 *       mappings:
 *        -
 *          field: title
 *          selector: //h1
 *          type: text
 *
 */

class SubFetch extends ProcessorOutputBase
{


  public function process($value)
  {

    // We expect a link to fetch be passed here.
    $url = $value;

    $opts           = ($this->config['options'] ?? []);

    // Use some of the main fetch options set.
    $mainConfig     = $this->output->getConfig();
    $mainFetchOpts  = ($mainConfig->getData()['fetch_options'] ?? []);

    $useCache       = ($mainFetchOpts['cache_enabled'] ?? true);
    $cacheDir       = ($mainFetchOpts['cache_dir'] ?? "/tmp/merlin_cache");

    // Allow sub fetch to override global cache setting.
    if (isset($opts['cache_enabled'])) {
      $useCache = $opts['cache_enabled'];
    }

    $headers = ($opts['headers'] ?? []);
    $config  = ($opts['config'] ?? null);

    $config_file = ($opts['config_file'] ?? null);
    if (empty($config) && empty($config_file)) {
      throw new \Exception('Either parser "config" array or "config_file" required to use sub_fetch.');
    }

    if (!empty($config)) {
      $config_data = $config;
    } else {
      $config_data = \Spyc::YAMLLoad($config_file);
    }

    if (empty($config_data['entity_type'])) {
      throw new \Exception('Config key "entity_type" must be specified to use sub_fetch.');
    }

    // Sub fetch options (these are the same as standard fetcher options).
    $fetchOpts      = ($config_data['fetch_options'] ?? []);
    $allowRedirects = ($fetchOpts['follow_redirects'] ?? FetcherDefaults::FOLLOW_REDIRECTS);
    $maxRedirects   = ($fetchOpts['max_redirects'] ?? FetcherDefaults::MAX_REDIRECTS);
    $ignoreSSL      = ($fetchOpts['ignore_ssl_errors'] ?? FetcherDefaults::IGNORE_SSL_ERRORS);
    $userAgent      = ($fetchOpts['user_agent'] ?? FetcherDefaults::USER_AGENT);
    $referer        = ($fetchOpts['referer'] ?? null);
    $ipResolve      = ($fetchOpts['ip_resolve'] ?? null);

    $timeouts       = ($fetchOpts['timeouts'] ?? []);
    $connectTimeout = ($timeouts['connect_timeout'] ?? FetcherDefaults::TIMEOUT_CONNECT);
    $timeout        = ($timeouts['timeout'] ?? FetcherDefaults::TIMEOUT);

    // We save the fetched output in a derivative of
    // specified config entity (for ease of checking)
    // the data is also returned to the main caller.
    $config_data['entity_type'] .= "_subfetch";

    // Check for empty url now have entity_type.
    if (empty($url)) {
      $d = [
          'error'    => "Empty URL provided",
          'found_on' => $this->crawler->getUri(),
      ];
      $this->output->addRow("error-subfetch-{$config_data['entity_type']}", (object) $d);
      return;
    }

    $config = new ArrayConfig($config_data);

    // Make sure any unicode chars are urlencoded before passed to Guzzle Uri.
    $url = preg_replace_callback(
        '/[^\x20-\x7f]/',
        function ($match) {
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

    /*
     *  Note:
     *  Currently subfetch is only using the Fetcher class to
     *  process data, not do requests.  This could be changed to
     *  use FetcherCurl and setting the success & error
     *  callbacks to set return data to class property.  This
     *  would remove the need for the Guzzle fetching below.
     */

    $fetcher = new FetcherBase($io, $this->output, $config);

    // Use cache?
    $cache = null;
    if ($useCache) {
      $cache = new Cache($base_uri, $cacheDir);
      $fetcher->setCache($cache);
    }

    // Return data.
    $data = null;

    // $io->writeln("\n--------- START SUB FETCH ---------");
    if ($cache instanceof Cache) {
      if ($cacheJson = $cache->get($url)) {
        $cacheData = json_decode($cacheJson, true);
        if (is_array($cacheData) && key_exists('contents', $cacheData) && !empty($cacheData['contents'])) {
          $contents = $cacheData['contents'];
          $redirect = ($cacheData['redirect'] ?? []);
          $io->writeln("Subfetch (cache): {$url}");
          $data = $fetcher->processContent($url, $contents, $redirect);
        }
      }
    }

    // No cache hit, try request URL.
    if (empty($data)) {
      $url_domainless = str_replace($base_uri, "", $url);

      $io->writeln("Subfetch: {$url}");

      $default_headers = [
          'User-Agent' => $userAgent,
          'Referer'    => ($referer ?? $base_uri),
      ];

      $headers = array_merge($default_headers, $headers);

    $client = new Client(
        [
            'base_uri'        => $base_uri,
            'timeout'         => $timeout,
            'connect_timeout' => $connectTimeout,
            'cookies'         => true,
            'headers'         => $headers,
        ]
    );

      if (!empty($allowRedirects)) {
        $redirects = [
            'max'             => $maxRedirects,
            'referer'         => true,
            'track_redirects' => true,
        ];
      } else {
        $redirects = false;
      }

      try {
        $response = $client->get(
            $url_domainless,
            [
                'curl'            => [
                    CURLOPT_REFERER        => $base_uri,
                    CURLOPT_SSL_VERIFYPEER => ($ignoreSSL ?? true),
                    CURLOPT_SSL_VERIFYHOST => ($ignoreSSL ?? true),
                    CURLOPT_IPRESOLVE      => FetcherBase::getCurlIpResolve($ipResolve),
                ],
                'allow_redirects' => $redirects,
            ]
        );

        // Check headers are content html.
        $content_type = strtolower($response->getHeader('content-type')[0]);
        $ct_html = "text/html";

        if (!empty($opts['json_data'])) {
          // Expect JSON data return, try decode and use result.
          $body = (string) $response->getBody();
          $data = json_decode($body);
          if (json_last_error()) {
            throw new \Exception(
                json_last_error_msg()." when decoding JSON (json_data mode)"
            );
          }
        } else if (substr($content_type, 0, strlen($ct_html)) === $ct_html) {
          // HTML, normal parsing.
          $body = (string) $response->getBody();
          $data = $fetcher->processContent($url, $body);
        } else {
          throw new \Exception("Expected text/html content-type, got: {$content_type}");
        }//end if
      } catch (RequestException $e) {
        $status = null;
        $reason = null;

        if ($e->hasResponse()) {
          $response = $e->getResponse();
          $status = $response->getStatusCode();
          $reason = $response->getReasonPhrase();
        }

        switch ($status) {
          case 500:
          case 404:
          case 400:
            $type = "{$config_data['entity_type']}-error-{$status}";
            break;

          default:
            $type = "{$config_data['entity_type']}-error";
        }

        $d = [
            'type'     => 'subfetch',
            'error'    => $e->getMessage(),
            'url'      => $url,
            'status'   => $status,
            'reason'   => $reason,
            'found_on' => $this->crawler->getUri(),
            'data'     => null,

        ];
        $this->output->addRow($type, (object) $d);
        return $d;
      } catch (\Exception $e) {
        $d = [
            'type'     => 'subfetch',
            'error'    => $e->getMessage(),
            'url'      => $url,
            'found_on' => $this->crawler->getUri(),
            'data'     => null,
        ];
        $this->output->addRow("{$config_data['entity_type']}-error", (object) $d);
        return $d;
      }//end try
    }//end if

    // $io->writeln("\n--------- END SUB FETCH ---------");
    $d = [
        'type'     => 'subfetch',
        'error'    => null,
        'url'      => $url,
        'found_on' => $this->crawler->getUri(),
        'data'     => $data,
    ];
    return $d;

  }//end process()


}//end class
