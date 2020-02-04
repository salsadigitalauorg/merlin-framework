<?php

namespace Merlin\Type;

use Merlin\Parser\ParserInterface;

/**
 * Generate an alias for the given row.
 *
 * @example:
 *   field: alias
 *   type: alias
 */
class Alias extends TypeBase implements TypeInterface
{


    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $uri = $this->crawler->getUri();
        $parts = parse_url($uri);

        $includeQuery = false;
        $includeFrag = false;

        $mainConfig = $this->output->getConfig();
        if ($mainConfig instanceof ParserInterface) {
          $includeQuery = ($mainConfig->get('url_options')['include_query'] ?? false);
          $includeFrag = ($mainConfig->get('url_options')['include_fragment'] ?? false);

          // Check for specific url overrides for the query and fragment options.
          $overrideUrls = ($mainConfig->get('url_options')['urls'] ?? null);
          if (is_array($overrideUrls)) {
            foreach ($overrideUrls as $idx => $overrideUrl) {
              if ($overrideUrl['url'] === self::getDomainlessUrl($uri)) {
                $includeQuery = ($overrideUrl['include_query'] ?? false);
                $includeFrag = ($overrideUrl['include_fragment'] ?? false);
              }
            }
          }
        }

        // Throw away domain, scheme etc and rebuild according to config options.
        $path  = isset($parts['path']) ? $parts['path'] : null;
        $query = isset($parts['query']) && $includeQuery ? "?".$parts['query'] : null;
        $frag  = isset($parts['fragment']) && $includeFrag ? "#".$parts['fragment'] : null;

        $url = "{$path}{$query}{$frag}";
        $url = $this->processValue($url);
        $this->addValueToRow($url);

    }//end process()


    /**
     * Returns the path, query and fragment component of the url.
     * @param $uri
     *
     * @return string
     */
    public static function getDomainlessUrl($uri) {
      $parts = parse_url($uri);
      $path  = isset($parts['path']) ? $parts['path'] : null;
      $query  = isset($parts['query']) ? "?".$parts['query'] : null;
      $frag  = isset($parts['fragment']) ? "#".$parts['fragment'] : null;
      $u = "{$path}{$query}{$frag}";
      return $u;

    }//end getDomainlessUrl()


}//end class
