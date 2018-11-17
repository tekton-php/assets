<?php namespace Tekton\Assets;

class AssetManifest extends \Tekton\Support\CachedManifest
{
    protected $srcBase;
    protected $targetBase;
    protected $root;
    protected $cwd;

    public function __construct(string $path, string $cacheDir, array $manifest = [], string $root = '', string $srcBase = '', string $targetBase = '')
    {
        $this->setSrcBase($srcBase);
        $this->setTargetBase($targetBase);
        $this->setRoot($root);
        $this->cwd = getcwd();

        parent::__construct($path, $cacheDir, $manifest);
    }

    public function setCwd(string $cwd)
    {
        $this->cwd = $cwd;
    }

    public function getCwd()
    {
        return $this->cwd;
    }

    public function setSrcBase(string $path)
    {
        $this->srcBase = $path;
    }

    public function getSrcBase()
    {
        return $this->srcBase;
    }

    public function setTargetBase(string $path)
    {
        $this->targetBase = $path;
    }

    public function getTargetBase()
    {
        return $this->targetBase;
    }

    public function setRoot(string $path)
    {
        $this->root = $path;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function get(string $asset, $default = null)
    {
        $srcUri = rtrim(((! empty($this->root)) ? $this->root.DS.$this->srcBase : $this->srcBase), DS);
        $targetUri = rtrim(((! empty($this->root)) ? $this->root.DS.$this->targetBase : $this->targetBase), DS);

        // If no default value is set we return the orginally requested asset
        $default = $default ?? $srcUri.DS.$asset;

        // If defined in manifest, return the revisioned file
        if ($this->exists($asset)) {
            return $targetUri.DS.parent::get($asset);
        }
        else {
            $compiledUri = $targetUri.DS.$asset;
            $compiledPath = $this->cwd.DS.$compiledUri;

            // If not defined in manifest, see if it exists in target dir
            if (file_exists($compiledPath)) {
                return $compiledUri;
            }
        }

        // Return default
        return $default;
    }
}
