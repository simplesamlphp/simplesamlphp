<?php

namespace SimpleSAML\Test\Utils;

use PHPUnit\Framework\TestCase;
use \SimpleSAML\Configuration;
use \SimpleSAML\Utils\System;

use \org\bovigo\vfs\vfsStream;

/**
 * Tests for SimpleSAML\Utils\System.
 */
class SystemTest extends TestCase
{
    const ROOTDIRNAME = 'testdir';
    const DEFAULTTEMPDIR = 'tempdir';

    public function setUp()
    {
        $this->root = vfsStream::setup(
            self::ROOTDIRNAME,
            null,
            [
                self::DEFAULTTEMPDIR => [],
            ]
        );
        $this->root_directory = vfsStream::url(self::ROOTDIRNAME);
    }

    /**
     * @covers \SimpleSAML\Utils\System::getOS
     * @test
     */
    public function testGetOSBasic()
    {
        $res = System::getOS();

        $this->assertInternalType("int", $res);
    }

    /**
     * @covers \SimpleSAML\Utils\System::resolvePath
     * @test
     */
    public function testResolvePathRemoveTrailingSlashes()
    {
        $base = "/base////";
        $path = "test";

        $res = System::resolvePath($path, $base);
        $expected = "/base/test";

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\System::resolvePath
     * @test
     */
    public function testResolvePathPreferAbsolutePathToBase()
    {
        $base = "/base/";
        $path = "/test";

        $res = System::resolvePath($path, $base);
        $expected = "/test";

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\System::resolvePath
     * @test
     */
    public function testResolvePathCurDirPath()
    {
        $base = "/base/";
        $path = "/test/.";

        $res = System::resolvePath($path, $base);
        $expected = "/test";

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\System::resolvePath
     * @test
     */
    public function testResolvePathParentPath()
    {
        $base = "/base/";
        $path = "/test/child/..";

        $res = System::resolvePath($path, $base);
        $expected = "/test";

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\System::resolvePath
     * @test
     */
    public function testResolvePathAllowsStreamWrappers()
    {
        $base = '/base/';
        $path = 'vfs://simplesaml';

        $res = System::resolvePath($path, $base);
        $expected = $path;

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\System::resolvePath
     * @test
     */
    public function testResolvePathAllowsAwsS3StreamWrappers()
    {
        $base = '/base/';
        $path = 's3://bucket-name/key-name';

        $res = System::resolvePath($path, $base);
        $expected = $path;

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\System::writeFile
     * @test
     */
    public function testWriteFileInvalidArguments()
    {
        $this->setExpectedException('\InvalidArgumentException');
        System::writeFile(null, null, null);
    }

    /**
     * @covers \SimpleSAML\Utils\System::writeFile
     * @test
     */
    public function testWriteFileBasic()
    {
        $tempdir = $this->root_directory.DIRECTORY_SEPARATOR.self::DEFAULTTEMPDIR;
        $config = $this->setConfigurationTempDir($tempdir);

        $filename = $this->root_directory.DIRECTORY_SEPARATOR.'test';

        System::writeFile($filename, '');

        $this->assertFileExists($filename);

        $this->clearInstance($config, '\SimpleSAML\Configuration');
    }

    /**
     * @covers \SimpleSAML\Utils\System::writeFile
     * @test
     */
    public function testWriteFileContents()
    {
        $tempdir = $this->root_directory.DIRECTORY_SEPARATOR.self::DEFAULTTEMPDIR;
        $config = $this->setConfigurationTempDir($tempdir);

        $filename = $this->root_directory.DIRECTORY_SEPARATOR.'test';
        $contents = 'TEST';

        System::writeFile($filename, $contents);

        $res = file_get_contents($filename);
        $expected = $contents;

        $this->assertEquals($expected, $res);

        $this->clearInstance($config, '\SimpleSAML\Configuration');
    }

    /**
     * @covers \SimpleSAML\Utils\System::writeFile
     * @test
     */
    public function testWriteFileMode()
    {
        $tempdir = $this->root_directory.DIRECTORY_SEPARATOR.self::DEFAULTTEMPDIR;
        $config = $this->setConfigurationTempDir($tempdir);

        $filename = $this->root_directory.DIRECTORY_SEPARATOR.'test';
        $mode = 0666;

        System::writeFile($filename, '', $mode);

        $res = $this->root->getChild('test')->getPermissions();
        $expected = $mode;

        $this->assertEquals($expected, $res);

        $this->clearInstance($config, '\SimpleSAML\Configuration');
    }

    /**
     * @covers \SimpleSAML\Utils\System::getTempDir
     * @test
     */
    public function testGetTempDirBasic()
    {
        $tempdir = $this->root_directory.DIRECTORY_SEPARATOR.self::DEFAULTTEMPDIR;
        $config = $this->setConfigurationTempDir($tempdir);

        $res = System::getTempDir();
        $expected = $tempdir;

        $this->assertEquals($expected, $res);
        $this->assertFileExists($res);

        $this->clearInstance($config, '\SimpleSAML\Configuration');
    }

    /**
     * @covers \SimpleSAML\Utils\System::getTempDir
     * @test
     */
    public function testGetTempDirNonExistant()
    {
        $tempdir = $this->root_directory.DIRECTORY_SEPARATOR.'nonexistant';
        $config = $this->setConfigurationTempDir($tempdir);

        $res = System::getTempDir();
        $expected = $tempdir;

        $this->assertEquals($expected, $res);
        $this->assertFileExists($res);

        $this->clearInstance($config, '\SimpleSAML\Configuration');
    }

    /**
     * @requires OS Linux
     * @covers \SimpleSAML\Utils\System::getTempDir
     * @test
     */
    public function testGetTempDirBadOwner()
    {
        if (!function_exists('posix_getuid')) {
            static::markTestSkipped('POSIX-functions not available;  skipping!');
        }

        $bad_uid = posix_getuid() + 1;

        $tempdir = $this->root_directory.DIRECTORY_SEPARATOR.self::DEFAULTTEMPDIR;
        $config = $this->setConfigurationTempDir($tempdir);

        chown($tempdir, $bad_uid);

        $this->setExpectedException('\SimpleSAML\Error\Exception');
        System::getTempDir();

        $this->clearInstance($config, '\SimpleSAML\Configuration');
    }
    private function setConfigurationTempDir($directory)
    {
        $config = Configuration::loadFromArray([
            'tempdir' => $directory,
        ], '[ARRAY]', 'simplesaml');

        return $config;
    }

    protected function clearInstance($service, $className)
    {
        $reflectedClass = new \ReflectionClass($className);
        $reflectedInstance = $reflectedClass->getProperty('instance');
        $reflectedInstance->setAccessible(true);
        $reflectedInstance->setValue($service, null);
        $reflectedInstance->setAccessible(false);
    }
}
