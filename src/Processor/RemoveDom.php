<?php

namespace Migrate\Processor;

use Symfony\Component\DomCrawler\Crawler;
use Migrate\Output\OutputInterface;

/**
 * Remove specific DOM elements from the markup.
 */
class RemoveDom extends ProcessorOutputBase implements ProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $config, Crawler $crawler, OutputInterface $output) {
    parent::__construct($config, $crawler, $output);

    $this->selector = $config['selector'];
    $this->xpath = (bool) $config['xpath'];
  }

  /**
   * Callback to remove elements from the DOM.
   */
  public static function removeNode() {
    return function(Crawler $crawler) {
      foreach ($crawler as $node) {
        $node->parentNode->removeChild($node);
      }
    };
  }

  /**
   * Find an xpath selector to remove from the dom.
   */
  public function processXpath(Crawler $dom) {
    $dom->evaluate($this->selector)->each(self::removeNode());
    return $dom;
  }

  /**
   * Find a css selector to find and remove.
   */
  public function processDom(Crawler $dom) {
    $dom->filter($this->selector)->each(self::removeNode());
    return $dom;
  }

  /**
   * {@inheritdoc}
   */
  public function process($value) {
    $dom = new Crawler($value);
    $dom = $this->xpath ? $this->processXpath($dom) : $this->processdom($dom);
    return $dom->html();
  }

}
