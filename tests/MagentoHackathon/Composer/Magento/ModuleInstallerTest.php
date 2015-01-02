<?php
namespace MagentoHackathon\Composer\Magento;

use Composer\Util\Filesystem;
use Composer\Test\TestCase;
use Composer\Composer;
use Composer\Config;
use MagentoHackathon\Composer\Magento\Installer\ModuleInstaller;
use MagentoHackathon\Composer\Magento\Parser\ParserFactory;
use MagentoHackathon\Composer\Magento\Parser\PathTranslationParserFactory;

class ModuleInstallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ModuleInstaller
     */
    protected $object;

    /** @var  Composer */
    protected $composer;
    protected $config;
    protected $vendorDir;
    protected $binDir;
    protected $magentoDir;
    protected $dm;
    protected $repository;
    protected $io;
    /** @var Filesystem */
    protected $fs;

    protected function setUp()
    {
        $this->fs = new Filesystem;


        $this->vendorDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'composer-test-vendor';
        $this->fs->ensureDirectoryExists($this->vendorDir);

        $this->binDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'composer-test-bin';
        $this->fs->ensureDirectoryExists($this->binDir);

        $this->magentoDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'composer-test-magento';
        $this->fs->ensureDirectoryExists($this->magentoDir);

        $this->composer = new Composer();
        $this->config = new Config();
        $this->composer->setConfig($this->config);
        $this->composer->setPackage($this->createPackageMock());

        $this->config->merge(array(
            'config' => array(
                'vendor-dir' => $this->vendorDir,
                'bin-dir' => $this->binDir,
            ),
        ));

        $this->dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
               ->disableOriginalConstructor()
               ->getMock();
        $this->composer->setDownloadManager($this->dm);

        $this->repository = $this->getMock('Composer\Repository\InstalledRepositoryInterface');
        $this->io = $this->getMock('Composer\IO\IOInterface');

        $parserFactory = new PathTranslationParserFactory(new ParserFactory(), new ProjectConfig(array()));
        $this->object = new ModuleInstaller($this->io, $this->composer, $parserFactory);
    }

    protected function tearDown()
    {
        $this->fs->removeDirectory($this->vendorDir);
        $this->fs->removeDirectory($this->binDir);
        $this->fs->removeDirectory($this->magentoDir);
    }

    protected function createPackageMock(array $extra = array(), $name = 'example/test')
    {
        //$package= $this->getMockBuilder('Composer\Package\RootPackageInterface')
        $package = $this->getMockBuilder('Composer\Package\RootPackage')
                ->setConstructorArgs(array(md5(rand()), '1.0.0.0', '1.0.0'))
                ->getMock();
        $extraData = array_merge(array('magento-root-dir' => $this->magentoDir), $extra);

        $package->expects($this->any())
                ->method('getExtra')
                ->will($this->returnValue($extraData));
        
        $package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $package;
    }

    /**
     * @dataProvider deployMethodProvider
     */
    public function testGetDeployStrategy( $strategy, $expectedClass, $composerExtra = array(), $packageName )
    {
        $extra = array('magento-deploystrategy' => $strategy);
        $extra = array_merge($composerExtra, $extra);
        $package = $this->createPackageMock($extra,$packageName);
        $this->composer->setPackage($package);
        $parserFactory = new PathTranslationParserFactory(new ParserFactory(), new ProjectConfig(array()));
        $installer = new ModuleInstaller($this->io, $this->composer, $parserFactory);
        $this->assertInstanceOf($expectedClass, $installer->getDeployStrategy($package));
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer\ModuleInstaller::supports
     */
    public function testSupports()
    {
        $this->assertTrue($this->object->supports('magento-module'));
    }
    
    public function deployMethodProvider()
    {
        $deployOverwrite = array(
            'example/test2' => 'symlink',
            'example/test3' => 'none',
        );
        
        return array(
            array(
                'method' => 'copy',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Copy',
                'composerExtra' => array(  ),
                'packageName'   => 'example/test1',
            ),
            array(
                'method' => 'symlink',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Symlink',
                'composerExtra' => array(  ),
                'packageName'   => 'example/test1',
            ),
            array(
                'method' => 'link',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Link',
                'composerExtra' => array(  ),
                'packageName'   => 'example/test1',
            ),
            array(
                'method' => 'none',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\None',
                'composerExtra' => array(  ),
                'packageName'   => 'example/test1',
            ),
            array(
                'method' => 'symlink',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Symlink',
                'composerExtra' => array( 'magento-deploystrategy-overwrite' => $deployOverwrite ),
                'packageName'   => 'example/test2',
            ),
            array(
                'method' => 'symlink',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\None',
                'composerExtra' => array( 'magento-deploystrategy-overwrite' => $deployOverwrite ),
                'packageName'   => 'example/test3',
            ),
        );
    }

    /*
     * Test that path mapping translation code doesn't have any effect when no
     * translations are specified.
     */

    /**
     * joinFilePathsProvider
     *
     * @return array
     */
    public function joinFilePathsProvider()
    {
        $ds = DIRECTORY_SEPARATOR;
        return array(
            array('app/etc/', '/modules', 'app'.$ds.'etc'.$ds.'modules'),
            array('app/etc/', 'modules', 'app'.$ds.'etc'.$ds.'modules'),
            array('app/etc', 'modules', 'app'.$ds.'etc'.$ds.'modules'),
            array('/app/etc', '/modules', $ds.'app'.$ds.'etc'.$ds.'modules'),
            array('/app/etc/', '/modules', $ds.'app'.$ds.'etc'.$ds.'modules'),
            array('/app/etc', 'modules/', $ds.'app'.$ds.'etc'.$ds.'modules'.$ds),
            array('app\\etc\\', '\\modules', 'app'.$ds.'etc'.$ds.'modules'),
            array('app\\etc\\', 'modules', 'app'.$ds.'etc'.$ds.'modules'),
            array('app\\etc', 'modules', 'app'.$ds.'etc'.$ds.'modules'),
            array('\\app\\etc', '\\modules', $ds.'app'.$ds.'etc'.$ds.'modules'),
            array('\\app\\etc\\', '\\modules', $ds.'app'.$ds.'etc'.$ds.'modules'),
            array('\\app\\etc', 'modules\\', $ds.'app'.$ds.'etc'.$ds.'modules'.$ds)
        );
    }

    /**
     * testJoinFilePaths
     *
     * @param $path1
     * @param $path2
     * @param $expected
     *
     * @return void
     *
     * @dataProvider joinFilePathsProvider
     * @covers MagentoHackathon\Composer\Magento\Installer\ModuleInstaller::joinFilePaths
     */
    public function testJoinFilePaths($path1, $path2, $expected)
    {
        $this->assertEquals($expected, $this->object->joinFilePath($path1, $path2));
    }

}

