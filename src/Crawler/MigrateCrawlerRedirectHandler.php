<?php

namespace Migrate\Crawler;


use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use  \Migrate\Output\OutputBase;

class MigrateCrawlerRedirectHandler
{

  /** @var array */
  private $config;

  /** @var \Migrate\Output\OutputBase */
  private $json;

  /**
   * The file name for the results file that contains redirect info.
   * @var string
   */
  private $filename;


  /**
   * MigrateCrawlerRedirectHandler constructor.
   *
   * @param array                      $config
   * @param \Migrate\Output\OutputBase $json
   * @param string                     $resultFilename
   */
  public function __construct(array $config, OutputBase $json, string $resultFilename)
  {
    $this->config = $config;
    $this->json = $json;
    $this->filename = $resultFilename;

  }//end __construct()


  public function getRedirectOptions() {

    $followRedirects = ($this->config['options']['follow_redirects'] ?? true);
    if ($followRedirects === false) {
      return false;
    }

    $maxRedirects = ($this->config['options']['max_redirects'] ?? 5);

    $redirectOptions = [
        'max'         => $maxRedirects,
        'on_redirect' => function(\GuzzleHttp\Psr7\Request $request, Response $response, Uri $effectiveUri) {
        $statusCode = $response->getStatusCode();
        $originUri = $request->getUri()->__toString();
        $destUri = $effectiveUri->__toString();
        $redirect = [];
        $redirect[] = [
            'origin'      => $originUri,
            'destination' => $destUri,
            'status_code' => $statusCode,
        ];
        $this->json->mergeRow($this->filename, 'redirects', $redirect, true);
      },
    ];

    return $redirectOptions;

}//end getRedirectOptions()


}//end class
