<?php namespace Tekton\Assets;

use Exception;
use InvalidArgumentException;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Tekton\Assets\Asset;
use Tekton\Assets\AssetQueue;
use Tekton\Assets\Filters\FilterInterface;
use Tekton\Support\Manifest;

class AssetCompiler
{
    protected $cacheUrl;
    protected $cachePath;
    protected $cacheMap = [];
    protected $cacheMapPath;
    protected $ignoreCacheTime = false;
    protected $assetMapPath;
    protected $assetMap = [];
    protected $filters = [];

    public function __construct(string $cachePath, string $cacheUrl)
    {
        $this->setCacheUrl($cacheUrl);
        $this->setCachePath($cachePath);
        $cachePath = $this->getCachePath();

        $this->cacheMapPath = $cachePath.DS.'_cache-map.php';
        $this->assetMapPath = $cachePath.DS.'_asset-map.php';

        if (! file_exists($cachePath)) {
            mkdir($cachePath, 0775, true);
        }

        if (file_exists($this->assetMapPath)) {
            $this->assetMap = include $this->assetMapPath;
        }
        if (file_exists($this->cacheMapPath)) {
            $this->cacheMap = include $this->cacheMapPath;
        }
    }

    public function setCachePath($path)
    {
        $this->cachePath = rtrim($path, DS);

        return $this;
    }

    public function getCachePath()
    {
        return $this->cachePath;
    }

    public function setCacheUrl($url)
    {
        $this->cacheUrl = rtrim($url, '/');

        return $this;
    }

    public function getCacheUrl()
    {
        return $this->cacheUrl;
    }

    public function getIgnoreCacheTime()
    {
        return $this->ignoreCacheTime;
    }

    public function setIgnoreCacheTime($ignore)
    {
        $this->ignoreCacheTime = (bool) $ignore;

        return $this;
    }

    public function getAssetMap()
    {
        return $this->assetMap;
    }

    public function saveAssetManifest($path, $format = 'php')
    {
        return (new Manifest('', $this->assetMap))->write($path, $format);
    }

    protected function saveAssetMap()
    {
        return write_object_to_file($this->assetMapPath, $this->assetMap);
    }

    protected function saveCacheMap()
    {
        return write_object_to_file($this->cacheMapPath, $this->cacheMap);
    }

    public function registerFilter(string $id, FilterInterface $filter)
    {
        $this->filters[$id] = $filter;
    }

    public function clearCache()
    {
        $this->assetMap = [];
        $this->cacheMap = [];

        $di = new RecursiveDirectoryIterator($this->getCachePath(), FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($ri as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        return $this;
    }

    public function getLastCacheUpdate()
    {
        return max(array_column($this->cacheMap ?: [['mtime' => false]], 'mtime'));
    }

    public function validateCache(string $uri, string $path)
    {
        if (! isset($this->assetMap[$uri]) || ! isset($this->cacheMap[$path])) {
            return false;
        }

        if (! $this->ignoreCacheTime) {
            foreach ($this->cacheMap[$path]['tests'] as $test) {
                if (filemtime($test) > $this->cacheMap[$path]['mtime']) {
                    return false;
                }
            }
        }

        return true;
    }

    public function compile($asset, $filters = [], $combine = false, $tests = [])
    {
        $cacheMap = $this->cacheMap;
        $assetMap = $this->assetMap;
        $result = [];
        $filters = (array) $filters;
        $tests = (array) $tests;

        if ($asset instanceof AssetQueue) {
            $assets = $asset->all();
        }
        else {
            $assets = (! is_array($asset)) ? [$asset] : $asset;
        }

        // Combine all files into single output
        if ($combine) {
            $combined = '';
            $type = '';

            // Create unique identifier
            if ($asset instanceof AssetQueue) {
                $uri = $asset->getId();
            }
            else {
                $uri = crc32(var_export(array_map(function($asset) {
                    return $asset->uri();
                }, $assets), true));
            }

            $uri = '_combined.'.$uri;

            // Only compile if cache is invalid
            if (! $this->validateCache($uri, $uri)) {
                // Add paths to tests array
                foreach ($assets as $key => $asset) {
                    $tests[] = $asset->path();
                }

                // Reset cacheMap entry
                $this->cacheMap[$uri] = [
                    'mtime' => 0,
                    'tests' => $tests,
                ];

                // Process all files
                foreach ($assets as $key => $asset) {
                    $path = $asset->path();
                    $type = $asset->type();

                    // Validate file
                    if (! file_exists($path)) {
                        throw new Exception('Asset file not found: '.$path);
                    }
                    else {
                        $path = realpath($path);
                    }

                    // Pass content to filters
                    $content = file_get_contents($path);

                    foreach ($filters as $id) {
                        if (isset($this->filters[$id])) {
                            $filter = $this->filters[$id];
                            $type = $filter->getResultingType($type);
                            $content = $filter->process($type, $content, $path);
                        }
                    }

                    $combined .= $content.PHP_EOL;
                }

                // Save combined file
                $cachePath = $this->getCachePath().DS.$uri;
                $cachePath .= '.'.uniqid();
                $cachePath .= '.'.$type;

                // Write compiled file to cache
                if (! file_put_contents($cachePath, $combined)) {
                    throw new Exception('Failed to write cache file: '.$cachePath);
                }

                // Update maps
                $result[$uri] = $cachePath;
                $this->cacheMap[$uri]['mtime'] = filemtime($cachePath);
                $this->assetMap[$uri] = $result[$uri];
            }
            else {
                // If it's already been compiled we return the asset uri
                $result[$uri] = $this->assetMap[$uri];
            }
        }
        // Process each file on its own
        else {
            foreach ($assets as $key => $asset) {
                $uri = $asset->uri();
                $path = $asset->path();
                $type = $asset->type();

                // Validate file
                if (! file_exists($path)) {
                    throw new Exception('Asset file not found: '.$path);
                }
                else {
                    $path = realpath($path);
                }

                // Only compile if cache is invalid
                if (! $this->validateCache($uri, $path)) {
                    // Reset cacheMap entry
                    $this->cacheMap[$path] = [
                        'mtime' => 0,
                        'tests' => array_merge([$path], $tests),
                    ];

                    // Pass content to filters
                    $result[$uri] = null;
                    $content = file_get_contents($path);
                    $cachePathBase = $this->getCachePath().DS.pathinfo($path, PATHINFO_FILENAME);
                    $cachePath = $cachePathBase.'.'.$type;

                    foreach ($filters as $id) {
                        if (isset($this->filters[$id])) {
                            $filter = $this->filters[$id];
                            $content = $filter->process($type, $content, $path);

                            $type = $filter->getResultingType($type);
                            $cachePath = $cachePathBase;
                            $cachePath .= '.'.filemtime($path);
                            $cachePath .= '.'.$type;
                        }
                    }

                    // Write compiled file to cache
                    if (! file_put_contents($cachePath, $content)) {
                        throw new Exception('Failed to write cache file: '.$cachePath);
                    }

                    // Update maps
                    $result[$uri] = $cachePath;
                    $this->cacheMap[$path]['mtime'] = filemtime($cachePath);
                    $this->assetMap[$uri] = $result[$uri];
                }
                else {
                    // If it's already been compiled we return the asset uri
                    $result[$uri] = $this->assetMap[$uri];
                }
            }
        }

        // If maps have been changed save them
        if ($this->cacheMap != $cacheMap) {
            $this->saveCacheMap();
        }
        if ($this->assetMap != $assetMap) {
            $this->saveAssetMap();
        }

        // Return array with all assets that have been compiled
        foreach ($result as $key => $path) {
            $uri = ltrim(str_replace($this->getCachePath(), '', $path), DS);
            $result[$key] = new Asset($uri, $this->getCachePath(), $this->getCacheUrl(), [], [], $this);
        }

        return $result;
    }
}
