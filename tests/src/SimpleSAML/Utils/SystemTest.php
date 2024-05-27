<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use org\bovigo\vfs\{vfsStream, vfsStreamDirectory};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Utils;

/**
 * Tests for SimpleSAML\Utils\System.
 */
#[CoversClass(Utils\System::class)]
class SystemTest extends TestCase
{
    private const ROOTDIRNAME = 'testdir';

    private const DEFAULTTEMPDIR = 'tempdir';

    /** @var \org\bovigo\vfs\vfsStreamDirectory */
    protected VfsStreamDirectory $root;

    /** @var string */
    protected $root_directory;

    /** @var \SimpleSAML\Utils\System */
    protected $sysUtils;


    /**
     */
    public function setUp(): void
    {
        $this->root = vfsStream::setup(
            self::ROOTDIRNAME,
            null,
            [
                self::DEFAULTTEMPDIR => [],
            ],
        );
        $this->root_directory = vfsStream::url(self::ROOTDIRNAME);
        $this->sysUtils = new Utils\System();
    }


    /**
     */
    public function testGetOSBasic(): void
    {
        $res = $this->sysUtils->getOS();

        $this->assertIsInt($res);
    }


    /**
     */
    public function testResolvePathRemoveTrailingSlashes(): void
    {
        $base = "/base////";
        $path = "test";

        $res = $this->sysUtils->resolvePath($path, $base);
        $expected = "/base/test";

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testResolvePathPreferAbsolutePathToBase(): void
    {
        $base = "/base/";
        $path = "/test";

        $res = $this->sysUtils->resolvePath($path, $base);
        $expected = "/test";

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testResolvePathCurDirPath(): void
    {
        $base = "/base/";
        $path = "/test/.";

        $res = $this->sysUtils->resolvePath($path, $base);
        $expected = "/test";

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testResolvePathParentPath(): void
    {
        $base = "/base/";
        $path = "/test/child/..";

        $res = $this->sysUtils->resolvePath($path, $base);
        $expected = "/test";

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testResolvePathAllowsStreamWrappers(): void
    {
        $base = '/base/';
        $path = 'vfs://simplesaml';

        $res = $this->sysUtils->resolvePath($path, $base);
        $expected = $path;

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testResolvePathAllowsAwsS3StreamWrappers(): void
    {
        $base = '/base/';
        $path = 's3://bucket-name/key-name';

        $res = $this->sysUtils->resolvePath($path, $base);
        $expected = $path;

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testWriteFileBasic(): void
    {
        $tempdir = $this->root_directory . DIRECTORY_SEPARATOR . self::DEFAULTTEMPDIR;
        $config = $this->setConfigurationTempDir($tempdir);

        $filename = $this->root_directory . DIRECTORY_SEPARATOR . 'test';

        $this->sysUtils->writeFile($filename, '');

        $this->assertFileExists($filename);

        $this->clearInstance($config, Configuration::class);
    }


    /**
     */
    public function testWriteFileContents(): void
    {
        $tempdir = $this->root_directory . DIRECTORY_SEPARATOR . self::DEFAULTTEMPDIR;
        $config = $this->setConfigurationTempDir($tempdir);

        $filename = $this->root_directory . DIRECTORY_SEPARATOR . 'test';
        $contents = 'TEST';

        $this->sysUtils->writeFile($filename, $contents);

        $res = file_get_contents($filename);
        $expected = $contents;

        $this->assertEquals($expected, $res);

        $this->clearInstance($config, Configuration::class);
    }


    /**
     */
    public function testWriteFileMode(): void
    {
        $tempdir = $this->root_directory . DIRECTORY_SEPARATOR . self::DEFAULTTEMPDIR;
        $config = $this->setConfigurationTempDir($tempdir);

        $filename = $this->root_directory . DIRECTORY_SEPARATOR . 'test';
        $mode = 0666;

        $this->sysUtils->writeFile($filename, '', $mode);

        $res = $this->root->getChild('test')->getPermissions();
        $expected = $mode;

        $this->assertEquals($expected, $res);

        $this->clearInstance($config, Configuration::class);
    }


    /**
     */
    public function testGetTempDirBasic(): void
    {
        $tempdir = $this->root_directory . DIRECTORY_SEPARATOR . self::DEFAULTTEMPDIR;
        $config = $this->setConfigurationTempDir($tempdir);

        $res = $this->sysUtils->getTempDir();
        $expected = $tempdir;

        $this->assertEquals($expected, $res);
        $this->assertFileExists($res);

        $this->clearInstance($config, Configuration::class);
    }


    /**
     */
    public function testGetTempDirNonExistent(): void
    {
        $tempdir = $this->root_directory . DIRECTORY_SEPARATOR . 'nonexistent';
        $config = $this->setConfigurationTempDir($tempdir);

        $res = $this->sysUtils->getTempDir();
        $expected = $tempdir;

        $this->assertEquals($expected, $res);
        $this->assertFileExists($res);

        $this->clearInstance($config, Configuration::class);
    }


    /**
     */
    public function testGetTempDirBadPermissions(): void
    {
        $tempdir = $this->root_directory . DIRECTORY_SEPARATOR . self::DEFAULTTEMPDIR;
        $config = $this->setConfigurationTempDir($tempdir);

        chmod($tempdir, 0440);

        $this->expectException(Error\Exception::class);
        $this->sysUtils->getTempDir();

        $this->clearInstance($config, Configuration::class);
    }


    /**
     * @param string $directory
     * @return \SimpleSAML\Configuration
     */
    private function setConfigurationTempDir(string $directory): Configuration
    {
        $config = Configuration::loadFromArray([
            'tempdir' => $directory,
        ], '[ARRAY]', 'simplesaml');

        return $config;
    }


    /**
     * @param \SimpleSAML\Configuration $service
     * @param class-string $className
     */
    protected function clearInstance(Configuration $service, string $className): void
    {
        $reflectedClass = new ReflectionClass($className);
        $reflectedInstance = $reflectedClass->getProperty('instance');
        $reflectedInstance->setAccessible(true);
        $reflectedInstance->setValue($service, []);
        $reflectedInstance->setAccessible(false);
    }
}
