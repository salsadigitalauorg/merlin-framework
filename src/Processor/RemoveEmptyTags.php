<?php

namespace Merlin\Processor;

/**
 * Remove empty tags from the row.
 *
 * This is a greedy match on empty tags and will match any space character in
 * a tag and will remove them from the row.
 *
 * @usage:
 *   remove_empty_tags: { }
 */
class RemoveEmptyTags implements ProcessorInterface
{


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        return preg_replace('#<([^ >]+)[^>]*>[[:space:]]*</\1>#', '', $value);

    }//end process()


}//end class
