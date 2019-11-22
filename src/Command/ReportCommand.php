<?php

namespace Migrate\Command;


use Migrate\Reporting\MigrateReport;
use Migrate\Reporting\MigrateReportMedia;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReportCommand extends Command
{

  /**
   * Set the default name for the command.
   *
   * @var string
   */
  protected static $defaultName = 'report';


  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setDescription('Generate a post-migrate report to check migrated urls.')
    ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to the configuration file')
    ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Path to the output directory');

  }//end configure()


  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $io = new SymfonyStyle($input, $output);
    $io->title('Migration Reporting');

    // Confirm destination directory is writable.
    if (!is_writable($input->getOption('output'))) {
      $io->error("Error: Output dir '".$input->getOption('output')."' is not writable.");
      exit(1);
    }

    // Confirm we have a config file.
    if (!is_readable($input->getOption('config'))) {
      $io->error("Error: Config file '".$input->getOption('config')."' is not readable.");
      exit(1);
    }

    $config = \Spyc::YAMLLoad($input->getOption('config'));
    $output = $input->getOption('output');

    if (!empty($config)) {
      // The MigrateReport class will do its thing from a config.
      MigrateReport::generateReportsFromConfig($io, $config, $output);

      // If you wanted to do something more manual you would do something like:
      // $mr = new MigrateReport($io, $dstDomain, $srcDomain, $options);
      // $data = $mr->verifyUrls($urls);
      // $mr->writeReportFiles($data);
      // unset($mr);.
    } else {
      $io->writeln("Config empty! Nothing to do...");
    }

}//end execute()


}//end class
