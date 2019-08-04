<?php

namespace Migrate\Type;

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
        $parts = parse_url($this->crawler->getUri());

        // Throw away domain, scheme etc.
        $path  = isset($parts['path']) ? $parts['path'] : null;
        $query = isset($parts['query']) ? "?".$parts['query'] : null;
        $frag  = isset($parts['fragment']) ? "#".$parts['fragment'] : null;

        $url = "{$path}{$query}{$frag}";

        $this->addValueToRow($url);

    }//end process()


}//end class
