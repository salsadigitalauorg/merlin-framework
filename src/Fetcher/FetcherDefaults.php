<?php

/**
 * Holds the default settings used by the Fetcher classes.
 */

namespace Migrate\Fetcher;

/**
 * Class FetcherDefaults
 * @package Migrate\Fetcher
 */
class FetcherDefaults
{

  /** @var int Number of concurrent requests */
  const CONCURRENCY = 10;

  /** @var int Time between requests in milliseconds */
  const DELAY = 100;

  /** @var bool Execute JS for Fetchers that support it */
  const EXECUTE_JS = false;

  /** @var bool Follow server redirects */
  const ALLOW_REDIRECTS = true;

  /** @var int Connect timeout */
  const TIMEOUT_CONNECT = 10;

  /** @var int Read timeout */
  const TIMEOUT_READ = 10;

  /** @var int Overall request timeout */
  const TIMEOUT = 30;

  /** @var string User agent to use */
  const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36";

}