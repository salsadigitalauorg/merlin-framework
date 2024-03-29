<?php

namespace Merlin\Command;

use Merlin\Crawler\MigrateCrawler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Merlin\Parser\CrawlerConfig;
use Merlin\Output\Yaml;
use Symfony\Component\Console\Style\SymfonyStyle;
use Spatie\Crawler\CrawlUrl;
use GuzzleHttp\RequestOptions;
use Spatie\Browsershot\Browsershot;


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
      ->setDescription('Crawl a website and generate a list of URLs')
      ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to the configuration file')
      ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Path to the output directory', __DIR__)
      ->addOption('debug', 'd', InputOption::VALUE_REQUIRED, 'Output debug messages', false)
      ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit the max number of items to migrate', 0)
      ->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Number of requests to make in parallel', 10)
      ->addOption('no-cache', null, InputOption::VALUE_NONE, 'Disable cache on this run');

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

    $config = new CrawlerConfig($input->getOption('config'));
    $this->config = $config->getConfig();
    $start = microtime(true);
    $yaml = new Yaml($io, $config);

    $followRedirects = ($this->options['follow_redirects'] ?? true);
    if ($followRedirects === false) {
      $redirectOptions = false;
      } else {
      $redirectOptions = [
          'max'             => ($this->options['max_redirects'] ?? 5),
          'track_redirects' => true,
          'referer'         => true,
      ];
    }

    $headers = ['User-Agent' => 'Merlin (+https://github.com/salsadigitalauorg/merlin-framework)'];

    // Add headers listed in config, if any are provided.
    if (!empty($this->config['options']['headers'])) {
      $headers = array_merge($headers, $this->config['options']['headers']);
    }

    $clientOptions = [
        RequestOptions::COOKIES         => $this->config['options']['cookies'],
        RequestOptions::CONNECT_TIMEOUT => $this->config['options']['connect_timeout'],
        RequestOptions::TIMEOUT         => $this->config['options']['timeout'],
        RequestOptions::ALLOW_REDIRECTS => $redirectOptions,
        RequestOptions::VERIFY          => $this->config['options']['verify'],
        RequestOptions::HEADERS         => $headers,
        RequestOptions::READ_TIMEOUT    => 120,
    ];

    $baseUrl = $this->config['domain'];

    // Optionally disable cache at runtime.
    if ($input->getOption('no-cache')) {
      $config->disableCache();
    }

    // Set crawl queue, preload with URLs if provided.
    $crawlQueue = new \Merlin\Crawler\MigrateCrawlQueue($this->config);

    // Add.
    if (!empty($urls = @$this->config['options']['urls'])) {
      $urls = is_array($urls) ? $urls : [$urls];
      $baseUrl = $this->config['domain'].$urls[0];
      foreach ($urls as $url) {
        $io->writeln("Adding starting point URL to queue: {$url}");
        $uri = new \GuzzleHttp\Psr7\Uri($this->config['domain'].$url);
        $crawlQueue->add(CrawlUrl::create($uri));
      }
    }

    $crawler = MigrateCrawler::create($clientOptions)
      ->setCrawlObserver(new \Merlin\Crawler\MigrateCrawlObserver($io, $yaml))
      ->SetCrawlQueue($crawlQueue)
      ->setCrawlProfile(new \Merlin\Crawler\CrawlInternalUrls($this->config));

    // Optionally override concurrency (default is 10).
    if (!empty($concurrency = @$this->config['options']['concurrency'])) {
      $io->writeln("Setting concurrency to {$concurrency}");
      $crawler->setConcurrency($concurrency);
    }

    // Set the robots respect flag.
    if (!empty($this->config['options']['ignore_robotstxt'])) {
      $crawler->ignoreRobots();
    }

    // Optionally override maximum results (default is unlimited/all).
    if (!empty($input->getOption('limit')) || @$this->config['options']['maximum_total']) {
      $max = $input->getOption('limit') ? $input->getOption('limit') : @$this->config['options']['maximum_total'];
      $io->writeln("Setting maximum crawl count to {$max}");
      $crawler->setTotalCrawlLimit($max);
    }

    // Optionally override depth (default is unlimited).
    if (!empty($depth = @$this->config['options']['maximum_depth'])) {
      $io->writeln("Setting maximum depth to {$depth}");
      $crawler->setMaximumDepth($depth);
    }

    // Optionally add pause between crawls.
    if (!empty($delay = @$this->config['options']['delay'])) {
      $io->writeln("Setting delay between requests to {$delay}ms");
      $crawler->setDelayBetweenRequests($delay);
    }

    if (!empty($this->config['options']['ignore_robotstxt'])) {
      $crawler->ignoreRobots();
    }

    $io->success('Starting crawl!');

    $crawler->startCrawling($baseUrl);

    $io->section('Processing requests');

    $io->section('Generating files');
    $yaml->writeFiles($input->getOption('output'), $input->getOption('quiet'));
    $io->success('Done!');

    $output->writeln("<comment>Completed in ".(microtime(true) - $start)."</comment>");

    return 0;

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
