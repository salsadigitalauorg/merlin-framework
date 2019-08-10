<?php

namespace Migrate\Output;

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
   * @param \RollingCurl\Request $request
   * @param bool                 $skipEmpty
   *
   * @return bool|void
   */
  public function put(Request $request, $skipEmpty=true) {
    // $urlHash = $this->hash($url);
    $content = $request->getResponseText();
    if ($skipEmpty && empty(trim($content))) {
      return;
    }

    $hash = $this->hash($content);
    $url = $request->getUrl();

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
   * Generates a hash from the content string.
   * // TODO CAN PROBABLY BE REMOVED USING SYMFONY CRAWLER INSTEAD.
   * @param $content
   *
   * @return string
   */
  private function hash_old($content) {

    $prevErrors = libxml_use_internal_errors(true);
    $prevEntities = libxml_disable_entity_loader(true);
    $dom = new \DOMDocument();
    $dom->strictErrorChecking = false;
    $dom->loadHTML($content);
    libxml_clear_errors();
    libxml_use_internal_errors($prevErrors);
    libxml_disable_entity_loader(($prevEntities));

    // Remove some suspects that may be dynamic.
    foreach ($this->exclude as $xpathString) {
      $this->removeNodes($dom, $xpathString);
    }

    $xpath = new \DOMXPath($dom);
    $nl = $xpath->query($this->selector);
    if ($nl !== false && $nl->count() === 1) {
      $node = $nl->item(0);
      $html = $node->ownerDocument->saveHTML($node);
    } else {
      $html = $dom->saveHTML();
    }

    return sha1($html);

  }//end hash_old()


  /**
   * Remove nodes from the DOM that match the xpath string.
   * // TODO CAN PROBABLY BE REMOVED USING SYMFONY CRAWLER INSTEAD.
   * @param $dom
   * @param $xpathString
   */
  private function removeNodes($dom, $xpathString) {
    $xpath = new \DOMXPath($dom);
    $nl = $xpath->query($xpathString);

    if ($nl === false) {
      return;
    }

    for ($i = $nl->length; --$i >= 0;) {
      $el = $nl->item($i);
      $el->parentNode->removeChild($el);
    }

  }//end removeNodes()


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
