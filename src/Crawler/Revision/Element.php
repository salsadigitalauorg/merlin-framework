<?php

namespace Migrate\Crawler\Revision;

use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ResponseInterface;

/**
 * Allows URLs to be grouped by an element appearing on the page.
 *
 * @example
 *   id: group-by-node-existence
 *   type: element
 *   options:
 *       selector: h1 # DOM or Xpath
 */
class Element extends RevisionBase
{


    /**
     * {@inheritdoc}
     */
    public function match($url, ResponseInterface $response) : bool
    {
        $dom = new Crawler($response->getBody()->__toString(), $url);

        if (empty($this->getOption('selector'))) {
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

        return $element->count() > 0;

    }//end match()


}//end class
