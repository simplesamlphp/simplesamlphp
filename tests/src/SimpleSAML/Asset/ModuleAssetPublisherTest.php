<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Asset;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Asset\ModuleAssetPublisher;

use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(ModuleAssetPublisher::class)]
class ModuleAssetPublisherTest extends TestCase
{
    public function testPublishCopiesAssetsAndRemovesStaleFiles(): void
    {
        $baseDir = sys_get_temp_dir() . '/ssp-asset-publisher-' . uniqid();
        mkdir($baseDir . '/modules/example/public/assets/css', 0777, true);
        mkdir($baseDir . '/modules/empty', 0777, true);
        mkdir($baseDir . '/public/assets/example/css', 0777, true);
        mkdir($baseDir . '/public/assets/empty', 0777, true);

        file_put_contents($baseDir . '/modules/example/public/assets/css/site.css', 'body{}');
        file_put_contents($baseDir . '/public/assets/example/css/stale.css', 'old');
        file_put_contents($baseDir . '/public/assets/empty/stale.css', 'old');

        $publisher = new ModuleAssetPublisher();
        $published = $publisher->publish($baseDir);

        $this->assertEquals(['example'], $published);
        $this->assertSame('body{}', file_get_contents($baseDir . '/public/assets/example/css/site.css'));
        $this->assertFalse(is_dir($baseDir . '/public/assets/empty'));
        $this->assertFileDoesNotExist($baseDir . '/public/assets/example/css/stale.css');
    }
}
