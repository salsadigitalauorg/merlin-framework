<?php

namespace Merlin\Type;

use Merlin\Parser\ParserInterface;
use Ramsey\Uuid\Uuid as UuidLib;

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

        $options = isset($this->config['options']) ? $this->config['options'] : [];

        $uri = $this->crawler->getUri();
        $parts = parse_url($uri);

        $includeQuery = false;
        $includeFrag = false;

        $mainConfig = $this->output->getConfig();
        $entity_type = "";
        if ($mainConfig instanceof ParserInterface) {
          $includeQuery = ($mainConfig->get('url_options')['include_query'] ?? false);
          $includeFrag = ($mainConfig->get('url_options')['include_fragment'] ?? false);
          $entity_type = $mainConfig->get('entity_type');

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

        // Decode the url, useful if you want unicode chars in output urls.
        if (($options['urldecode'] ?? false)) {
          $url = urldecode($url);
        }

        // Return uuidv3 of alias instead of actual alias.
        if (($options['return_uuid'] ?? false)) {
          $uuid_url = UuidLib::uuid3(UuidLib::NAMESPACE_DNS, strtolower($url))->toString();
          $this->addValueToRow($uuid_url);
          return;
        }

        // Truncate the url to a certain length and keep track of truncated urls.
        $truncate = ($options['truncate'] ?? false);
        if ($truncate !== false) {
          $url_len = strlen(utf8_decode($url));
          $max_len = $truncate;
          $url_truncated = null;

          if ($url_len > $max_len) {
            $url_original = $url;
            $url_trimmed = mb_strimwidth($url,0, $max_len,'','utf-8');
            $GLOBALS['_merlin_truncated_url_track'][$url_trimmed] += 1;

            // TODO: Better place than $GLOBALS to store this...
            $tag = "-".$GLOBALS['_merlin_truncated_url_track'][$url_trimmed];
            $url = $url_trimmed.$tag;
            $data = [
                'url'           => $url_original,
                'url_truncated' => $url,
            ];

            $this->output->addRow("{$entity_type}-truncated-urls", (object) $data);
          }
        }//end if

        // Processors are run after the option modifiers.
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
