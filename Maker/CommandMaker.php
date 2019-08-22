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
            ->addArgument('bundleNamespace', InputArgument::REQUIRED, 'Namespace for your command (e.g. <fg=yellow>App</>)')
            ->addArgument('aggregateNamespace', InputArgument::REQUIRED, 'Your aggregates namespace (e.g. <fg=yellow>RevisionTen\CMS\Model</>)')
            ->addArgument('aggregateClass', InputArgument::REQUIRED, 'Your aggregates classname (e.g. <fg=yellow>Page</>)')
            ->addArgument('eventText', InputArgument::REQUIRED, 'Your events log message (e.g. <fg=yellow>Page Created</>)')
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $commandName = $input->getArgument('commandName');
        $bundleNamespace = $input->getArgument('bundleNamespace');
        $aggregateClass = $input->getArgument('aggregateClass');
        $aggregateNamespace = $input->getArgument('aggregateNamespace');
        $eventText = $input->getArgument('eventText');

        if (class_exists($aggregateNamespace.'\\'.$aggregateClass)) {
            $generator->generateClass($bundleNamespace.'\\Command\\'.$commandName.'Command',
                __DIR__.'/../Resources/skeleton/Command.tpl.php',
                [
                    'commandName' => $commandName,
                    'bundleNamespace' => $bundleNamespace,
                    'aggregateClass' => $aggregateClass,
                    'aggregateNamespace' => $aggregateNamespace,
                    'eventText' => $eventText,
                ]
            );
            $generator->generateClass($bundleNamespace.'\\Event\\'.$commandName.'Event',
                __DIR__.'/../Resources/skeleton/Event.tpl.php',
                [
                    'commandName' => $commandName,
                    'bundleNamespace' => $bundleNamespace,
                    'aggregateClass' => $aggregateClass,
                    'aggregateNamespace' => $aggregateNamespace,
                    'eventText' => $eventText,
                ]
            );
            $generator->generateClass($bundleNamespace.'\\Handler\\'.$commandName.'Handler',
                __DIR__.'/../Resources/skeleton/Handler.tpl.php',
                [
                    'commandName' => $commandName,
                    'bundleNamespace' => $bundleNamespace,
                    'aggregateClass' => $aggregateClass,
                    'aggregateNamespace' => $aggregateNamespace,
                    'eventText' => $eventText,
                ]
            );

            $generator->writeChanges();
            $this->writeSuccessMessage($io);
        } else {
            $io->text('<error>Aggregate does not exist</error>');
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }
}
