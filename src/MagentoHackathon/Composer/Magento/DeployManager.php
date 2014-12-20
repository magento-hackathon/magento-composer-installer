<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento;


use Composer\IO\IOInterface;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\Deploystrategy\Copy;

class DeployManager {

    const SORT_PRIORITY_KEY = 'magento-deploy-sort-priority';

    /**
     * @var Entry[]
     */
    protected $packages = array();

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * an array with package names as key and priorities as value
     * 
     * @var array
     */
    protected $sortPriority = array();

    /**
     * @var ProjectConfig
     */
    protected $projectConfig;

    /**
     * @param IOInterface   $io
     * @param ProjectConfig $projectConfig
     */
    public function __construct(IOInterface $io, ProjectConfig $projectConfig )
    {
        $this->io = $io;
        $this->projectConfig = $projectConfig;
    }

    /**
     * @param Entry $package
     */
    public function addPackage(Entry $package)
    {
        $this->packages[] = $package;
    }

    /**
     * @param $priorities
     */
    public function setSortPriority($priorities)
    {
        $this->sortPriority = $priorities;
    }

    /**
     * uses the sortPriority Array to sort the packages.
     * Highest priority first.
     * Copy gets per default higher priority then others
     */
    protected function sortPackages()
    {
        $sortPriority = $this->sortPriority;
        $getPriorityValue = function( Entry $object ) use ( $sortPriority ){
            $result = 100;
            if( isset($sortPriority[$object->getPackageName()]) ){
                $result = $sortPriority[$object->getPackageName()];
            }elseif( $object->getDeployStrategy() instanceof Copy ){
                $result = 101;
            }
            return $result;
        };
        usort( 
            $this->packages, 
            function($a, $b)use( $getPriorityValue ){
                /** @var Entry $a */
                /** @var Entry $b */
                $aVal = $getPriorityValue($a);
                $bVal = $getPriorityValue($b);
                if ($aVal == $bVal) {
                    return 0;
                }
                return ($aVal > $bVal) ? -1 : 1;
            }
        );
    }
    
    public function doDeploy()
    {
        $this->sortPackages();
        /** @var Entry $package */
        foreach( $this->packages as $package ){
            if( $this->io->isDebug() ){
                $this->io->write('start magento deploy for '. $package->getPackageName() );
            }
            $package->getDeployStrategy()->deploy();

            $deployedFiles = $package->getDeployStrategy()->getDeployedFiles();

            if ($this->projectConfig->hasAutoAppendGitignore()) {
                $this->appendGitIgnore($package, $deployedFiles, $this->getGitIgnoreFileLocation());
            }
        }
    }

    /**
     * Get .gitignore file location
     *
     * @return string
     */
    public function getGitIgnoreFileLocation()
    {
        $ignoreFile = $this->projectConfig->getMagentoRootDir() . '/.gitignore';
        return $ignoreFile;
    }

    /**
     * Add all the files which are to be deployed
     * to the .gitignore file, if it doesn't
     * exist then create a new one
     *
     * @param Entry $package
     * @param array $deployedFiles
     */
    public function appendGitIgnore(Entry $package, array $deployedFiles, $ignoreFile)
    {
        $contents = array();
        if (file_exists($ignoreFile)) {
            $contents = file($ignoreFile, FILE_IGNORE_NEW_LINES);
        }

        foreach ($deployedFiles as $ignore) {
            if (!in_array($ignore, $contents)) {
                $additions[] = $ignore;
            }
        }

        if (!empty($additions)) {
            array_unshift($additions, '#' . $package->getPackageName());
            $contents = array_merge($contents, $additions);
            file_put_contents($ignoreFile, implode("\n", $contents));
        }
    }
}

