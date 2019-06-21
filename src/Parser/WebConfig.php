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


}//end class
