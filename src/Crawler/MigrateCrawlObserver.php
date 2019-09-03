<?php

namespace Migrate\Crawler;

use Consolidation\Comments\Comments;
use Spatie\Crawler\CrawlObserver;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;


class MigrateCrawlObserver extends CrawlObserver
{
    /** @var \Symfony\Component\Console\Style\SymfonyStyle */
    protected $io;

    /** @var \Migrate\Output\OutputBase */
    protected $json;

    /** @var integer */
    protected $count = 0;


    public function __construct($io, $json)
    {
        $this->io = $io;
        $this->json = $json;

    }//end __construct()


    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface   $url
     */
    public function willCrawl(UriInterface $url)
    {
        $this->count++;

    }//end willCrawl()


    /**
     * Called when the crawler has crawled the given url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl=null
    ) {
        $url_string = $url->__toString();
        $return_url = $url_string;

        if (!empty($this->json->getConfig()->get('options')['path_only'])) {
          $query = parse_url($url_string, PHP_URL_QUERY);
          $return_url = !empty($query) ? parse_url($url_string, PHP_URL_PATH)."?{$query}" : parse_url($url_string, PHP_URL_PATH);
        }

        if (empty($return_url)) {
            $return_url = '/';
        }

        $this->io->writeln($url_string." (found on {$foundOnUrl})");

        $groups = isset($this->json->getConfig()->get('options')['group_by']) ? $this->json->getConfig()->get('options')['group_by'] : [];

        foreach ($groups as $config) {
            if (empty($config['type'])) {
                // Invalid group definition.
                continue;
            }

            $class_name = str_replace('_', '', ucwords($config['type'], '_'));
            $class = "\\Migrate\\Crawler\\Group\\".ucfirst($class_name);

            if (!class_exists($class)) {
                // An unknown type.
                continue;
            }

            $type = new $class($config);

            if ($type->match($url_string, $response)) {
                // Only match on the first option.
                return $this->json->mergeRow("crawled-urls-{$type->getId()}", $type->getId(), [$return_url], true);
            }
        }//end foreach

        // Add this to the default group if it doesn't match.
        $this->json->mergeRow('crawled-urls-default', 'default', [$return_url], true);

    }//end crawled()


    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl=null
    ) {
        $this->json->mergeRow('crawl-error', $url->__toString(), [$requestException->getMessage()], true);
        $this->io->error("Error: ${url} -- Found on url: ${foundOnUrl}");

    }//end crawlFailed()


    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling()
    {

      $this->mergeUrlsIntoConfigFiles();

      $this->io->success("Done!");
      $this->io->success($this->count." total URLs.");

    }//end finishedCrawling()


    /**
     * Checks config and merges any urls into the config files specified if they exist.
     */
    private function mergeUrlsIntoConfigFiles() {

        // /** @var \Migrate\Parser\Config $config */
        $config = $this->json->getConfig();

        // Check if any of our groups have merge config file names specified.
        $groups = isset($config->get('options')['group_by']) ? $config->get('options')['group_by'] : [];
        $merges = [];

        foreach ($groups as $group) {
            if (empty($group['type'])) {
                // Invalid group definition.
                continue;
            }

            if (isset($group['options']['merge_into'])) {
                $merges[] = [
                    'id'          => $group['id'],
                    'config_file' => $group['options']['merge_into'],
                ];
            }
        }

        // Check if the crawler has merge config specified (i.e, all urls).
        if (isset($config->get('options')['merge_into'])) {
            $merges[] = [
                'id'          => 'default',
                'config_file' => $config->get('options')['merge_into'],
            ];
        }

        // Get mergin'.
        if (count($merges) > 0) {
            $this->io->section('Merging urls into config files');

            // Config files are defined relative to the crawler config.
            $pathInfo = pathinfo($config->getSource());
            $basePath = $pathInfo['dirname'];

            foreach ($merges as $idx => $merge) {
                $errorMsg = null;
                $id = $merge['id'];
                $configFile = $merge['config_file'];
                $srcConfigFile = realpath($basePath.DIRECTORY_SEPARATOR.$configFile);

                $srcPathInfo = pathinfo($srcConfigFile);
                $srcConfigFilename = $srcPathInfo['filename'];
                $dstConfigFile = $srcPathInfo['dirname'].DIRECTORY_SEPARATOR.$srcConfigFilename."_merged_urls.yml";

                $data = $this->json->getData();
                $urls = ($data["crawled-urls-{$id}"][$id] ?? []);

                if (is_file($srcConfigFile)) {
                    if (empty($urls)) {
                        continue;
                    }

                    $srcConfig = \Spyc::YAMLLoad($srcConfigFile);
                    if (isset($srcConfig['urls'])) {
                        $srcUrls = &$srcConfig['urls'];
                    } else {
                        $srcUrls = [];
                    }

                    foreach ($urls as $url) {
                        if (!in_array($url, $srcUrls)) {
                            $srcUrls[] = trim($url);
                        }
                    }

                    $yaml = \Spyc::YAMLDump($srcConfig, 2, 0);

                    // Attempt to pull in comments from original.
                    // @TODO: This currently does not seem to bring in comments at the end of the line.
                    $commentManager = new Comments();
                    $commentManager->collect(explode("\n", file_get_contents($srcConfigFile)));
                    $yamlWithComments = $commentManager->inject(explode("\n", $yaml));
                    $yamlWithComments = implode("\n", $yamlWithComments);

                    $bytes = file_put_contents($dstConfigFile, $yamlWithComments);
                    if ($bytes !== false) {
                        $this->io->writeln("Merging urls into $dstConfigFile <info>Done!</info>");
                    } else {
                        $errorMsg = "Failed writing when merging urls into config file: {$dstConfigFile}";
                        $this->json->mergeRow('crawl-merge-error', $id, [$errorMsg], true);
                        $this->io->error($errorMsg);
                    }
                } else {
                    $errorMsg = "Could not find existing config file for group '{$id}' to merge urls: {$configFile}";
                    $this->json->mergeRow('crawl-merge-error', $id, [$errorMsg], true);
                    $this->io->error($errorMsg);
                }//end if
            }//end foreach
        }//end if

    }//end mergeUrlsIntoConfigFiles()


}//end class
