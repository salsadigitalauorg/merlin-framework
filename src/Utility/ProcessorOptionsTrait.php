<?php
/**
 * The options trait for processors.
 */

namespace Migrate\Utility;

/**
 * Allow processors to access options in a repeatable way.
 */
trait ProcessorOptionsTrait
{


    /**
     * Provide a default set of options.
     *
     * @param bool $xpath
     *   If we should use different options for xpath.
     *
     * @return array
     */
    public function options($xpath=false)
    {
        return [];

    }//end options()


    /**
     * Get a specific option value.
     *
     * @param string $key
     *   The option key.
     *
     * @return mixed
     *   The option value.
     */
    public function getOption($key, $xpath=false)
    {
        $options = !empty($this->config['options']) ? $this->config['options'] : [];
        $options = array_merge($this->options($xpath), $options);
        return array_key_exists($key, $options) ? $options[$key] : false;

    }//end getOption()


}
