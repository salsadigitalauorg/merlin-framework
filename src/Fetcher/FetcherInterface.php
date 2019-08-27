<?php

/**
 * FetcherInterface, see the default Fetcher classes such as Fetchers/FetcherCurl
 * to get an idea of what your methods need to do to work as a Fetcher.
 *
 * A Fetcher class should extend Fetcher/FetcherBase and implement this interface.
 */

namespace Migrate\Fetcher;

/**
 * Interface FetcherInterface
 * @package Migrate\Fetcher
 */
interface FetcherInterface
{


  /**
   * Initialise your fetcher class in this function.  It is a substitute for __construct().
   * @return mixed
   */
  public function init();


  /**
   * Provide a method to add a URL to your fetchers internal queue.
   * @param string|null $url
   *
   * @return mixed
   */
  public function addUrl(?string $url);


  /**
   * Provide a *blocking* method that processes the urls in the queue.
   * @return mixed
   */
  public function start();


  /**
   * This function is called after all processing is complete.  If you need to do
   * some extra processing when everything is finished, you can add that here.
   * @return mixed
   */
  public function onComplete();


}//end interface
