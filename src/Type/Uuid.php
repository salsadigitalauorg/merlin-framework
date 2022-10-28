<?php

namespace Merlin\Type;

use Merlin\Utility\MerlinUuid;

/**
 * Add a unique identifier to the row.
 *
 * @example:
 *   field: uuid
 *   type: uuid
 */
class Uuid extends TypeBase implements TypeInterface
{


    /**
     * {@inheritdoc}
     */
    public function process()
    {
        extract($this->config);
        $uuid = false;

        if (isset($selector)) {
            try {
                $element = @$this->crawler->evaluate($selector);
                if (is_array($element)) {
                    $element = @$this->crawler->filter($selector);
                }
            } catch (\Exception $e) {
                // We couldn't find the selector.
            }

            $value = $this->processValue($element->text());

            if ($element && $element->count() > 0) {
                $uuid = MerlinUuid::getUuid($value);
            }
        }

        if (!$uuid) {
            $path = $this->crawler->getUri();
            $uuid = MerlinUuid::getUuid($path);
        }

        $this->row->{$field} = $uuid;

    }//end process()


}//end class
