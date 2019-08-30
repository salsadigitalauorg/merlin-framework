<?php

namespace Migrate\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Migrate\Parser\CrawlerConfig;
use RollingCurl\RollingCurl;
use Migrate\Parser\ParserInterface;
use Migrate\Output\Yaml;
use Migrate\Output\OutputInterface as MigrateOutputInterface;
use RollingCurl\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Style\SymfonyStyle;
use Migrate\Exception\ElementNotFoundException;
use Migrate\Exception\ValidationException;
use Migrate\MigrateCrawlObserver;
use Spatie\Crawler\Crawler as SpatieCrawler;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;

class CrawlCommand extends Command
{

    /**
     * Set the default name for the command.
     *
     * @var string
     */
    protected static $defaultName = 'crawl';

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

        $config       = new CrawlerConfig($input->getOption('config'));
        $this->config = $config->getConfig();
        $start   = microtime(true);
        $yaml    = new Yaml($io, $config);

        $clientOptions = [
            RequestOptions::COOKIES         => true,
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::TIMEOUT         => 10,
            RequestOptions::ALLOW_REDIRECTS => $this->config['options']['follow_redirects'],
            RequestOptions::VERIFY          => false,
            RequestOptions::HEADERS         => ['User-Agent' => 'Merlin'],
        ];

        $baseUrl = $this->config['domain'];

        $crawler = SpatieCrawler::create($clientOptions)
          ->setCrawlObserver(new \Migrate\Crawler\MigrateCrawlObserver($io, $yaml))
          ->SetCrawlQueue(new \Migrate\Crawler\MigrateCrawlQueue($this->config))
          ->setCrawlProfile(new \Migrate\Crawler\CrawlInternalUrls($this->config));

        // Optionally override concurrency (default is 10).
        if (!empty($concurrency = @$this->config['options']['concurrency'])) {
          $io->writeln("Setting concurrency to {$concurrency}");
          $crawler->setConcurrency($concurrency);
        }

        // Optionally override maximum results (default is unlimited/all).
        if (!empty($max = @$this->config['options']['maximum_total'])) {
          $io->writeln("Setting maximum crawl count to {$max}");
          $crawler->setMaximumCrawlCount($max);
        }

        // Optionally override deptch (default is unlimited).
        if (!empty($depth = @$this->config['options']['maximum_depth'])) {
          $io->writeln("Setting maximum depth to {$depth}");
          $crawler->setMaximumDepth($depth);
        }

        // Optionally add pause between crawls.
        if (!empty($delay = @$this->config['options']['delay'])) {
          $io->writeln("Setting delay between requests to {$delay}ms");
          $crawler->setDelayBetweenRequests($delay);
        }

        $io->success('Starting crawl!');
        $crawler->startCrawling($baseUrl);

        $io->section('Processing requests');

        $io->section('Generating files');
        $yaml->writeFiles($input->getOption('output'), $input->getOption('quiet'));
        $io->success('Done!');

        $output->writeln("<comment>Completed in ".(microtime(true) - $start)."</comment>");

    }//end execute()


    private function add_header($header, $value)
    {
        return function (callable $handler) use ($header, $value) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler, $header, $value) {
                $request = $request->withHeader($header, $value);
                return $handler($request, $options);
            };
        };

    }//end add_header()


}//end class
