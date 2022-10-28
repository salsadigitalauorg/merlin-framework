<?php

namespace Merlin\Crawler\Group;

use Psr\Http\Message\ResponseInterface;

/**
 * Allows URLs to be grouped by a query part.
 *
 * @example
 *   id: group-by-path
 *   type: query
 *   options:
 *       pattern: something=blah* # Supports wildcards
 */
class Query extends GroupBase
{


  /**
   * {@inheritdoc}
   */
  public function match($url, ResponseInterface $response) : bool
  {
    $query = parse_url($url, PHP_URL_QUERY);

    $pattern = $this->getOption('pattern');
    $pattern = is_array($pattern) ? $pattern : [$pattern];

    foreach ($pattern as $p) {
      if (fnmatch($p, $query)) {
        return TRUE;
      }
    }

    return FALSE;

  }//end match()


}//end class
