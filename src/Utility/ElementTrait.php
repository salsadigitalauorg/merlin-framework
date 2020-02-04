<?php

namespace Merlin\Utility;

use Symfony\Component\DomCrawler\Crawler;

trait ElementTrait
{


    /**
     * Ensure that the given variable is a valid element.
     *
     * @return bool
     */
    public function isValidElement($element)
    {
        if (!is_callable([$element, 'count'])) {
            return false;
        }

        if ($element->count() === 0) {
            return false;
        }

        return true;

    }//end isValidElement()


}
