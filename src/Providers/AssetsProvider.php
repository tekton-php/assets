<?php namespace Tekton\Assets\Providers;

use Illuminate\Support\ServiceProvider;
use Tekton\Assets\AssetManager;
use Tekton\Assets\AssetManifest;

class AssetsProvider extends ServiceProvider
{
    function provides()
    {
        return ['assets'];
    }

    function register()
    {
        $config = $this->app['config'];

        // Get asset manager paths from config
        $source = $config->get('assets.source', 'assets');
        $compiled = $config->get('assets.compiled', 'dist');
        $root = $config->get('assets.root', cwd_rel_path(app_path()));
        $cwd = $config->get('assets.cwd', cwd());
        $manifestPath = $cwd.DS.$root.DS.$config->get('assets.manifest', 'dist'.DS.'manifest.json');

        // Configure cache
        $cacheDir = ensure_dir_exists($this->app->path('cache').DS.'assets');
        $cachePath = $cacheDir.DS.'manifest.php';
        $this->app->registerPath('cache.assets', $cachePath);

        // Create manifest
        $manifest = new AssetManifest($manifestPath, $cachePath, [], $root, $source, $compiled);
        $manifest->setCwd($config->get('assets.cwd', cwd()));

        // Register the AssetManager
        $this->app->singleton('assets', function() use ($manifest) {
            return $manager = new AssetManager($manifest);
        });
    }
}
