<?php

namespace Migrate\Parser;

class WebConfig extends ConfigBase
{


    /**
     * {@inheritdoc}
     */
    protected function parse()
    {
        if (!file_exists($this->source)) {
            throw new \Exception("Invalid source file provided: Cannot locate $this->source");
        }

        $data = \Spyc::YAMLLoad($this->source);

        if (empty($data['domain'])) {
            throw new \Exception("Invalid source file: No domain found in the source file");
        }

        if (!array_key_exists('urls', $data) && !array_key_exists('urls_file', $data)) {
            throw new \Exception("Need to supply one or both of: urls, urls_file");
        }

        /*
         * If a URLs file is provided, add the URLs to the URL array
         * otherwise use the URL array.
         */

        if (!empty($data['urls_file'])) {
            $urls_file = dirname($this->source).'/'.$data['urls_file'];

            if (!file_exists($urls_file)) {
                throw new \Exception("Invalid URLs file provided: cannot locate {$data['urls_file']}");
            }

            $urls_from_file = \Spyc::YAMLLoad($urls_file);
            if (!is_array($urls_from_file['urls'])) {
                $urls_from_file['urls'] = [$urls_from_file['urls']];
            }

            $this->totals['urls_from_file'] = count($urls_from_file['urls']);

            if (isset($data['urls'])) {
                $data_urls_array = is_array($data['urls']) ? $data['urls'] : [$data['urls']];
                $this->totals['urls_from_config'] = count($data_urls_array);
                $data['urls'] = array_merge($data_urls_array, $urls_from_file['urls']);
            } else {
                $data['urls'] = $urls_from_file['urls'];
            }

            unset($data['urls_file']);
        } else {
            if (!isset($data['urls'])) {
                $this->totals['urls_from_config'] = 0;
            } else {
                $data_urls_array = is_array($data['urls']) ? $data['urls'] : [$data['urls']];
                $this->totals['urls_from_config'] = count($data_urls_array);
            }
        }//end if

        if (empty($data['urls'])) {
            throw new \Exception("Invalid source file: No URLs found in the source file");
        }

        if (empty($data['entity_type'])) {
            throw new \Exception("Invalid source file: No content type found in the source file");
        }

        if (empty($data['mappings'])) {
            throw new \Exception("Invalid source file: No mappings found in the source file");
        }

        $this->data = $data;
        $this->totals['mappings'] = count($data['mappings']);
        $this->totals['urls']     = count($data['urls']);
        return $this;

    }//end parse()


    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        if ($this->totals['urls'] > 0) {
            $this->totals['urls']--;
            return $this->data['domain'].array_shift($this->data['urls']);
        }

    }//end getUrl()


    /**
     * Forces cache disable.
     */
    public function disableCache() {
        $this->data['fetch_options']['cache_enabled'] = false;

    }//end disableCache()


}//end class
