<?php

namespace Merlin\Output;

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


    /**
     * {@inheritdoc}
     */
    public function toString(array $data=[])
    {
        return json_encode($data, (JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    }//end toString()


}//end class
