<?php namespace Tekton\Assets;

use Tekton\Support\Contracts\Manifest;
use Tekton\Assets\AssetsQueue;
use Tekton\Assets\AssetsCompiler;

class AssetManager
{
    protected $rootUrl = '';
    protected $rootPath = '';
    protected $manifest = null;
    protected $compiler = null;
    protected $queues = [];

    public function __construct(string $rootPath, string $rootUrl, Manifest $manifest = null, AssetCompiler $compiler = null)
    {
        $this->setRootPath($rootPath);
        $this->setRootUrl($rootUrl);

        if (! is_null($compiler)) {
            $this->setCompiler($compiler);
        }
        if (! is_null($manifest)) {
            $this->setManifest($manifest);
        }
    }

    public function setRootPath($path)
    {
        $this->rootPath = rtrim($path, DS);

        return $this;
    }

    public function getRootPath()
    {
        return $this->rootPath;
    }

    public function setRootUrl($url)
    {
        $this->rootUrl = rtrim($url, '/');

        return $this;
    }

    public function getRootUrl()
    {
        return $this->rootUrl;
    }

    public function setCompiler(AssetCompiler $compiler)
    {
        $this->compiler = $compiler;

        return $this;
    }

    public function getCompiler()
    {
        return $this->compiler;
    }

    public function setManifest(Manifest $manifest)
    {
        $this->manifest = $manifest;

        return $this;
    }

    public function getManifest()
    {
        return $this->manifest;
    }

    public function get($asset)
    {
        if ($uri = $this->resolve($asset)) {
            return new Asset($uri, $this->rootPath, $this->rootUrl, [], [], $this->compiler);
        }

        return null;
    }

    public function resolve($asset)
    {
        if ($this->manifest && $uri = $this->manifest->get($asset)) {
            return $uri;
        }
        elseif (file_exists($this->rootPath.DS.($uri = $asset))) {
            return $uri;
        }

        return false;
    }

    public function exists($asset)
    {
        if ($this->manifest && $this->manifest->exists($asset)) {
            return true;
        }
        elseif (file_exists($this->rootPath.DS.$asset)) {
            return true;
        }

        return false;
    }

    public function queue(string $id = 'default')
    {
        if (! isset($this->queues[$id])) {
            $this->queues[$id] = new AssetQueue($id, $this->rootPath, $this->rootUrl, [], [$this, 'resolve'], $this->compiler);
        }

        return $this->queues[$id];
    }
}
