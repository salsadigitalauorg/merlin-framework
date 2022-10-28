<?php

namespace Merlin\Crawler\Group;

use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ResponseInterface;

/**
 * Allows URLs to be grouped by a meta tag.
 *
 * @example
 *   id: group-by-node-type
 *   type: meta
 *   options:
 *       attr: name
 *        value: 'contenttype'
 *       content:
 *          - 'Landing page'
 *          - 'Standard page'
 */
class Meta extends GroupBase
{


  /**
   * {@inheritdoc}
   */
  public function match($url, ResponseInterface $response) : bool
  {
    $attr = $this->getOption('attr');
    $value = $this->getOption('value');
    $content = $this->getOption('content');
    if (empty($attr) || empty($value) || empty($content)) {
      return FALSE;
    }

    if (!is_array($content)) {
      $content = [$content];
    }

    $dom = new Crawler($response->getBody()->__toString(), $url);
    $metatags = $dom->filter('meta');
    $meta = null;

    $metatags->each(
        function(Crawler $node) use ($attr, $value, &$meta) {
          if ($node->attr($attr) === $value) {
            $meta = $node;
          }
        }
    );

    try {
      if ($meta) {
        $meta_content = $meta->attr('content');
        return in_array($meta_content, $content);
      }
    } catch (\Exception $error) {
      return FALSE;
    }

    return FALSE;

  }//end match()


}//end class
