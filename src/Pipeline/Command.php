<?php
/**
 * SwiftOtter_Base is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SwiftOtter_Base is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with SwiftOtter_Base. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Joseph Maxwell
 * @copyright SwiftOtter Studios, 10/28/16
 * @package default
 **/

namespace Driver\Pipeline;

use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Environment\Manager as EnvironmentManager;
use Driver\Pipeline\Transport\TransportInterface;
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

class Command extends ConsoleCommand implements CommandInterface
{
    const PIPELINE = 'pipeline';
    const ENVIRONMENT = 'environment';
    const DEBUG = 'debug';
    const TAG = 'tag';
    const CI = 'ci';

    private Master $pipeMaster;
    private EnvironmentManager $environmentManager;
    private LoggerInterface $logger;
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

    protected function configure()
    {
        $this->setName('run')
            ->setDescription('This tool runs handles databases; importing and exporting. It is based on pipelines which are defined in YAML configuration. Specify which pipeline to run as an argument.');

        $this->addArgument(self::PIPELINE, InputArgument::OPTIONAL, 'The pipeline to execute.')
            ->addOption(self::ENVIRONMENT, 'env', InputOption::VALUE_OPTIONAL, 'The environment(s) for which to run Driver.')
            ->addOption(self::TAG, 'tag', InputOption::VALUE_OPTIONAL, 'A tag for the output file.')
            ->addOption(self::DEBUG, 'd', InputOption::VALUE_OPTIONAL, 'Enable debug mode')
            ->addOption(self::CI, 'ci', InputOption::VALUE_OPTIONAL, 'Run without asking questions');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<comment>Executing Pipeline Command...</comment>");
        $this->logger->setParams($input, $output);
        $this->environmentManager->setRunFor($input->getOption(self::ENVIRONMENT));
        $this->tag->setTag($input->getOption(self::TAG));

        if ($pipeLine = $input->getArgument(self::PIPELINE)) {
            $this->pipeMaster->run($pipeLine);
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

            $output->writeln('Running pipeline: <info>'. $pipeLine . '</info>');

            $this->pipeMaster->run($pipeLine);
        }
    }

    private function askAboutEnv($input, $output)
    {
        $helper = $this->getHelper('question');

        if (!$input->getOption(self::ENVIRONMENT)) {
            $envQuestion = new ChoiceQuestion(
                '<question> What environment do you want to execute? </question>',
                $this->environmentManager->getAllEnvironments(),
                'local'
            );

            $env = $helper->ask($input, $output, $envQuestion);
            $output->writeln('Using: <info>'. $env . '</info> environment');

            $input->setOption(self::ENVIRONMENT, $env);
            $this->environmentManager->setRunFor($env);
        }
    }


    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $this->output->writeln("<error>The Pipe command cannot be included in a pipe. It is the mother of all pipes.</error>");
        throw new \Exception('The Pipe command cannot be included in a pipe. It is the mother of all pipes.');
    }

    public function getProperties()
    {
        return $this->properties;
    }
}
