<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\ContaoManager;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use PBDKN\TabellenverwaltungBundle\ContaoTabellenverwaltungBundle;
final class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [BundleConfig::create(ContaoTabellenverwaltungBundle::class)->setLoadAfter([ContaoCoreBundle::class])];
    }
}
