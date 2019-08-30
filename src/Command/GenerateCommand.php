<?php

namespace Migrate\Command;

use Migrate\Output\ContentHash;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Migrate\Parser\Config;
use RollingCurl\RollingCurl;
use Migrate\Parser\ParserInterface;
use Migrate\Output\Json;
use Migrate\Output\OutputInterface as MigrateOutputInterface;
use RollingCurl\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Style\SymfonyStyle;
use Migrate\Exception\ElementNotFoundException;
use Migrate\Exception\ValidationException;

class GenerateCommand extends Command
{

    /**
     * Set the default name for the command.
     *
     * @var string
     */
    protected static $defaultName = 'generate';

    /**
     * Configuration associated with a run.
     *
     * @var mixed
     */
    protected $config;


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Build migration datasets from configuration objects')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to the configuration file')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Path to the output directory', __DIR__)
            ->addOption('debug', 'd', InputOption::VALUE_REQUIRED, 'Output debug messages', false)
            ->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Number of requests to make in parallel', 10);

    }//end configure()


    /**
     * Generate a new field type instance based on the row.
     *
     * @param string                               $type
     *   The field type.
     * @param Symfony\Component\DomCrawler\Crawler $crawler
     *   The HTML object.
     * @param Migrate\Output\OutputInterface       $output
     *   The output object for the command.
     * @param \stdClass                            $row
     *   The row object.
     * @param array                                $config
     *   The field configuration.
     *
     * @return Migrate\Type\FieldTypeInterface
     */
    public static function TypeFactory($type='text', Crawler $crawler, MigrateOutputInterface $output, &$row, array $config=[])
    {
        $type  = str_replace('_', '', ucwords($type, '_'));
        $class = "Migrate\\Type\\".ucfirst($type);

        if (!class_exists($class)) {
            throw new \Exception("Invalid field type: $type is not defined. Field: ".json_encode($config));
        }

        return new $class($crawler, $output, $row, $config);

    }//end TypeFactory()


    /**
     * Return a request callback for RollingCurl.
     *
     * This will handle looping through each CURL response and
     * will run through each field mapping and attempt to find
     * those values in the repsonse. This will also handle
     * parsing the mapping configuration and creating additional
     * output files for related entities if any are required.
     *
     * @param Migrate\Parser\ParserInterface                   $parser
     *   The configuration object.
     * @param Migrate\Output\OutputInterface                   $output
     *   The output object.
     * @param Symfony\Component\Console\Output\OutputInterface $io
     *   The console output.
     * @param ContentHash                                      $hashes
     *   ContentHash container (null if skip_duplicates=false)
     *
     * @return function
     *   A callback for the curl request handler.
     */
    public function requestCallback(ParserInterface $parser, MigrateOutputInterface $output, OutputInterface $io, ContentHash $hashes=null, $debug=false)
    {
        return function (Request $request, RollingCurl $curl) use ($parser, $output, $io, $hashes, $debug) {
            // Handle HTTP statuses.
            switch ($request->getResponseInfo()["http_code"]) {
            case 500:
            case 404:
            case 400:
                $output->mergeRow(
                    "error-{$request->getResponseInfo()['http_code']}",
                    'urls',
                    [$request->getUrl()],
                    true
                );
                return;
            }

            $row = new \stdClass;

            $io->write('Parsing... '.$request->getUrl());

            $duplicate = false;
            if ($hashes instanceof ContentHash) {
              $duplicate = $hashes->put($request);
            }

            if ($duplicate === false) {
              while ($field = $parser->getMapping()) {
                $crawler = new Crawler($request->getResponseText(), $request->getUrl());
                $type = self::TypeFactory($field['type'], $crawler, $output, $row, $field);
                try {
                  $type->process();
                } catch (ElementNotFoundException $e) {
                  $output->mergeRow($e::FILE, $request->getUrl(), [$e->getMessage()], true);
                } catch (ValidationException $e) {
                  $output->mergeRow($e::FILE, $request->getUrl(), [$e->getMessage()], true);
                } catch (\Exception $e) {
                  $output->mergeRow('error-unhandled', $request->getUrl(), [$e->getMessage()], true);
                }
              }//end while
            }

            // Reset the parser so we have mappings back at 0.
            $parser->reset();
            $io->writeln(' <info>(Done!)</info>');

            if (!empty((array) $row)) {
                $output->addRow($parser->get('entity_type'), $row);
            }

            // Clear list of completed requests to avoid memory growth.
            $curl->clearCompleted();
        };

    }//end requestCallback()


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migration framework');
        $io->section('Preparing the configuration');

        // Confirm destination directory is writable.
        if (!is_writable($input->getOption('output'))) {
            $io->error("Error: ".$input->getOption('output')." is not writable.");
            exit(1);
        }

        $config       = new Config($input->getOption('config'));
        $this->config = $config->getConfig();
        $io->success('Done!');

        if (($config->get('url_options')['find_content_duplicates'] ?? true)) {
          $hashes = new ContentHash($config);
        } else {
          $hashes = null;
        }

        $start   = microtime(true);
        $json    = new Json($io, $config);

        $io->section('Processing requests');

        if ($this->config->get('parser') == 'xml') {
            $this->runXml($json, $io, $input);
        } else {
            $this->runWeb($json, $io, $input, $hashes);
        }

        if ($hashes instanceof ContentHash) {
          $duplicateUrls = $hashes->getDuplicates();
          if (!empty($duplicateUrls)) {
            $json->mergeRow('url-content-duplicates', 'duplicates', $duplicateUrls, true);
          }
        }

        $io->section('Generating files');
        $json->writeFiles($input->getOption('output'), $input->getOption('quiet'));
        $io->success('Done!');

        $output->writeln("<comment>Completed in ".(microtime(true) - $start)."</comment>");

    }//end execute()


    /**
     * Run web-based parsing via rolling curl.
     */
    private function runWeb($json, $io, $input, $hashes)
    {
        $request = new RollingCurl();

        while ($url = $this->config->getUrl()) {
            $request->get($url);
        }

        $request
            ->setCallback($this->requestCallback($this->config, $json, $io, $hashes, $input->getOption('debug')))
            ->setSimultaneousLimit(10)
            ->execute();

    }//end runWeb()


    /**
     * Run xml-based parsing.
     */
    private function runXml($json, $io, $input)
    {
        foreach ($this->config->get('files') as $file) {
            if (!file_exists($file)) {
                $json->mergeRow('error-file', 'missing', [$file], true);
                continue;
            }

            $row = new \stdClass;

            $io->write('Parsing... '.$file);

            while ($field = $this->config->getMapping()) {
                $crawler = new Crawler();
                $crawler->addXmlContent(file_get_contents($file));

                $type = self::TypeFactory($field['type'], $crawler, $json, $row, $field);
                try {
                    $type->process();
                } catch (ElementNotFoundException $e) {
                    $json->mergeRow($e::FILE, $file, [$e->getMessage()], true);
                } catch (ValidationException $e) {
                    $json->mergeRow($e::FILE, $file, [$e->getMessage()], true);
                } catch (\Exception $e) {
                    $json->mergeRow('error-unhandled', $file, [$e->getMessage()], true);
                }
            }

            // Reset the parser so we have mappings back at 0.
            $this->config->reset();
            $io->writeln(' <info>(Done!)</info>');

            if (!empty((array) $row)) {
                $json->addRow($this->config->get('entity_type'), $row);
            }
        }//end foreach

    }//end runXml()


}//end class
