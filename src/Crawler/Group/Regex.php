<?php


namespace Merlin\Crawler\Group;

use Psr\Http\Message\ResponseInterface;

/**
 * Allows URLs to be grouped by a regex.
 *
 * @example
 *   id: group-by-path
 *   type: query
 *   options:
 *       pattern: (.*?)\/Home(.*?)
 */
class Regex extends GroupBase
{


  /**
   * {@inheritdoc}
   */
  public function match($url, ResponseInterface $response) : bool
  {

    $pattern = $this->getOption('pattern');
    $pattern = is_array($pattern) ? $pattern : [$pattern];

    foreach ($pattern as $p) {
      if (preg_match($p, $url) == 1) {
        return TRUE;
      }
    }

    return FALSE;

  }//end match()


}//end class
