<?php

namespace Migrate\Command;

use GuzzleHttp\RequestOptions;
use Migrate\Fetcher\Cache;
use Migrate\Fetcher\FetcherBase;
use Migrate\Fetcher\FetcherSpatieCrawler;
use Migrate\Fetcher\FetcherSpatieCrawlerObserver;
use Migrate\Fetcher\Process;
use Migrate\Fetcher\FetcherSpatieCrawlerQueue;
use Migrate\Fetcher\ContentHash;
use Spatie\Browsershot\Browsershot;
use Spatie\Crawler\Crawler as SpatieCrawler;
use Spatie\Crawler\CrawlUrl;
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
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of items to migrate', 0)
            ->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Number of requests to make in parallel', 10)
            ->addOption('no-cache', null, InputOption::VALUE_NONE, 'Disable cache on this run');

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

        if ($input->getOption('no-cache')) {
          $this->config->disableCache();
        }

        $io->success('Done!');

        $start   = microtime(true);
        $json    = new Json($io, $config);

        $io->section('Processing requests');

        if ($limit = $input->getOption('limit')) {
            $io->writeln("Setting the maximum migrate count to {$limit} items.");
            $io->writeln('');
        }

        if ($this->config->get('parser') == 'xml') {
            $this->runXml($json, $io, $input);
        } else {
            $this->runWeb($json, $io, $input);
        }

        $io->section('Generating files');
        $json->writeFiles($input->getOption('output'), $input->getOption('quiet'));
        $io->success('Done!');

        $output->writeln("<comment>Completed in ".(microtime(true) - $start)."</comment>");

    }//end execute()


    /**
     * Run web-based parsing via a delegated Fetcher.
     *
     * @param \Migrate\Output\OutputInterface                   $json
     * @param \Symfony\Component\Console\Output\OutputInterface $io
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     *
     * @throws \Exception
     */
    private function runWeb(\Migrate\Output\OutputInterface $json, OutputInterface $io, InputInterface $input) {
      $useCache     = ($this->config->get('fetch_options')['cache_enabled'] ?? true);
      $cacheDir     = ($this->config->get('fetch_options')['cache_dir'] ?? "/tmp/merlin_cache");
      $fetcherClass = ($this->config->get('fetch_options')['fetcher_class'] ?? "\\Migrate\\Fetcher\\Fetchers\\SpatieCrawler\\FetcherSpatieCrawler");

      // Optionally override maximum results (default is unlimited/all).
      $limit = $input->getOption('limit') ? $input->getOption('limit') : 0;
      $urls = $limit ? array_slice($this->config->get('urls'), 0, $limit, true) : $this->config->get('urls');

      $fetcher = FetcherBase::FetcherFactory($fetcherClass, $io, $json, $this->config);

      // Use cache?
      $cache = null;
      if ($useCache) {
        $cache = new Cache($this->config->get('domain'), $cacheDir);
        $fetcher->setCache($cache);
      }

      // Processed cached and build non-cached url list to fetch.
      foreach ($urls as $url) {
        $url = $this->config->get('domain').$url;
        $fetcher->incrementCount('total');

        if ($cache instanceof Cache) {
          if ($cacheJson = $cache->get($url)) {
            $cacheData = json_decode($cacheJson, true);
            if (is_array($cacheData) && key_exists('contents', $cacheData) && !empty($cacheData['contents'])) {
              $contents = $cacheData['contents'];
              $io->writeln("Fetched (cache): {$url}");
              $fetcher->processContent($url, $contents);
              $fetcher->incrementCount('fetched_cache');
              continue;
            }
          }
        }

        $fetcher->addUrl($url);
      }

      $fetcher->start();
      $fetcher->complete();

    }//end runWeb()


    /**
     * Run xml-based parsing.
     *
     * @param \Migrate\Output\OutputInterface                   $json
     * @param \Symfony\Component\Console\Output\OutputInterface $io
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     *
     * @throws \Exception
     */
    private function runXml(\Migrate\Output\OutputInterface $json, OutputInterface $io, InputInterface $input) {

        // Optionally override maximum results (default is unlimited/all).
        $limit = $input->getOption('limit') ? $input->getOption('limit') : 0;
        $files = $limit ? array_slice($this->config->get('files'), 0, $limit, true) : $this->config->get('files');
        $entity_type = $this->config->get('entity_type');

        foreach ($files as $file) {
            if (!file_exists($file)) {
                $json->mergeRow("{$entity_type}-error-file", 'missing', [$file], true);
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
                    $json->mergeRow("{$entity_type}-".$e::FILE, $file, [$e->getMessage()], true);
                } catch (ValidationException $e) {
                    $json->mergeRow("{$entity_type}-".$e::FILE, $file, [$e->getMessage()], true);
                } catch (\Exception $e) {
                    $json->mergeRow("{$entity_type}-error-unhandled", $file, [$e->getMessage()], true);
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
