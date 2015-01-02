<?php
/**
 * ModuleInstaller.php
 */

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;
use MagentoHackathon\Composer\Magento\Parser\ParserFactoryInterface;

/**
 * Class ModuleInstaller
 *
 * @package MagentoHackathon\Composer\Magento\Installer
 */
class ModuleInstaller extends MagentoInstallerAbstract
{
    /**
     * Package Type Definition
     */
    const PACKAGE_TYPE = 'magento-module';

    /**
     * @param IOInterface $io
     * @param Composer $composer
     * @param ParserFactoryInterface $parserFactory
     * @param string $type
     *
     * @throws \ErrorException
     */
    public function __construct(
        IOInterface $io,
        Composer $composer,
        ParserFactoryInterface $parserFactory,
        $type = 'magento-module'
    ) {
        parent::__construct($io, $composer, $parserFactory, $type);
    }

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     *
     * @return bool
     */
    public function supports($packageType)
    {
        return self::PACKAGE_TYPE === $packageType;
    }
}
