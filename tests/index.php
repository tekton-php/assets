<?php require '../vendor/autoload.php';

use Tekton\Assets\AssetCompiler;
use Tekton\Assets\AssetManager;
use Tekton\Assets\AssetManifest;
use Tekton\Assets\Filters\MinifyFilter;
use Tekton\Assets\Filters\ScssFilter;

$compiler = new AssetCompiler($cacheDir = __DIR__.DS.'cache', 'http://localhost:8000/cache');
$compiler->registerFilter('minify', new MinifyFilter);
$compiler->registerFilter('scss', new ScssFilter);
$compiler->clearCache();

$manager = new AssetManager(__DIR__, 'http://localhost:8000/', null, $compiler);

// Compile single file
echo $manager->get('js/index.js')->compile('minify')->path().PHP_EOL;

// Compile queue
$manager->queue('styles')->add('one', 'css/one.css', ['two']);
$manager->queue('styles')->add('two', 'css/two.css');

foreach ($manager->queue('styles')->compile('minify', true) as $asset) {
    echo $asset->path().PHP_EOL;
}

// Compile array of assets
foreach ($compiler->compile([$manager->get('css/one.css'), $manager->get('css/two.css')], 'minify', true) as $asset) {
    echo $asset->path().PHP_EOL;
}

// Save manifest
$compiler->saveAssetManifest($manifestPath = $cacheDir.DS.'manifest.json', 'json');

// Use manifest
$manifest = new AssetManifest($manifestPath, $cacheDir.'/');
$manifest->setRoot('sub');
$assetManager = new AssetManager(__DIR__, 'http://localhost:8000/', $manifest, $compiler);
echo $assetManager->get('sub.css')->path().PHP_EOL;

// Use scss
echo $manager->get('scss/main.scss')->compile(['scss', 'minify'])->path().PHP_EOL;
