<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\DependencyInjection;
use PBDKN\TabellenverwaltungBundle\Command\ImportEfgCommand;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
final class ContaoTabellenverwaltungExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->register(ImportEfgCommand::class, ImportEfgCommand::class)
            ->setArguments([new Reference('doctrine.dbal.default_connection')])
            ->addTag('console.command', ['command' => 'directory:import-efg']);
    }
}
