<?php

/**
 * Provides a hash map based on the hash of content and url(s).
 */

namespace Migrate\Fetcher;

use Migrate\Parser\ParserInterface;
use RollingCurl\Request;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Maintains a map of content hashes to URLs.
 * Class ContentHash
 * @package Migrate\Output
 */
class ContentHash
{

  /**
   * @var array
   */
  protected $map;

  /**
   * @var string
   */
  protected $selector;

  /**
   * @var array
   */
  protected $exclude;


  public function __construct(ParserInterface $config=null)
  {

    $this->map = [];
    $this->selector = "//body";
    $this->exclude = [
        '//script',
        '//comment()',
        '//style',
        '//input',
        '//head',
    ];

    if ($config instanceof ParserInterface) {
      $selector = ($config->get('url_options')['hash_selector'] ?? null);
      $exclude  = ($config->get('url_options')['hash_exclude_nodes'] ?? null);

      if (!empty($selector)) {
        $this->selector = $selector;
      }

      if (is_array($exclude) && !empty($exclude) && !empty($exclude[0])) {
        $this->exclude = $exclude;
      }
    }

  }//end __construct()


  /**
   * Stores the hash of the content of the request in the map.  Returns true
   * if the content was a duplicate or false if it is the first time seen.
   *
   * @param string  $url
   * @param string  $content
   * @param bool    $skipEmpty
   *
   * @return bool|void
   */
  public function put($url, $content, $skipEmpty=true) {
    if ($skipEmpty && empty(trim($content))) {
      return;
    }

    $hash = $this->hash($content);

    if (key_exists($hash, $this->map)) {
      if (!in_array($url, $this->map[$hash])) {
        $this->map[$hash][] = $url;
        return true;
      }
    } else {
      $this->map[$hash][] = $url;
      return false;
    }

  }//end put()


  /**
   * Generates a hash from the content string.
   * @param $content
   *
   * @return string
   */
  private function hash($content) {

    $crawler = new Crawler($content);

    // Remove some suspects that may be dynamic.
    foreach ($this->exclude as $xpathString) {
        $crawler->filterXPath($xpathString)->each(
            function (Crawler $c) {
              $node = $c->getNode(0);
              $node->parentNode->removeChild($node);
            }
        );
    }

    $n = $crawler->filterXPath($this->selector);
    if ($n->count() > 0) {
      $h = $n->html();
    } else {
      $h = $crawler->html();
    }

    return sha1($h);

  }//end hash()


  /**
   * Returns true if the hash exists in the map.
   * @param $hash
   *
   * @return bool
   */
  protected function exists($hash) {
    return key_exists($hash, $this->map);

  }//end exists()


  /**
   * Returns the hash of the url if it is in the map.
   * @param $url
   *
   * @return string|null
   */
  public function getUrl($url) {
    foreach ($this->map as $hash => $urls) {
      if (in_array(trim($url), $urls)) {
        return $hash;
      }
    }

    return null;

  }//end getUrl()


  public function getDuplicates() {
    $duplicates = [];
    foreach ($this->map as $hash => $urls) {
      if (count($urls) > 1) {
        $duplicates[] = [
            'hash' => $hash,
            'urls' => $urls,
        ];
      }
    }

    return $duplicates;

  }//end getDuplicates()


}//end class
