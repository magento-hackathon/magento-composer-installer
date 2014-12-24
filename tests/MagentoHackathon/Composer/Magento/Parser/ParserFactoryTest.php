<?php

namespace MagentoHackathon\Composer\Magento\Parser;

use Composer\Package\Package;
use Composer\Package\RootPackage;
use org\bovigo\vfs\vfsStream;

/**
 * Class ParserFactoryTest
 * @package MagentoHackathon\Composer\Magento\Parser
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ParserFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $root;

    public function setUp()
    {
        $this->root = vfsStream::setup('root');
    }

    public function testMapParserIsReturnedIfMapOverwriteFound()
    {
        $package = new Package('module-package', '1.0.0', 'module-package');
        $rootPackage = new RootPackage('root-package', '1.0.0', 'root-package');
        $rootPackage->setExtra(array('magento-map-overwrite' => array('module-package' => array())));

        $factory = new ParserFactory;
        $instance = $factory->make($package, $rootPackage, vfsStream::url('root'));
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Parser\MapParser', $instance);
    }

    public function testMapParserIsReturnIfModuleMapFound()
    {

        $package = new Package('module-package', '1.0.0', 'module-package');
        $package->setExtra(array('map' => array()));

        $rootPackage = new RootPackage('root-package', '1.0.0', 'root-package');

        $factory = new ParserFactory;
        $instance = $factory->make($package, $rootPackage, vfsStream::url('root'));
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Parser\MapParser', $instance);
    }

    public function testPackageXmlParserIsReturnedIfPackageXmlKeyIsFound()
    {

        vfsStream::newFile('Package.xml')->at($this->root);
        $package = new Package('module-package', '1.0.0', 'module-package');
        $package->setExtra(array('package-xml' => 'Package.xml'));

        $rootPackage = new RootPackage('root-package', '1.0.0', 'root-package');

        $factory = new ParserFactory;
        $instance = $factory->make($package, $rootPackage, vfsStream::url('root'));
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Parser\PackageXmlParser', $instance);
    }

    public function testModmanParserIsReturnedIfModmanFileIsFound()
    {

        vfsStream::newFile('modman')->at($this->root);
        $package = new Package('module-package', '1.0.0', 'module-package');

        $rootPackage = new RootPackage('root-package', '1.0.0', 'root-package');

        $factory = new ParserFactory;
        $instance = $factory->make($package, $rootPackage, vfsStream::url('root'));
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Parser\ModmanParser', $instance);
    }

    public function testExceptionIsThrownIfNoParserConditionsAreMet()
    {
        $this->setExpectedException(
            'ErrorException',
            'Unable to find deploy strategy for module: "module-package" no known mapping'
        );

        $package = new Package('module-package', '1.0.0', 'module-package');
        $rootPackage = new RootPackage('root-package', '1.0.0', 'root-package');

        $factory = new ParserFactory;
        $instance = $factory->make($package, $rootPackage, vfsStream::url('root'));
    }
}
