<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Asset\ModuleAssetPublisher;
use SimpleSAML\Command\AssetsPublishCommand;
use SimpleSAML\Configuration;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

use function file_put_contents;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(AssetsPublishCommand::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ModuleAssetPublisher::class)]
final class AssetsPublishCommandTest extends TestCase
{
    private string $baseDir;

    private CommandTester $commandTester;


    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/ssp-assets-publish-command-' . uniqid();
        mkdir($this->baseDir . '/modules', 0777, true);
        mkdir($this->baseDir . '/public/assets', 0777, true);

        Configuration::clearInternalState();
        Configuration::loadFromArray([
            'basedir' => $this->baseDir,
        ], '[ARRAY]', 'simplesaml');

        $command = new AssetsPublishCommand(new ModuleAssetPublisher());
        $this->commandTester = new CommandTester($command);
    }


    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->baseDir);
        Configuration::clearInternalState();
    }


    public function testCanCreateInstanceWithDefaultConstructor(): void
    {
        $command = new AssetsPublishCommand();
        $this->assertInstanceOf(AssetsPublishCommand::class, $command);
    }


    public function testCommandMetadata(): void
    {
        $command = new AssetsPublishCommand();
        $this->assertSame('assets:publish', $command->getName());
        $this->assertSame('Publish module assets into public/assets.', $command->getDescription());
    }


    public function testExecuteOutputsSuccessWhenNoAssetsNeededPublishing(): void
    {
        $this->commandTester->execute([]);
        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('No module assets needed publishing.', $output);
    }


    public function testExecuteOutputsSuccessWhenAssetsArePublished(): void
    {
        mkdir($this->baseDir . '/modules/example/public/assets/css', 0777, true);
        mkdir($this->baseDir . '/modules/admin/public/assets/js', 0777, true);
        file_put_contents($this->baseDir . '/modules/example/public/assets/css/style.css', 'body {}');
        file_put_contents($this->baseDir . '/modules/admin/public/assets/js/script.js', 'console.log();');

        $this->commandTester->execute([]);
        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Published module assets for:', $output);
        $this->assertStringContainsString('admin', $output);
        $this->assertStringContainsString('example', $output);
    }
}
