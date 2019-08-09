<?php

namespace Migrate\Output;

use Symfony\Component\Console\Output\OutputInterface as ConsoleOutput;
use Symfony\Component\Console\Style\OutputStyle;
use Migrate\Parser\ParserInterface;

/**
 * The output base class.
 */
abstract class OutputBase implements OutputInterface
{

    /**
     * @var Symfony\Component\Console\Output\OutputInterface;
     */
    protected $io;

    /**
     * An array of outputs from the runner.
     *
     * @var array
     */
    protected $data;

    /**
     * The configuration object.
     *
     * @var Migrate\Parser\ParserInterface
     */
    protected $config;


    /**
     * Build an instance of the output object.
     */
    public function __construct(ConsoleOutput $output, ParserInterface $config)
    {
        $this->io     = $output;
        $this->config = $config;

    }//end __construct()


    /**
     * {@inheritdoc}
     */
    public function addRow($entity_type, \stdClass $row)
    {
        if (empty($this->data[$entity_type])) {
            $this->data[$entity_type] = [];
        }

        $this->data[$entity_type][] = $row;
        return $this;

    }//end addRow()


    /**
     * Merge a row into the result object.
     *
     * This will attempt to keep a flattened representation of the data
     * that is being maintained by the object. It will expect a key and
     * an entity type and will then continue to merge any newly given
     * data to the array.
     *
     * @param string $type
     *   The type to be used for the data. This will be the file name when writeFiles is called.
     * @param string $key
     *   The key to maintain in the files representation.
     * @param array  $data
     *   The data to add to the row.
     *
     * @return $this
     *   The instance of the output object.
     */
    public function mergeRow($type, $key='data', array $data=[], $recursive=false)
    {
        if (empty($this->data[$type])) {
            $this->data[$type] = [];
        }

        if (empty($this->data[$type][$key])) {
            $this->data[$type][$key] = [];
        }

        $this->data[$type][$key] = $recursive ? array_merge_recursive($this->data[$type][$key], $data) : ($this->data[$type][$key] + $data);

        return $this;

    }//end mergeRow()


    /**
     * Validate the output object.
     *
     * Remove duplicates from the result sets.
     */
    public function validate(&$data, $file)
    {
        $uuids = [];

        foreach ($data as $key => $row) {
            // Hash of null is c87ee674-4ddc-3efe-a74e-dfe25da5d7b3.
            if (!is_array($row) && isset($row->uuid) && $row->uuid == "c87ee674-4ddc-3efe-a74e-dfe25da5d7b3") {
                unset($data[$key]);
                continue;
            } else {
                foreach ($row as $k => $item) {
                    if (isset($item->uuid) && $item->uuid == 'c87ee674-4ddc-3efe-a74e-dfe25da5d7b3') {
                        unset($data[$key][$k]);
                        continue;
                    }
                }
            }

            if (is_array($row)) {
                foreach ($row as $k => $item) {
                    $hash = md5(json_encode($item));
                    if (in_array($hash, $uuids)) {
                        unset($data[$key][$k]);
                    }

                    $uuids[] = $hash;
                }
            } else {
                $hash = md5(json_encode($row));
                if (in_array($hash, $uuids)) {
                    unset($data[$key]);
                }

                $uuids[] = $hash;
            }
        }//end foreach

        return $data;

    }//end validate()


    /**
     * {@inheritdoc}
     */
    public function writeFiles($dir=null, $quiet=false)
    {
        $ext    = $this->ext;
        $append = !empty($this->config->get('runner')['append']);

        foreach ($this->data as $file => $data) {
            $filename = $dir ? "$dir/$file.$ext" : "$file.$ext";

            if ($append && file_exists($filename)) {
                $file      = file_get_contents($filename);
                $file_data = json_decode($file, true);
                $data      = array_merge_recursive($file_data, $data);
            }

            $data = $this->validate($data, $file);
            if (file_exists($filename) && !is_writable($filename)) {
                $new_filename = $dir ? "$dir/$file." . time() . ".$ext" : "$file." . time() . ".$ext";
                $this->io->writeln("<comment>Permission denied saving to {$filename}. Using {$new_filename} instead.</comment>");
                $filename = $new_filename;
            }

            file_put_contents($filename, $this->toString($data));

            if ($quiet) {
                $this->io->setVerbosity(OutputStyle::VERBOSITY_NORMAL);
                $this->io->writeln($filename);
            } else {
                $this->io->writeln("Generating $filename <info>Done!</info>");
            }
        }

    }//end writeFiles()


    /**
     * Accessor for the config object.
     *
     * @return Migrate\Parser\ParserInterface
     */
    public function getConfig()
    {
        return $this->config;

    }//end getConfig()


}//end class
