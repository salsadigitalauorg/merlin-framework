<?php

namespace Merlin\Crawler\Group;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Allows URLs to be grouped by an element value.
 *
 * @example:
 *   id: group-by-node-value
 *   type: value
 *   options:
 *       selector: h1 # DOM or Xpath
 *       value: 'My Value' # Expected value of the node
 *       attribute: 'alt' # Optional attribute to choose (if not specified Node text will be compared).
 */
class Value extends GroupBase
{


    /**
     * {@inheritdoc}
     */
    public function match($url, ResponseInterface $response): bool
    {
        $dom = new Crawler($response->getBody()->__toString(), $url);

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

        $element_value = $this->getOption('attribute') ? $element->attr($this->getOption('attribute')) : $element->text();

        $matches = [];
        preg_match($this->getOption('pattern'), $element_value, $matches);

        return !empty($matches);

    }//end match()


}//end class
