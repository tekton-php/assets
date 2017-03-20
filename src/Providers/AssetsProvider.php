<?php namespace Tekton\Assets\Providers;

use Tekton\Support\ServiceProvider;
use Tekton\Assets\AssetManager;
use Tekton\Support\CachedManifest;

class AssetsProvider extends ServiceProvider {

    function register() {
        $config = $this->app->make('config');

        // Get asset manager paths from config
        $manifestPath = $config->get('assets.manifest', 'dist'.DS.'manifest.json');
        $source = $config->get('assets.source', 'assets');
        $compiled = $config->get('assets.compiled', 'dist');
        $root = cwd_rel_path(app_path());

        // Configure cache
        $cacheDir = get_path('cache').DS.'manifest';
        $this->app->registerPath('cache.manifest', $cacheDir);

        if ( ! file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Create manifest
        $manifest = new CachedManifest('assets', $cacheDir, $manifestPath);

        // Register the AssetManager
        $this->app->singleton('assets', function() use ($manifest, $root, $source, $compiled) {
            return new AssetManager($manifest, $root, $source, $compiled);
        });
    }
}
