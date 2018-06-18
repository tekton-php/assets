<?php namespace Tekton\Assets;

class AssetManifest extends \Tekton\Support\CachedManifest
{
    protected $srcBase;
    protected $targetBase;
    protected $root;
    protected $cwd;

    function __construct(string $path, string $cacheDir, array $manifest = [], string $root = '', string $srcBase = '', string $targetBase = '')
    {
        // Define ds
        if ( ! defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        $this->setSrcBase($srcBase);
        $this->setTargetBase($targetBase);
        $this->setRoot($root);
        $this->cwd = getcwd();

        parent::__construct($path, $cacheDir, $manifest);
    }

    function setCwd(string $cwd)
    {
        $this->cwd = $cwd;
    }

    function getCwd()
    {
        return $this->cwd;
    }

    function setSrcBase(string $path)
    {
        $this->srcBase = $path;
    }

    function getSrcBase()
    {
        return $this->srcBase;
    }

    function setTargetBase(string $path)
    {
        $this->targetBase = $path;
    }

    function getTargetBase()
    {
        return $this->targetBase;
    }

    function setRoot(string $path)
    {
        $this->root = $path;
    }

    function getRoot()
    {
        return $this->root;
    }

    function get(string $asset, $default = null)
    {
        $srcUri = (! empty($this->root)) ? $this->root.DS.$this->srcBase : $this->srcBase;
        $targetUri = (! empty($this->root)) ? $this->root.DS.$this->targetBase : $this->targetBase;

        // If no default value is set we return the orginally requested asset
        $default = (is_null($default)) ? $srcUri.DS.$asset : $default;

        // If defined in manifest, return the revisioned file
        if ($this->exists($asset)) {
            return $targetUri.DS.parent::get($asset);
        }
        else {
            $compiledPath = $targetUri.DS.$asset;
            $compiledAbsPath = $this->cwd.DS.$compiledPath;

            // If not defined in manifest, see if it exists in target dir
            if (file_exists($compiledAbsPath)) {
                return $compiledPath;
            }
        }

        // Return default
        return $default;
    }
}