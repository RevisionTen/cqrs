<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Maker;

use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class CommandMaker extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:cqrscommand';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new cqrs command')
            ->addArgument('commandName', InputArgument::REQUIRED, 'Choose a name for your command (e.g. <fg=yellow>PageCreate</>)')
            ->addArgument('namespace', InputArgument::REQUIRED, 'Namespace for your command (e.g. <fg=yellow>App</>)')
            ->addArgument('aggregateClass', InputArgument::REQUIRED, 'Your aggregates namespace (e.g. <fg=yellow>RevisionTen\CMS\Model</>)')
            ->addArgument('aggregateNamespace', InputArgument::REQUIRED, 'Your aggregates classname (e.g. <fg=yellow>Page</>)')
            ->addArgument('eventText', InputArgument::REQUIRED, 'Your events log message (e.g. <fg=yellow>Page Created</>)')
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $commandName = $input->getArgument('commandName');
        $namespace = $input->getArgument('namespace');
        $aggregateClass = $input->getArgument('aggregateClass');
        $aggregateNamespace = $input->getArgument('aggregateNamespace');
        $eventText = $input->getArgument('eventText');

        $generator->generateClass($namespace.'\\'.$commandName.'Command',
            'Command.tpl.php',
            [
                'commandName' => $commandName,
                'namespace' => $namespace,
                'aggregateClass' => $aggregateClass,
                'aggregateNamespace' => $aggregateNamespace,
                'eventText' => $eventText,
            ]
        );

        $generator->writeChanges();
        $this->writeSuccessMessage($io);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }
}
