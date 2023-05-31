<?php


namespace Merlin\Reporting;

use Curl\MultiCurl;
use Merlin\Fetcher\Cache;
use Dompdf\Dompdf;
use SamChristy\PieChart\PieChartGD;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Generates reports for a Migration.  This verifies URLs on a
 * target domain, check redirects, compares source response etc.
 * Class MigrateReport
 * @package Merlin\Reporting
 */
class MigrateReport
{

  const REPORT_TYPE_CONTENT   = 'content';
  const REPORT_TYPE_MEDIA     = 'media';
  // TODO: Combined report of all run errors, requires updates to error jsons.
  // const REPORT_TYPE_POSTRUN   = 'postrun';.

  /** @var \Curl\MultiCurl  */
  private $multiCurl;

  /** @var array Contains detected HTTP errors */
  private $outErrorsHttp;

  /** @var array Contains non-HTTP errors */
  private $outErrorsGeneral;

  /** @var array Contains various warnings */
  private $outWarnings;

  /** @var array Contains URLs that were 200 OK */
  private $outSuccess;

  /** @var array Contains info about any urls that were redirects */
  private $outRedirects;

  /** @var array List of all urls actually checked */
  private $urlsChecked;

  /** @var string Destination target domain*/
  private $dstDomain;

  /** @var string Source original domain */
  private $srcDomain;

  /** @var string Output directory for files and report */
  private $outputDir;

  /** @var \Merlin\Fetcher\Cache Optional cache instance for extra checks */
  private $cache;

  /** @var array Options for optional thangs. */
  private $options;

  /** @var array Report options. */
  private $reportOptions;

  /** @var Root stem for filenames generated. */
  private $filenameRoot;

  /** @var Detemines if a comparison between source and destination responses is required */
  private $verifySourceResponse;

  /** @var If verifySourceResponse is true we need a hash map of the original urls to lookup.  */
  private $originalUrlHashMap;

  /** @var string Report type */
  protected $reportType = self::REPORT_TYPE_CONTENT;

  /** @var SymfonyStyle for output if you want it. */
  private $io;

  /** @var array of tiles that contains URL list, either yaml or json. */
  private $urlsSource;

  /** @var boolean Saves the files and titles report from the source file name. */
  private $adoptSourceName;

  /** @var ProgressBar Seemingly gliding across the pond without effort a duck's feet are, in fact, rather busy. */
  private $progressBar;


  public function __construct(SymfonyStyle $io, $dstDomain, $srcDomain=null, array $options=[])
  {

    $this->dstDomain = $dstDomain;
    $this->srcDomain = $srcDomain;

    // This enables checking that the source and target have the same http code response.
    $this->verifySourceResponse = ($options['verify_source_response'] ?? false);

    // Cache here is used for comparison to generate some extra info. It is
    // NOT used to cache the requests for reporting purposes.
    $useCache       = ($options['cache_enabled'] ?? true);
    $cacheDir       = ($options['cache_dir'] ?? "/tmp/merlin_cache");
    if ($this instanceof MigrateReportMedia) {
      // We don't cache documents/images/videos et al.
      $options['cache_enabled'] = false;
      $useCache = false;
    }

    if ($useCache) {
      $this->cache = new Cache($dstDomain, $cacheDir);
    }

    // Curl settings.
    $concurrency    = ($options['concurrency'] ?? 10);
    $allowRedirects = ($options['follow_redirects'] ?? true);
    $maxRedirects   = ($options['max_redirects'] ?? 5);
    $ignoreSSL      = ($options['ignore_ssl_errors'] ?? false);
    $timeouts       = ($options['timeouts'] ?? []);
    $connectTimeout = ($timeouts['connect_timeout'] ?? 10);
    $timeout        = ($timeouts['timeout'] ?? 30);

    // Output dir for data and report.
    $outputDir      = ($options['output_dir'] ?? "/tmp");
    $this->outputDir = $outputDir;

    // Options that might be needed around the place.
    $this->options = $options;

    // Report options (title, paper size etc).
    $reportOptions  = ($options['report_options'] ?? []);
    $this->reportOptions = $reportOptions;

    // Name from source.  This names the report files and titles
    // from the source files.  This is a convenience thing.
    $this->adoptSourceName = ($options['adopt_source_name'] ?? true);

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

    $curl->setOpt(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    $this->multiCurl = $curl;

    // TODO: Consider turning these into separate files/streams, or use a streaming XML or json encoder.
    $this->outWarnings = [];
    $this->outErrorsGeneral = [];
    $this->outErrorsHttp = [];
    $this->outSuccess = [];
    $this->outRedirects = [];
    $this->urlsChecked = [];

    if (empty($srcDomain)) {
      if ($this->verifySourceResponse) {
        $this->outWarnings[] = ['message' => 'No source domain specified, original URL responses cannot be verified.'];
      }

      $this->verifySourceResponse = false;
    } else {
      if (!$this->verifySourceResponse) {
        $this->outWarnings[] = ['message' => 'Source domain specified, but verify_source_response not set true in config.  No source vs destination verification has been performed.'];
      }
    }

    // User IO.
    $this->io = $io;

}//end __construct()


  /**
   * Sets the filename root stem used by files generated.
   *
   * @param string $suffix           String to tag onto autogenerated filename stem.
   * @param string $overrideFilename Fully specify entire filename stem manually.
   */
  private function setFilenameRoot($suffix=null, $overrideFilename=null) {

    // $fn = rtrim($this->dstDomain, '/');
    // $fn = preg_replace('/[^a-z0-9]+/','-', strtolower($fn));
    $fn = "merlin-report-{$this->reportType}";

    if (!empty($suffix)) {
      $suffix = preg_replace('/[^a-z0-9]+/','-', strtolower($suffix));
      $fn .= "-{$suffix}";
    }

    if (!empty($overrideFilename)) {
      $filename = preg_replace('/[^a-zA-Z0-9._\-]+/','-', $overrideFilename);
      $fn = $filename;
    }

    $this->filenameRoot = $fn;

  }//end setFilenameRoot()


  /**
   * Sets the file containing the urls source.  How this is
   * processed depends on the report type.
   * @param $source
   */
  public function setUrlsSource($source) {

    // People can specify a single url source file as a
    // string, but our processing requires it to be
    // wrapped in an array as though it were multiple.
    if (is_string($source)) {
      $source = [$source];
    }

    $this->urlsSource = $source;

    $filenameSuffix = null;

    // If we are using the source file names, we set our
    // output path filename and report title to use that.
    if ($this->adoptSourceName) {
      $source = $this->urlsSource[0];
      $parts = pathinfo($source);
      $name = $parts['filename'];

      $filenameSuffix = $name;

      // Set report title if not specified.
      if (!isset($this->reportOptions['title'])) {
        $title = str_replace(["_", "-"], " ", $name);
        $this->reportOptions['title'] = $title;
      }
    } else {
      // Output filename suffix.  You need this if you plan on running
      // multiple media reports so that the file names are unique, or
      // if you don't use name from source files option.
      $filenameSuffix = ($this->options['filename_suffix'] ?? null);
    }

    // If a report title is specified then use that as the filename.
    $reportTitle = ($this->reportOptions['title'] ?? null);
    $adoptTitleName = ($this->reportOptions['adopt_title_name'] ?? true);
    if (!empty($reportTitle) && $adoptTitleName) {
      $filenameSuffix = $reportTitle;
    }

    // Set filename suffix.
    $this->setFilenameRoot($filenameSuffix);

    // Lastly, if full filename override specified, use that instead.
    $outputFileRoot = ($this->options['filename_root'] ?? null);
    if (!empty($outputFileRoot)) {
      $this->setFilenameRoot(null, $outputFileRoot);
    }

  }//end setUrlsSource()


  /**
   * Returns the current url source filename.
   * @return array
   */
  public function getUrlsSource() {
    return $this->urlsSource;

  }//end getUrlsSource()


  /**
   * Callback for URL fetched successfully.
   * @return \Closure
   */
  private function onSuccess() {
    return function($instance) {
      $this->outSuccess[] = [
          'url'            => $instance->url,
          'httpStatusCode' => $instance->httpStatusCode,
      ];
    };

  }//end onSuccess()


  /**
   * Callback for any error that may have occurred during the request.
   * @return \Closure
   */
  private function onError() {
    return function ($instance) {

      $error = [
          'url'              => $instance->url,
          'errorCode'        => $instance->errorCode,
          'errorMessage'     => $instance->errorMessage,
          'curlErrorCode'    => $instance->curlErrorCode,
          'curlErrorMessage' => $instance->curlErrorMessage,
          'httpError'        => $instance->httpError,
          'httpStatusCode'   => $instance->httpStatusCode,
          'httpErrorMessage' => $instance->httpErrorMessage,
      ];

      if ($instance->httpError) {
        $this->outErrorsHttp[] = $error;
      } else {
        $this->outErrorsGeneral[] = $error;
      }
    };

  }//end onError()


  /**
   * Complete call back that is called every time before onSuccess or onError.
   * Here we primarily use this to determine if that URL was a redirect.
   * @return \Closure
   */
  private function onComplete() {
    return function ($instance) {

      // Check if was redirect and populate the redirect info.
      $redirect = RedirectUtils::checkForRedirectMulticurl($instance);
      $isRedirect = ($redirect['redirect'] ?? false);
      if ($isRedirect) {
        $this->outRedirects[] = $redirect;
      }

      // Check if what we cached was a redirect.
      if ($this->cache instanceof Cache) {
        $this->checkIfCachedWasRedirect($instance->url, $isRedirect);
      }

      // If we have an original url hash set & option set then let's compare the original response to new url.
      $originalUrl = null;
      if ($this->verifySourceResponse && !empty($this->originalUrlHashMap)
        && isset($this->originalUrlHashMap[$instance->url])) {
        $originalUrl = $this->originalUrlHashMap[$instance->url];
        // Ahoy - checkForRedirect() does as labelled on the tin, but in the
        // event it isn't a redirect, it also returns the status_code of the url.
        $srcResponse = RedirectUtils::checkForRedirect($originalUrl);
        $srcStatusCode = intval($srcResponse['status_code']);
        $dstStatusCode = intval($instance->httpStatusCode);
        if ($srcStatusCode !== $dstStatusCode) {
          $this->outWarnings[] = [
              'message'         => "Source URL HTTP status code does not match destination URL status code.",
              'src_status_code' => $srcStatusCode,
              'src_url'         => $originalUrl,
              'dst_status_code' => $dstStatusCode,
              'dst_url'         => $instance->url,
          ];
        }

        if ($srcStatusCode !== 200) {
          $this->outWarnings[] = [
              'message'         => "Source URL HTTP status code is not 200 OK.",
              'src_status_code' => $srcStatusCode,
              'src_url'         => $originalUrl,
          ];
        }
      }//end if

      $this->urlsChecked[] = $instance->url;

      if ($this->progressBar) {
        $this->progressBar->advance();
      } else {
        $this->io->writeln("Checked dst url: ".$instance->url);
        if (!empty($originalUrl)) {
          $this->io->writeln("Checked src url: ".$originalUrl);
        }
      }
    };

  }//end onComplete()


  /**
   * If cache path is provided on construct, we check if the original source
   * url was a redirect and how that matches up with the target domain.
   * @param $url
   * @param $isRedirect
   */
  public function checkIfCachedWasRedirect($url, $isRedirect) {
    if ($cached = $this->cache->get($url)) {
      $cachedWasRedirect = ($cached['redirect']['redirect'] ?? false);
      if ($cachedWasRedirect && !$isRedirect) {
        $this->outWarnings[] = [
            'message'               => 'Original url was a redirect, new url is not.',
            'new_url'               => $url,
            'original_url_redirect' => $cached['redirect'],
        ];
      }
    } else {
      $this->outWarnings[] = ['message' => "Using cache comparison, but no cache data found for url: {$url}"];
    }

  }//end checkIfCachedWasRedirect()


  /**
   * Tries to get a list of URLs from a YAML or JSON config or results file.
   * @return array
   * @throws \Exception
   */
  public function getUrlsFromSourceFile() {

    $urls = [];

    if (count($this->urlsSource) > 1) {
      $this->io->writeln("NOTE: Using multiple source files for URL list.");
    }

    // Check for some kind of pattern specified to find source files.
    if (count($this->urlsSource) === 1 && preg_match('/[*?\[\]]/', $this->urlsSource[0])) {
      $urlsSource = [];
      foreach (glob($this->urlsSource[0]) as $filename) {
        $urlsSource[] = $filename;
      }

      $count = count($urlsSource);
      $this->io->writeln("NOTE: Using pattern for matching source files for URL list.  Found {$count} files.");
    } else {
      $urlsSource = $this->urlsSource;
    }

    if ($this instanceof MigrateReportMedia) {
      foreach ($urlsSource as $source) {
        if (!is_readable($source)) {
          throw new \Exception("Cannot read URLs source file '{$source}'.  Aborting. ");
        }

        // Assume JSON results.
        $file = file_get_contents($source);
        $json = json_decode($file, true);
        foreach ($json['data'] as $data) {
          $mediaFile = ($data['file'] ?? null);
          if (!empty($mediaFile)) {
            $urls[] = $mediaFile;
          }
        }
      }
    } else {
      foreach ($urlsSource as $source) {
        // Assume YAML config.
        $parts = pathinfo($source);
        $ext = $parts['extension'];
        if ($ext === 'yml') {
          $yml = \Spyc::YAMLLoad($source);
          $urls = array_merge($urls, ($yml['urls'] ?? []));
        } else if ($ext === 'json') {
          $file = file_get_contents($source);
          $json = json_decode($file, true);
          $urls = array_merge($urls, ($json['urls'] ?? []));
        } else {
          throw new \Exception("Could not determine url source file type from extension '{$source}'.  Aborting.");
        }
      }
    }//end if

    return $urls;

  }//end getUrlsFromSourceFile()


  /**
   * Entry point to start report job.  Overwrite this if
   * your report does something that is a bit different.
   *
   * @param $initOptions
   *
   * @throws \ErrorException
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public function startReport($initOptions=null) {
    $urls = ($initOptions['urls'] ?? null);
    $data = $this->verifyUrls($urls);
    $this->writeReportFiles($data);

  }//end startReport()


  /**
   * Main function that kicks off the process.
   *
   * @param $urls
   *
   * @return array
   * @throws \ErrorException
   */
  public function verifyUrls($urls=null) {

    if (empty($this->filenameRoot)) {
      throw new \Exception("You have called verifyUrls without setting a filename root! QUE?!");
    }

    if (empty($urls)) {
      $urls = $this->getUrlsFromSourceFile();
    }

    $targetDomain = $this->dstDomain;

    $rewriteUrls = ($this->options['rewrite_urls']['rewrite'] ?? false);
    $replacePath = null;
    if ($rewriteUrls) {
      $targetDomain = ($this->options['rewrite_urls']['domain'] ?? $this->dstDomain);
      $replacePath  = ($this->options['rewrite_urls']['path'] ?? null);
    }

    $count = 0;
    foreach ($urls as $url) {
      $p = parse_url($url);
      if (!empty($p['host'])) {
        $originalUrl = $url;
      } else {
        // Assume original url was on source domain.
        $originalUrl = $this->srcDomain."/".$url;
      }

      // Strip host from any urls.
      @$host = $p['scheme']."://".$p['host'];
      $url = str_replace($host, "", $url);

      if (!empty($replacePath)) {
        $url = $replacePath."/".basename($url);
      }

      // Final full destination url.
      $finalDstUrl = $targetDomain.$url;

      // Store a lookup back to our original url from final url.
      if ($rewriteUrls || $this->verifySourceResponse) {
        $this->originalUrlHashMap[$finalDstUrl] = $originalUrl;
      }

      $this->multiCurl->addGet($finalDstUrl);

      $count++;
    }//end foreach

    // Go go gadget progress bar.
    // $this->progressBar = new ProgressBar($this->io, $count);.
    $this->io->writeln("Checking {$count} URLs...");

    if ($this->progressBar) {
      $this->progressBar->start();
    }

    // Start curlin'.
    $this->multiCurl->start();

    if ($this->progressBar) {
      $this->progressBar->finish();
    }

    $this->io->success("URL Check complete");

    $data = $this->buildData($urls);

    return $data;

  }//end verifyUrls()


  /**
   * Builds summary data from the results generated.
   *
   * @param $urls
   *
   * @return array
   */
  public function buildData($urls) {

    $this->io->writeln("Munging data...");

    // Pure url counts.
    $countUrlsToCheck   = count($urls);
    $countUrlsChecked   = count($this->urlsChecked);
    if ($countUrlsToCheck !== $countUrlsChecked) {
      $urlsMissing = array_diff($urls, $this->urlsChecked);
      $this->outWarnings[] = [
          'message'      => 'Urls to check count does not match urls checked.',
          'missing_urls' => $urlsMissing,
      ];
    }

    // Final out message counts.
    $countSuccess       = count($this->outSuccess);
    $countErrorHttp     = count($this->outErrorsHttp);
    $countErrorGeneral  = count($this->outErrorsGeneral);
    $countErrorTotal    = ($countErrorGeneral + $countErrorHttp);
    $countRedirect      = count($this->outRedirects);
    $countWarning       = count($this->outWarnings);

    // Count http errors by code.
    $countErrorHttpCodes = [];
    if ($countErrorHttp > 0) {
      foreach ($this->outErrorsHttp as $httpError) {
        $statusCode = $httpError['httpStatusCode'];
        if (key_exists($statusCode, $countErrorHttpCodes)) {
          $countErrorHttpCodes[$statusCode]++;
        } else {
          $countErrorHttpCodes[$statusCode] = 1;
        }
      }
    }

    // Cache stats and warning.
     $cacheComparisonEnabled = ($this->cache instanceof Cache);
     if ($cacheComparisonEnabled) {
       $cacheStats = $this->cache->getStats();
     } else {
       $cacheStats = null;
       if (!($this instanceof MigrateReportMedia)) {
         $this->outWarnings[] = ['message' => 'Cache disabled - no redirect response comparison performed.'];
       }
     }

    $data = [
        // Summary counts.
        'count_urls_to_check'      => $countUrlsToCheck,
        'count_urls_checked'       => $countUrlsChecked,
        'count_success'            => $countSuccess,
        'count_error_http'         => $countErrorHttp,
        'count_error_general'      => $countErrorGeneral,
        'count_error_total'        => $countErrorTotal,
        'count_error_http_codes'   => $countErrorHttpCodes,
        'count_redirect'           => $countRedirect,
        'count_no_redirect'        => ($countSuccess - $countRedirect),
        'count_warning'            => $countWarning,
        'cache_comparison_enabled' => $cacheComparisonEnabled,
        'cache_stats'              => $cacheStats,
        'success_ratio'            => ($countSuccess / $countUrlsChecked),

        // Output data.  May want to later write to disk separately.
        'warnings'                 => $this->outWarnings,
        'errors_http'              => $this->outErrorsHttp,
        'errors_general'           => $this->outErrorsGeneral,
        'redirects'                => $this->outRedirects,
        'success'                  => $this->outSuccess,
    ];

     $this->io->success("Munge complete!");

     return $data;

  }//end buildData()


  /**
   * Writes all the report files.
   * @param $data
   *
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public function writeReportFiles($data) {

    // Write data to disk.
    if (($this->reportOptions['save_json'] ?? true)) {
      $jsonFilename = $this->outputDir.DIRECTORY_SEPARATOR.$this->filenameRoot.".json";
      $bytesJson = $this->writeData($jsonFilename, $data);
      if ($bytesJson === false) {
        $this->io->error("Failed writing JSON data file: {$jsonFilename}");
      } else {
        $this->io->success("Wrote JSON data file: {$jsonFilename}");
      }
    }

    // Build the HTML report.
    $html = $this->buildHtml($data);

    // Save the HTML.
    if (($this->reportOptions['save_html'] ?? true)) {
      $htmlFilename = $this->outputDir.DIRECTORY_SEPARATOR.$this->filenameRoot.".html";
      $bytesHtml = $this->writeHtml($htmlFilename, $html);
      if ($bytesHtml === false) {
        $this->io->error("Failed writing HTML report file: {$htmlFilename}");
      } else {
        $this->io->success("Wrote HTML report file: {$htmlFilename}");
      }
    }

    // Save the PDF.
    if (($this->reportOptions['save_pdf'] ?? false)) {
      $pdfFilename = $this->outputDir.DIRECTORY_SEPARATOR.$this->filenameRoot.".pdf";
      $paperSize = ($this->reportOptions['paper_size'] ?? 'A4');
      $paperOrientation = ($this->reportOptions['paper_orientation'] ?? 'landscape');
      $bytesPDF = $this->writePDF($pdfFilename, $html, $paperSize, $paperOrientation);
      if ($bytesPDF === false) {
        $this->io->error("Failed writing PDF report file: {$pdfFilename}");
      } else {
        $this->io->success("Wrote PDF report file: {$pdfFilename}");
      }
    }

  }//end writeReportFiles()


  /**
   * Builds the HTML report using the generated data and twig template.
   * @param $data
   *
   * @return string
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  private function buildHtml($data) {

    // Note: twig will throw an exception if there is doom.
    $loader = new \Twig\Loader\FilesystemLoader(__DIR__."/html_templates");
    $twig = new \Twig\Environment(
        $loader,
        // Note: Currently not caching templates.
        ['cache' => false]
    );

    $b64logo = base64_encode(file_get_contents(__DIR__."/html_templates/merlin_logo.png"));

    $summary = [
        [
            'label' => 'Total URLs requested check',
            'value' => $data['count_urls_to_check'],
        ],
        [
            'label' => 'Total URLs actually checked',
            'value' => $data['count_urls_to_check'],
        ],
        [
            'label' => 'Total Success',
            'value' => $data['count_success'],
        ],
        [
            'label' => 'Total Redirect (included in Success)',
            'value' => $data['count_redirect'],
        ],
        [
            'label' => 'Goodness Factor',
            'value' => number_format(($data['success_ratio'] * 100), 0)."%",
        ],
        [
            'label' => 'Total Warnings',
            'value' => $data['count_warning'],
        ],
        [
            'label' => 'Total with HTTP Error',
            'value' => $data['count_error_http'],
        ],
        [
            'label' => 'Total with Other Error',
            'value' => $data['count_error_general'],
        ],
    ];

    // Sweet Pies.
    $chart = new PieChartGD(600, 375);
    if ($data['count_no_redirect'] > 0) {
      $chart->addSlice('Success', $data['count_no_redirect'], '#00ff00');
    }

    if ($data['count_redirect']) {
      $chart->addSlice('Success (Redirect)', $data['count_redirect'], '#00bb00');
    }

    if ($data['count_error_http']) {
      $chart->addSlice('HTTP Error', $data['count_error_http'], '#dd0000');
    }

    if ($data['count_error_general']) {
      $chart->addSlice('Other Error', $data['count_error_general'], '#bb0000');
    }

    $chart->draw();
    $temp = tempnam(sys_get_temp_dir(), 'merlin_').".png";
    $chart->savePNG($temp);
    $chart->destroy();
    $b64pie1 = base64_encode(file_get_contents($temp));
    unlink($temp);

    // Warnings & Errors.
    $warnings       = $this->prettyPrintOutput($this->outWarnings);
    $errorsHttp     = $this->prettyPrintOutput($this->outErrorsHttp);
    $errorsGeneral  = $this->prettyPrintOutput($this->outErrorsGeneral);

    // Report title or default title.
    $title = ($this->reportOptions['title'] ?? null);
    if (empty($title)) {
      switch ($this->reportType) {
        case self::REPORT_TYPE_MEDIA:
          $title = 'Media URL Migration Report';
          break;
        case self::REPORT_TYPE_CONTENT;
          // Fall Through.
        default:
          $title = 'Content URL Migration Report';
      }
    }

    // Put it into the array for twig.
    $templateData = [
        'title'          => $title,
        'domain'         => $this->dstDomain,
        'date'           => date('l jS \of F Y h:i:s A'),
        'b64logo'        => 'data:image/png;charset=utf-8;base64,'.$b64logo,
        'b64pie1'        => 'data:image/png;charset=utf-8;base64,'.$b64pie1,
        'summary'        => $summary,
        'cache_stats'    => $data['cache_stats'],
        'redirects'      => $data['redirects'],
        'warnings'       => $warnings,
        'errors_http'    => $errorsHttp,
        'errors_general' => $errorsGeneral,
    ];

    $html = $twig->render('migrate_url_report.html', $templateData);

    return $html;

}//end buildHtml()


  /**
   * Format the array objects for output.
   * TODO: no doubt could be prettier ;)
   *
   * @param $output
   *
   * @return array
   */
  public function prettyPrintOutput($output) {
    $out = [];
    foreach ($output as $item) {
      $out[] = print_r($item, 1);
    }

    return $out;

  }//end prettyPrintOutput()


  /**
   * Writes the summary data to disk.
   *
   * @param $filename
   * @param $data
   *
   * @return false|int
   */
  private function writeData($filename, $data) {
    $data = json_encode($data);
    return file_put_contents($filename, $data);

  }//end writeData()


  /**
   * Writes the HTML version of the report to disk.
   *
   * @param $filename
   * @param $html
   *
   * @return false|int
   */
  private function writeHtml($filename, $html) {
    return file_put_contents($filename, $html);

  }//end writeHtml()


  /**
   * Writes the PDF version of the report to disk.
   * WARNING: This is currently using DOMPDF. For large results/tables/many pages it is SLOW.
   * TODO: REPLACE WITH SOMETHING FASTER.
   *
   * @param        $filename
   * @param        $html
   * @param string $paperSize
   * @param string $orientation
   *
   * @return false|int
   */
  private function writePDF($filename, $html, $paperSize='A4', $orientation='landscape') {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper($paperSize, $orientation);
    $dompdf->render();
    $pdf = $dompdf->output();
    return file_put_contents($filename, $pdf);

  }//end writePDF()


  /**
   * Uses a config file to automatically generate the reports specified.
   *
   * @param \Symfony\Component\Console\Style\SymfonyStyle|null $io
   *
   * @param array                                              $config
   *
   * @param string|null                                        $outputDir
   *
   * @throws \ErrorException
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public static function generateReportsFromConfig(SymfonyStyle $io, array $config, string $outputDir=null) {

    $reports = ($config['reports'] ?? null);
    if (empty($reports)) {
      $io->writeln("No reports specified in config file!  Nothing to do...");
      exit(1);
    }

    foreach ($reports as $report) {
      $enabled    = ($report['enabled'] ?? true);
      if (!$enabled) {
        $io->warning("A report is set as enabled=false, skipping...");
        continue;
      }

      $dstDomain  = ($report['dst_domain'] ?? null);
      $srcDomain  = ($report['src_domain'] ?? null);
      $reportType = ($report['type'] ?? null);
      $urlSource  = ($report['url_source'] ?? null);
      $options    = ($report['options'] ?? []);

      // The command line output dir will overwrite any config output dir.
      if (!empty($outputDir)) {
        $options['output_dir'] = $outputDir;
      }

      $io->section("Generating {$reportType} report for {$dstDomain}");
      $mr = MigrateReport::MigrateReportFactory($reportType, $io, $dstDomain, $srcDomain, $options);
      $mr->setUrlsSource($urlSource);
      $mr->startReport();
      unset($mr);
    }//end foreach

}//end generateReportsFromConfig()


  /**
   * Returns a MigrateReport instance of the specified type.
   * @param $type
   * @param $io
   * @param $dstDomain
   * @param $srcDomain
   * @param $options
   *
   * @return \Merlin\Reporting\MigrateReport|\Merlin\Reporting\MigrateReportMedia
   * @throws \Exception
   */
  public static function MigrateReportFactory($type, $io, $dstDomain, $srcDomain, $options) {
    switch ($type) {
      case self::REPORT_TYPE_CONTENT:
        return new MigrateReport($io, $dstDomain, $srcDomain, $options);
        break;
      case self::REPORT_TYPE_MEDIA:
        return new MigrateReportMedia($io, $dstDomain, $srcDomain, $options);
        break;
      default:
        $class = 'Merlin\\Reporting\\MigrateReport' . str_replace('_', '', ucwords($type, '_'));
        if(class_exists($class))
        {
          return new $class($io, $dstDomain, $srcDomain, $options);
        }
        throw new \Exception("Unknown report type: '{$type}'  Aborting.");
        exit(1);
    }

  }//end MigrateReportFactory()


}//end class
