<?php

namespace Migrate\Parser;

class XmlConfig extends ConfigBase
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

        if (empty($data['files'])) {
            throw new \Exception("Invalid source file: No XML files defined");
        }

        if (empty($data['entity_type'])) {
            throw new \Exception("Invalid source file: No content type found in the source file");
        }

        if (empty($data['mappings'])) {
            throw new \Exception("Invalid source file: No mappings found in the source file");
        }

        // Glob handling.
        $files = [];
        foreach ($data['files'] as $file) {
            if (!empty($file['glob'])) {
                $files = array_merge($files, $this->globResult($file['path'], $file['glob']));
            } else {
                $files[] = $file;
            }
        }

        $data['files'] = $files;

        $this->data = $data;
        $this->totals['mappings'] = count($data['mappings']);
        $this->totals['files']    = count($data['files']);
        return $this;

    }//end parse()


    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        if ($this->totals['files'] > 0) {
            $this->totals['files']--;
            return array_shift($this->data['files']);
        }

    }//end getUrl()


    /**
     * Support file globbing.
     */
    private function globResult($path, $globs)
    {
        $files = [];

        foreach ($globs as $glob) {
            if ($result = glob("{$path}/{$glob}")) {
                $files = array_merge($files, $result);
            } else {
                throw new \Exception("Invalid source file: {$path}/{$glob} is invalid.");
            }
        }

        return $files;

    }//end globResult()


}//end class
