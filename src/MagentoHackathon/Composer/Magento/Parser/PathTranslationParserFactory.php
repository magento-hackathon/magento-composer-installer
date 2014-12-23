<?php

namespace MagentoHackathon\Composer\Magento\Parser;

use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use MagentoHackathon\Composer\Magento\ProjectConfig;

/**
 * Class PathTranslationParserFactory
 * @package MagentoHackathon\Composer\Magento\Parser
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PathTranslationParserFactory implements ParserFactoryInterface
{
    /**
     * @var ParserFactoryInterface
     */
    protected $parserFactory;

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @param ParserFactoryInterface $parserFactory
     */
    public function __construct(ParserFactoryInterface $parserFactory, ProjectConfig $config)
    {
        $this->parserFactory = $parserFactory;
        $this->config = $config;
    }

    /**
     * @param PackageInterface $package
     * @param RootPackageInterface $rootPackage
     * @param string $sourceDir
     * @return Parser
     * @throws \ErrorException
     */
    public function make(PackageInterface $package, RootPackageInterface $rootPackage, $sourceDir)
    {
        $parser = $this->parserFactory->make($package, $rootPackage, $sourceDir);

        if ($this->config->hasPathMappingTranslations()) {
            $translations = $this->config->getPathMappingTranslations();
            return new PathTranslationParser($parser, $translations);
        }

        return $parser;
    }
}
