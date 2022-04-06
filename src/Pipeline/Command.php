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
use Driver\Pipeline\Transport\Factory as TransportFactory;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Logs\LoggerInterface;
use Driver\System\Tag;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Driver\Pipeline\Environment\Manager as EnvironmentManager;

class Command extends ConsoleCommand implements CommandInterface
{
    const PIPELINE = 'pipeline';
    const ENVIRONMENT = 'environment';
    const DEBUG = 'debug';
    const TAG = 'tag';

    private TransportFactory $transportFactory;
    private Master $pipeMaster;
    private EnvironmentManager$environmentManager;
    private array $properties;
    private LoggerInterface $logger;
    private ConsoleOutput $output;
    private Tag $tag;

    public function __construct(
        Master $pipeMaster,
        TransportFactory $transportFactory,
        LoggerInterface $logger,
        EnvironmentManager $environmentManager,
        ConsoleOutput $output,
        Tag $tag,
        array $properties = []
    ) {
        $this->transportFactory = $transportFactory;
        $this->pipeMaster = $pipeMaster;
        $this->logger = $logger;
        $this->environmentManager = $environmentManager;
        $this->output = $output;
        $this->tag = $tag;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Executes the pipe line specified in the -p (--pipeline) parameter.');

        $this->addArgument(self::PIPELINE, InputArgument::OPTIONAL, 'The pipeline to execute (leave blank to run default pipeline).')
            ->addOption(self::ENVIRONMENT, 'env', InputOption::VALUE_OPTIONAL, 'The environment(s) for which to run Driver.')
            ->addOption(self::TAG, 'tag', InputOption::VALUE_OPTIONAL, 'A tag for the output file.')
            ->addOption(self::DEBUG, 'd', InputOption::VALUE_OPTIONAL, 'Enable debug mode');
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
            $this->pipeMaster->runDefault();
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
