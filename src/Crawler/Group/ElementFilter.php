<?php

namespace Merlin\Crawler\Group;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Allows regex element filters to extract to separate files.
 *
 * @example
 *   id: group-by-node-type
 *   type: element_filter
 *   options:
 *      selector: .node # DOM or Xpath
 *      pattern: /node-\w+/
 *      filter_attr: class
 */
class ElementFilter extends GroupBase
{

  protected $filter_type;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $config=[])
  {
    parent::__construct($config);
    $this->filter_type = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() : string
  {
    $id = parent::getId();

    if ($this->filter_type) {
      $id .= "-{$this->filter_type}";
    }

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function match($url, ResponseInterface $response) : bool
  {
    $dom = new Crawler($response->getBody()->__toString(), $url);
    $filter_attr = $this->getOption('filter_attr') ?: 'class';
    $pattern = $this->getOption('pattern');

    if (empty($this->getOption('selector')) || empty($pattern)) {
      return FALSE;
    }

    try {
      $element = $dom->evaluate($this->getOption('selector'));
    } catch (\Exception $error) {
      $element = [];
    }

    if (!is_callable([$element, 'count']) || $element->count() === 0) {
      try {
        $element = $dom->filter($this->getOption('selector'));
      } catch (\Exception $error) {
        return FALSE;
      }
    }

    if ($element->count() === 0) {
      return FALSE;
    }

     $types = $element->each(function(Crawler $node) use ($filter_attr, $pattern) {
      preg_match($pattern, $node->attr($filter_attr), $matches);
      return reset($matches);
    });

    $this->filter_type = reset($types);

    return TRUE;
  }


}
