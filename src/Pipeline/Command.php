<?php

declare(strict_types=1);

namespace Driver\Pipeline;

use Driver\Pipeline\Environment\Manager as EnvironmentManager;
use Driver\System\Logs\LoggerInterface;
use Driver\System\Tag;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class Command extends ConsoleCommand
{
    private const PIPELINE = 'pipeline';
    private const ENVIRONMENT = 'environment';
    private const DEBUG = 'debug';
    private const TAG = 'tag';
    private const CI = 'ci';

    private Master $pipeMaster;
    private LoggerInterface $logger;
    private EnvironmentManager $environmentManager;
    private ConsoleOutput $output;
    private Tag $tag;

    public function __construct(
        Master $pipeMaster,
        LoggerInterface $logger,
        EnvironmentManager $environmentManager,
        ConsoleOutput $output,
        Tag $tag
    ) {
        $this->pipeMaster = $pipeMaster;
        $this->logger = $logger;
        $this->environmentManager = $environmentManager;
        $this->output = $output;
        $this->tag = $tag;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('run')
            ->setDescription(
                'This tool runs handles databases; importing and exporting. '
                . 'It is based on pipelines which are defined in YAML configuration. '
                . 'Specify which pipeline to run as an argument.'
            );

        $this->addArgument(self::PIPELINE, InputArgument::OPTIONAL, 'The pipeline to execute.')
            ->addOption(
                self::ENVIRONMENT,
                'env',
                InputOption::VALUE_OPTIONAL,
                'The environment(s) for which to run Driver.'
            )
            ->addOption(self::TAG, 'tag', InputOption::VALUE_OPTIONAL, 'A tag for the output file.')
            ->addOption(self::DEBUG, 'd', InputOption::VALUE_OPTIONAL, 'Enable debug mode')
            ->addOption(self::CI, 'ci', InputOption::VALUE_OPTIONAL, 'Run without asking questions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln("<comment>Executing Pipeline Command...</comment>");
        $this->logger->setParams($input, $output);
        $this->environmentManager->setRunFor($input->getOption(self::ENVIRONMENT));
        $this->tag->setTag($input->getOption(self::TAG));

        if ($pipeLine = $input->getArgument(self::PIPELINE)) {
            $transport = $this->pipeMaster->run($pipeLine);
        } else {
            if (!$input->hasOption(self::CI)) {
                $helper = $this->getHelper('question');
                $pipelineQuestion = new Question(
                    '<question> What pipeline do you want to execute? </question> [type enter for default]: ',
                    Master::DEFAULT_NODE
                );

                $pipeLine = $helper->ask($input, $output, $pipelineQuestion);

                $this->askAboutEnv($input, $output);
            } else {
                $pipeLine = Master::DEFAULT_NODE;
            }

            $output->writeln('Running pipeline: <info>' . $pipeLine . '</info>');

            $transport = $this->pipeMaster->run($pipeLine);
        }

        foreach ($transport->getErrors() as $error) {
            $output->writeln('<error>' . $error->getNode() . ' - ' . $error->getMessage() . '</error>');
        }
    }

    private function askAboutEnv(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');

        if (!$input->getOption(self::ENVIRONMENT)) {
            $envQuestion = new ChoiceQuestion(
                '<question> What environment do you want to execute? </question>',
                $this->environmentManager->getAllEnvironments(),
                'local'
            );

            $env = $helper->ask($input, $output, $envQuestion);
            $output->writeln('Using: <info>' . $env . '</info> environment');

            $input->setOption(self::ENVIRONMENT, $env);
            $this->environmentManager->setRunFor($env);
        }
    }
}
