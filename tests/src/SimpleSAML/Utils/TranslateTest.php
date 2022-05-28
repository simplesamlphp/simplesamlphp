<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Tests for SimpleSAML\Utils\Translate.
 *
 * @covers \SimpleSAML\Utils\Translate
 */
class TranslateTest extends TestCase
{
    protected Utils\System $sysUtils;

    protected Utils\Translate $translate;

    protected Filesystem $filesystem;

    protected string $tempdir;

    public function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->sysUtils = new Utils\System();

        do {
            $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . mt_rand();
        } while (!@mkdir($tmp, 0700));
        $this->tempdir = $tmp;

        $this->translate = new Utils\Translate();
    }

    /**
     * @test
     */
    public function testCompileAllTemplatesMain(): void
    {
        $workdir = $this->tempdir . DIRECTORY_SEPARATOR . "testall";
        $this->translate->compileAllTemplates('', $workdir);
        $this->checkAllFilesAreCompiledTemplates($workdir);
    }

    /**
     * @test
     */
    public function testCompileAllTemplatesModule(): void
    {
        $workdir = $this->tempdir . DIRECTORY_SEPARATOR . "testadmin";
        $this->translate->compileAllTemplates('admin', $workdir);
        $this->checkAllFilesAreCompiledTemplates($workdir);
    }

    public function tearDown(): void
    {
        $this->filesystem->remove($this->tempdir);
    }

    protected function checkAllFilesAreCompiledTemplates(string $dir): void
    {
        $finder = new Finder();
        $finder->files()->in($dir);

        $this->assertTrue($finder->hasResults());

        foreach ($finder as $file) {
            $this->assertEquals('php', $file->getExtension());
            $this->assertStringContainsString('class __TwigTemplate', $file->getContents());
        }
    }
}
