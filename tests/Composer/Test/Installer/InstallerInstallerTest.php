<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Test\Installer;

use Composer\Installer\InstallerInstaller;
use Composer\Package\Loader\JsonLoader;
use Composer\Package\PackageInterface;

class InstallerInstallerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $loader = new JsonLoader();
        $this->packages = array();
        for ($i = 1; $i <= 3; $i++) {
            $this->packages[] = $loader->load(__DIR__.'/Fixtures/installer-v'.$i.'/composer.json');
        }

        $this->dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->im = $this->getMockBuilder('Composer\Installer\InstallationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMock('Composer\Repository\WritableRepositoryInterface');

        $this->io = $this->getMock('Composer\IO\IOInterface');
    }

    public function testInstallNewInstaller()
    {
        $this->repository
            ->expects($this->once())
            ->method('getPackages')
            ->will($this->returnValue(array()));
        $installer = new InstallerInstallerMock(__DIR__.'/Fixtures/', __DIR__.'/Fixtures/bin', $this->dm, $this->io, $this->im, array($this->repository));

        $test = $this;
        $this->im
            ->expects($this->once())
            ->method('addInstaller')
            ->will($this->returnCallback(function ($installer) use ($test) {
                $test->assertEquals('installer-v1', $installer->version);
            }));

        $installer->install($this->repository, $this->packages[0]);
    }

    public function testUpgradeWithNewClassName()
    {
        $this->repository
            ->expects($this->once())
            ->method('getPackages')
            ->will($this->returnValue(array($this->packages[0])));
        $this->repository
            ->expects($this->exactly(2))
            ->method('hasPackage')
            ->will($this->onConsecutiveCalls(true, false));
        $installer = new InstallerInstallerMock(__DIR__.'/Fixtures/', __DIR__.'/Fixtures/bin', $this->dm, $this->io, $this->im, array($this->repository));

        $test = $this;
        $this->im
            ->expects($this->once())
            ->method('addInstaller')
            ->will($this->returnCallback(function ($installer) use ($test) {
                $test->assertEquals('installer-v2', $installer->version);
            }));

        $installer->update($this->repository, $this->packages[0], $this->packages[1]);
    }

    public function testUpgradeWithSameClassName()
    {
        $this->repository
            ->expects($this->once())
            ->method('getPackages')
            ->will($this->returnValue(array($this->packages[1])));
        $this->repository
            ->expects($this->exactly(2))
            ->method('hasPackage')
            ->will($this->onConsecutiveCalls(true, false));
        $installer = new InstallerInstallerMock(__DIR__.'/Fixtures/', __DIR__.'/Fixtures/bin', $this->dm, $this->io, $this->im, array($this->repository));

        $test = $this;
        $this->im
            ->expects($this->once())
            ->method('addInstaller')
            ->will($this->returnCallback(function ($installer) use ($test) {
                $test->assertEquals('installer-v3', $installer->version);
            }));

        $installer->update($this->repository, $this->packages[1], $this->packages[2]);
    }
}

class InstallerInstallerMock extends InstallerInstaller
{
    public function getInstallPath(PackageInterface $package)
    {
        $version = $package->getVersion();
        return __DIR__.'/Fixtures/installer-v'.$version[0].'/';
    }
}