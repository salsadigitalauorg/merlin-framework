<?php

namespace Merlin\Parser;

use Merlin\Parser\XmlConfig;
use Merlin\Parser\WebConfig;

/**
 * Configuration parser
 */

class Config extends ConfigBase
{


    /**
     * Returns subclassed configuration.
     */
    public function getConfig()
    {
        switch ($this->get('parser')) {
        case 'xml':
            return new XmlConfig($this->source);
        break;

        case 'url':
        default:
            return new WebConfig($this->source);
        break;
        }

    }//end getConfig()


}//end class
