<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AssetReferencesTest extends TestCase
{
    public function test_literal_asset_references_resolve_to_public_files(): void
    {
        $missingAssets = [];
        $projectRoot = dirname(__DIR__, 2);

        foreach ([$projectRoot.'/resources/views', $projectRoot.'/app'] as $sourceDirectory) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDirectory)
            );

            foreach ($files as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());
                preg_match_all("/asset\\(\\s*['\"]([^'\"]+)['\"]\\s*\\)/", $contents, $matches);

                foreach ($matches[1] as $asset) {
                    if (! is_file($projectRoot.'/public/'.$asset)) {
                        $missingAssets[] = $file->getPathname().': '.$asset;
                    }
                }
            }
        }

        $this->assertSame([], $missingAssets, implode(PHP_EOL, $missingAssets));
    }

    public function test_view_assets_use_their_actual_avif_format(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        foreach (['assets/View2.avif', 'assets/View3.avif'] as $asset) {
            $contents = file_get_contents($projectRoot.'/public/'.$asset);

            $this->assertStringContainsString('ftypavif', substr($contents, 0, 16));
        }
    }
}
