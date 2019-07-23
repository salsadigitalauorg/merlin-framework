<?php

namespace Migrate\Utility;

trait HashTrait
{

    /**
     * Provide a repeatable hash of an object.
     *
     * @return string
     */
    public function hash($mixed): string
    {
        $hash = json_encode($mixed);
        return md5($hash);
    }

}
