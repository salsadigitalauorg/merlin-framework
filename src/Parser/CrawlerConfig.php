<?php

namespace Migrate\Parser;

use Migrate\Parser\XmlConfig;
use Migrate\Parser\WebConfig;

/**
 * Configuration parser
 */

class CrawlerConfig extends ConfigBase
{

    /**
     * {@inheritdoc}
     */
    protected function parse()
    {

        $defaults = [
          'follow_redirects' => true,   # Allow internal redirects.
          'ignore_robotstxt' => false,  # Ignore robots.txt rules.
          'concurrency' => 5,           # Crawler concurrency.
          'rewrite_domain' => true,    # Standardise base domain (e.g protocol or www/non-www variation).
          'delay' => 100,               # Delay between URL retrieval (ms).
          'exclude' => []               # Regex options for crawl exclusion.
        ];

        if (!file_exists($this->source)) {
            throw new \Exception("Invalid source file provided: Cannot locate $this->source");
        }

        $data = \Spyc::YAMLLoad($this->source);

        if (empty($data['domain'])) {
            throw new \Exception("Invalid source file: No domain found in the source file");
        }

        if (empty($data['options'])) {
          // Merged with defaults.
          $data['options'] = [];
        }

        $this->data = $data;

        // Merge with defaults.
        $this->data['options'] = array_merge($defaults, $this->data['options']);
        return $this;

    }//end parse()

    /**
     * Returns subclassed configuration.
     */
    public function getConfig()
    {
        return $this->data;
    }//end getConfig()


}//end class