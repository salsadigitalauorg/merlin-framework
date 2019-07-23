<?php

namespace Migrate\Output;

/**
 * Manage the output in json.
 */
class Json extends OutputBase
{

    /**
     * The extension to use when writing the file.
     *
     * @var string
     */
    protected $ext = 'json';


    public function serialize($row)
    {
        return json_encode($row, (JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }


    protected function afterOpen($resource)
    {
        fwrite($resource, '[');
    }

    protected function beforeClose($resource)
    {
        // $stat = fstat($resource);
        // ftruncate($resource, $stat['size'] - 1);
        fwrite($resource, ']');
    }

    protected function afterRow($resource)
    {

        fwrite($resource, ',');

    }

    /**
     * {@inheritdoc}
     */
    public function toString(array $data=[])
    {
        return json_encode($data, (JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    }//end toString()


}//end class
