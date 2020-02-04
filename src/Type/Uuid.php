<?php

namespace Merlin\Type;

use Ramsey\Uuid\Uuid as UuidLib;

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
                $uuid = UuidLib::uuid3(UuidLib::NAMESPACE_DNS, $value)->toString();
            }
        }

        if (!$uuid) {
            $path = $this->crawler->getUri();
            $uuid = UuidLib::uuid3(UuidLib::NAMESPACE_DNS, $path)
            ->toString();
        }

        $this->row->{$field} = $uuid;

    }//end process()


}//end class
