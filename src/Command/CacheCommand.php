<?php

/**
 * Provides some simple cache management for CLI use.
 */

namespace Migrate\Command;


use Migrate\Fetcher\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CacheCommand
 * @package Migrate\Command
 */
class CacheCommand extends Command
{

  /** @var string  */
  protected static $defaultName = 'cache';

  /** @var string  */
  private $cacheRoot;

  /** @var SymfonyStyle */
  private $io;


  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setDescription('Cache tools for migration data')
      ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to the configuration file')
      ->addOption('purge-url', null, (InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL), 'Purge url(s) from cache')
      ->addOption('purge-domain', null, InputOption::VALUE_OPTIONAL, 'Purge entire cache for domain')
      ->addOption('cache-dir', null, InputOption::VALUE_OPTIONAL, 'Specify cache dir (overrides config file)')
      ->addOption('domain', null, InputOption::VALUE_OPTIONAL, 'Specify target domain to use')
      ->addOption('stats', null, InputOption::VALUE_NONE, 'Print the cache statistics');

  }//end configure()


  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $this->io = new SymfonyStyle($input, $output);
    $this->io->title('Migration framework - Cache Tools');

    // Set default cache dir.
    $this->cacheRoot = "/tmp/merlin_cache";

    $configDomain = null;

    $configFile = $input->getOption('config');
    if ($configFile && is_file($configFile)) {
      $conf = \Spyc::YAMLLoad($configFile);

      // We might have a crawl config or a fetcher config.
      // Unfortunately, atm these have two different cache enabled directives, so we need to check both.
      $crawlerCache = ($conf['options']['cache_dir'] ?? null);
      $fetcherCache = ($conf['fetch_options']['cache_dir'] ?? null);

      if ($crawlerCache) {
        $this->cacheRoot = $crawlerCache;
      } else if ($fetcherCache) {
        $this->cacheRoot = $fetcherCache;
      }

      $configDomain = ($conf['domain'] ?? null);
    }

    if ($input->getOption('domain')) {
      $configDomain = $input->getOption('domain');
    }

    if ($input->getOption('cache-dir')) {
      $this->cacheRoot = $input->getOption('cache-dir');
    }

    if (!is_dir($this->cacheRoot)) {
      $this->io->error("Specified root dir does not exist: {$this->cacheRoot}");
      exit(1);
    }

    $printStats  = ($input->getOption('stats') ?? false);
    $purgeUrls   = $input->getOption('purge-url');
    $purgeDomain = null;
    if (!empty($input->getOption('purge-domain'))) {
      $purgeDomain = ($input->getOption('purge-domain') ?? $configDomain);
    }

    switch (true) {
      case $purgeUrls:
        $this->purgeUrls($configDomain, $purgeUrls);
        break;
      case $purgeDomain:
        $this->purgeDomain($purgeDomain);
        break;
      case $printStats:
        $this->printStats($configDomain);
        break;
      default:
        $this->io->writeln("Nothing to do... bye!");
        exit(0);
        break;
    }

}//end execute()


  /**
   * @param $domain
   *
   * @return \Migrate\Fetcher\Cache
   * @throws \Exception
   */
  private function initCache($domain) {
    $cache = new Cache($domain, $this->cacheRoot);
    $this->io->section("Cache settings:");
    $this->io->writeln("Domain:      {$domain}");
    $this->io->writeln("Cache root:  {$this->cacheRoot}");
    $this->io->writeln("Cache dir:   {$cache->getPath()}");

    return $cache;

  }//end initCache()


  /**
   * Purges a list of urls from given domain cache bucket.
   *
   * @param $domain
   * @param $urls
   *
   * @throws \Exception
   */
  private function purgeUrls($domain, $urls)
  {

    if (empty($urls[0])) {
      $this->io->error("Specify at least one URL!");
      exit(1);
    }

    foreach ($urls as $url) {
      $url = trim($url);
      if (!empty($url)) {
        // If no domain, we check url to fetch it out of there.
        // This may be the case if manually specifying without a config file.
        if (empty($domain)) {
          $p = parse_url($url);
          $scheme = $p['scheme'];
          $host = $p['host'];
          $targetDomain = "{$scheme}://{$host}";
          if (!empty($p['port'])) {
            $targetDomain .= ":".$p['port'];
          }
        } else {
          // Assume using relative url. We str replace just in case someone uses
          // the full URL from the command line and gets confused about it.
          $url = str_replace($domain, "", $url);
          $url = "{$domain}{$url}";
          $targetDomain = $domain;
        }

        // Check we have a valid url.
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
          $this->io->warning("Invalid URL, skipping: $url");
          continue;
        }

        $cache = $this->initCache($targetDomain);
        $filename = $cache->getFilename($url);

        $this->io->writeln("Purge url:  ".$url);
        $this->io->writeln("Purge file: ".$filename);

        $exists = is_file($filename);
        if (!$exists) {
          $this->io->warning("{$filename} -- Not found in cache!");
        } else {
          $success = $cache->unlink($url);
          if ($success) {
            $this->io->success("{$filename} -- Purged!");
          } else {
            $this->io->error("{$filename} -- Purge Failed!");
          }
        }
      }//end if
    }//end foreach

  }//end purgeUrls()


  /**
   * Purges the entire cache for a given domain.
   *
   * @param $domain
   *
   * @throws \Exception
   */
  private function purgeDomain($domain) {

      if (!empty($domain)) {
        $cache = $this->initCache($domain);
        $yes = $this->io->ask("Really purge entire cache for {$domain}? [y/n]");
        if (!in_array($yes,['Y', 'y'])) {
          $this->io->writeln('Not purging, safety first! :D');
          exit(0);
        }

        $path = $cache->getPath();
        if (!is_dir($path)) {
          $this->io->warning("No cache found at: {$path}");
          exit(0);
        }

        $cache->clearCache(true);

        $success = !is_dir($path);
        if ($success) {
          $this->io->success("{$path} -- Purged!");
        } else {
          $this->io->error("{$path} -- Purge Failed!");
        }
      } else {
        // This one should never happen (!).
        $this->io->error("No domain specified!");
        exit(1);
      }//end if

  }//end purgeDomain()


  /**
   * Prints out the cache statistics.
   * @param $domain
   *
   * @throws \Exception
   */
  private function printStats($domain) {
    if (empty($domain)) {
      $this->io->error("Specify a domain to print statistics for.");
      exit(1);
    }

    $cache = $this->initCache($domain);
    $this->io->section("Cache Statistics:");
    $stats = $cache->getStats();
    if (!empty($stats)) {
      foreach ($stats as $k => $v) {
        $this->io->writeln("{$k}: {$v}");
      }
    } else {
      $this->io->error("No cache found at: {$cache->getPath()}");
    }

  }//end printStats()


}//end class
