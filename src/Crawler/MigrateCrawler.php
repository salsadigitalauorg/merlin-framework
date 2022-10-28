<?php

namespace Merlin\Crawler;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Merlin\Fetcher\Cache;
use Spatie\Crawler\Crawler;
use Generator;
use Spatie\Crawler\LinkAdder;

class MigrateCrawler extends Crawler
{


  /**
   * Overwritten getCrawlRequests generator function that
   * facilitates caching of the content for crawling.
   * @return \Generator
   */
  protected function getCrawlRequests(): Generator
  {
    while ($crawlUrl = $this->crawlQueue->getFirstPendingUrl()) {
      foreach ($this->getCrawlObservers() as $observer) {
        if ($observer instanceof MigrateCrawlObserver) {
          $cache = $observer->getCache();
          $url = $crawlUrl->url->__toString();

          if ($cache instanceof Cache) {
            if ($cacheJson = $cache->get($url)) {
              $cacheData = json_decode($cacheJson, true);

              if (is_array($cacheData) && key_exists('contents', $cacheData) && !empty($cacheData['contents'])) {
                $contents = $cacheData['contents'];
                $foundOnUrl = $cacheData['foundOnUrl'];
                $foundOnUrl = new \GuzzleHttp\Psr7\Uri($foundOnUrl);
                $redirect = ($cacheData['redirect'] ?? []);

                // Only add non-redirected or internal redirected links to queue.
                if ((!empty($redirect) && $redirect['redirect'] && !$redirect['is_external'])
                  || empty($redirect['redirect'])
                ) {
                  $linkAdder = new LinkAdder($this);
                  $linkAdder->addFromHtml($contents, $crawlUrl->url);
                }

                $this->crawlQueue->markAsProcessed($crawlUrl);

                $fakeResponse = new Response(200, [], $contents);
                $observer->crawled($crawlUrl->url, $fakeResponse, $foundOnUrl, true);

                continue 2;
              }//end if
            }//end if
          }//end if
        }//end if
      }//end foreach

      if (!$this->crawlProfile->shouldCrawl($crawlUrl->url)) {
        $this->crawlQueue->markAsProcessed($crawlUrl);
        continue;
      }

      if ($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl)) {
        continue;
      }

      foreach ($this->crawlObservers as $crawlObserver) {
        $crawlObserver->willCrawl($crawlUrl->url);
      }

      $this->crawlQueue->markAsProcessed($crawlUrl);

      yield $crawlUrl->getId() => new Request('GET', $crawlUrl->url);
    }//end while

  }//end getCrawlRequests()


}//end class
