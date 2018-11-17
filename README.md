Tekton Assets
=============

An asset manager that supports asset manifests, queues and compilation/minification.

## Installation

```sh
composer require tekton/assets
```

## Usage

### Basic

The AssetManager and AssetCompiler can be used separately but are designed to work together to make asset management and processing as simple and pain free as possible.

To use it, create an instance of AssetManager and provide the filesystem and web root for your application.

```php
require 'vendor/autoload.php';

use Tekton\Assets\AssetManager;

$manager = new AssetManager(__DIR__, 'http://localhost:8000/');

// Get file path and URL
$path = $manager->get('images/logo.png')->path();
$url = $manager->get('images/logo.png')->url();
```

Queues are a way to group different assets together similarily to how WordPress handles assets. Dependencies can be defined and the resulting list will be sorted in order.

```php
use Tekton\Assets\AssetManager;

$manager->queue('scripts')->add('jquery', 'js/jquery.min.js');
$manager->queue('scripts')->add('app', 'js/app.js', ['jquery']);

// Output every script
foreach ($manager->queue('scripts')->all() as $asset) {
    echo '<script src="'.$asset->url().'"></script>';
}
```

If assets are revisioned for cache busting as a build system step you can load the manifest and the AssetManager will first try and resolve the asset through the manifest before checking the filesystem. An AssetManifest require a cache directory so that JSON/YML files aren't parsed every request.

```php
use Tekton\Assets\AssetManifest;

$manager->setManifest(new AssetManifest('manifest.json', 'cache/'));
```

### Compilation

The AssetManager also supports configuring an AssetCompiler that enables processing of files server side.

```php
use Tekton\Assets\AssetCompiler;
use Tekton\Assets\Filters\MinifyFilter;

$compiler = new AssetCompiler('cache/', 'http://localhost:8000/cache/');
$compiler->registerFilter('minify', new MinifyFilter);

$manager->setCompiler($compiler);

// Compile single file
echo '<link href="'.$manager->get('css/main.css')->compile('minify')->url().'">';

// Compile queue
$manager->queue('styles')->add('one', 'css/one.css');
$manager->queue('styles')->add('two', 'css/two.css', ['one']);

foreach ($manager->queue('styles')->compile('minify') as $asset) {
    echo '<link href="'.$asset->url().'">';
}

// OR combine queue into single file
echo '<link href="'.$manager->queue('styles')->compile('minify', true)->url().'">';
```

For whenever cache needs to be invalidated by testing more than just one source file you can add an array of files to check for changes.

```php
use Tekton\Assets\Filters\ScssFilter;

$compiler->registerFilter('scss', new ScssFilter);

// Compile SCSS and test all SCSS files for changes
echo '<link href="'.$manager->get('scss/main.scss')->compile(['scss', 'minify'], glob('scss/*.scss'))->url().'">';

// For queues the additional paths to test are the third argument
$manager->queue('styles')->add('vendor', 'css/vendor.css');
$manager->queue('styles')->add('app', 'scss/main.scss', ['vendor']);

foreach ($manager->queue('styles')->compile('scss', false, glob('scss/*.scss')) as $asset) {
    echo '<link href="'.$asset->url().'">';
}
```

### Performance

As much as possible is cached to avoid filesystem interactions and recompilation. But for production it's advisable to disable file modification time checks (especially if you're compiling SCSS with many additional files to test for changes) and only compile if resulting file is missing. Then you'll have to empty the cache manually or as a post-deployment step.

```php
$compiler->setIgnoreCacheTime(true);

// After deployment...
$compiler->clearCache();
```

## License

MIT
