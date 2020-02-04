<?php

namespace Merlin\Output;

/**
 * The output interface.
 */
interface OutputInterface
{


    /**
     * Write the contents of the output object to files.
     */
    public function writeFiles();


    /**
     * Validate the data for the file.
     */
    public function validate(&$data, $file);


    /**
     * Add a row to the output object.
     *
     * @param string   $entity_type
     *   The entity type to add this too.
     * @param stdClass $row
     *   The row to add.
     *
     * @return this
     */
    public function addRow($entity_type, \stdClass $row);


    /**
     * Convert a data array to string.
     *
     * This is called when the comand is attempting to write
     * the files to disk. Each output object will understand
     * how to generate a string from the data.
     *
     * @return string
     *   The output string.
     */
    public function toString(array $data=[]);


}//end interface
