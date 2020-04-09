<?php

namespace Merlin\Crawler\Group;

use Psr\Http\Message\ResponseInterface;

/**
 * Allows URLs to be grouped by a path part.
 *
 * @example
 *   id: group-by-path
 *   type: element
 *   options:
 *       pattern: /path/* # Supports wildcards
 */
class Path extends GroupBase
{


    /**
     * {@inheritdoc}
     */
    public function match($url, ResponseInterface $response) : bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        $pattern = $this->getOption('pattern');
        $pattern = is_array($pattern) ? $pattern : [$pattern];

        foreach ($pattern as $p) {
            if (fnmatch($p, $path)) {
                return TRUE;
            }
        }

        return FALSE;

    }//end match()


}//end class
