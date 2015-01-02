<?php

namespace MagentoHackathon\Composer\Magento\Parser;

use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;

/**
 * Interface ParserFactoryInterface
 * @package MagentoHackathon\Composer\Magento\Parser
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
interface ParserFactoryInterface
{

    /**
     * @param PackageInterface $package
     * @param RootPackageInterface $rootPackage
     * @param string $sourceDir
     * @return Parser
     */
    public function make(PackageInterface $package, RootPackageInterface $rootPackage, $sourceDir);
}
