<?php

namespace MagentoHackathon\Composer\Magento\Parser;

use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;

/**
 * Class ParserFactory
 * @package MagentoHackathon\Composer\Magento\Parser
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ParserFactory implements ParserFactoryInterface
{
    /**
     * @param PackageInterface $package
     * @param RootPackageInterface $rootPackage
     * @param string $sourceDir
     * @return Parser
     * @throws \ErrorException
     */
    public function make(PackageInterface $package, RootPackageInterface $rootPackage, $sourceDir)
    {
        $rootExtra = $rootPackage->getExtra();
        if (isset($rootExtra['magento-map-overwrite'])) {
            $moduleSpecificMap = array_change_key_case($rootExtra['magento-map-overwrite'], CASE_LOWER);
            if (isset($moduleSpecificMap[$package->getName()])) {
                $map = $moduleSpecificMap[$package->getName()];
                return new MapParser($map);
            }
        }

        $extra = $package->getExtra();

        if (isset($extra['map'])) {
            return new MapParser($extra['map']);
        }

        if (isset($extra['package-xml'])) {
            return new PackageXmlParser(sprintf('%s/%s', $sourceDir, $extra['package-xml']));
        }

        $modmanFile = sprintf('%s/modman', $sourceDir);
        if (file_exists($modmanFile)) {
            return new ModmanParser($modmanFile);
        }

        throw new \ErrorException(
            sprintf('Unable to find deploy strategy for module: "%s" no known mapping', $package->getName())
        );
    }
}