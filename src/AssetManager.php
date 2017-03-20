<?php namespace Tekton\Assets;

use Tekton\Support\Contracts\Manifest;

class AssetManager {
    // protected $manifestPath;
    protected $manifest;
    protected $srcBase;
    protected $targetBase;
    protected $root;

    function __construct(Manifest $manifest, $root = '', $srcBase = '', $targetBase = '') {
        // Define ds
        if ( ! defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        $this->setManifest($manifest);
        $this->setSrcBase($srcBase);
        $this->setTargetBase($targetBase);
        $this->setRoot($root);
    }

    function setManifest(Manifest $manifest) {
        $this->manifest = $manifest;
    }

    function setSrcBase($path) {
        $this->srcBase = $path;
    }

    function setTargetBase($path) {
        $this->targetBase = $path;
    }

    function setRoot($path) {
        $this->root = $path;
    }

    function get($asset) {
        $srcUri = ( ! empty($this->root)) ? $this->root.DS.$this->srcBase : $this->srcBase;
        $targetUri = ( ! empty($this->root)) ? $this->root.DS.$this->targetBase : $this->targetBase;

        // If defined in manifest, return the revisioned file
        if ($this->manifest->has($asset)) {
            return $targetUri.DS.$this->manifest->get($asset);
        }
        else {
            $compiledPath = $targetUri.DS.$asset;
            $compiledAbsPath = realpath($compiledPath);

            // If not defined in manifest, see if it exists in target dir
            if (file_exists($compiledAbsPath)) {
                return $compiledPath;
            }
        }

        // Return original asset path
        return $srcUri.DS.$asset;
    }
}
