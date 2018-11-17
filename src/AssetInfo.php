<?php namespace Tekton\Assets;

use Tekton\Assets\Asset;

class AssetInfo
{
    protected $asset;

    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    public function uri()
    {
        return $this->uri;
    }

    public function path()
    {
        return $this->manager->fsRoot.DS.$this->uri;
    }

    public function url()
    {
        return $this->manager->webRoot.'/'.$this->uri;
    }

    public function type()
    {
        return $this->type;
    }

    public function setData($key, $val = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->data[$k] = $v;
            }
        }
        else {
            $this->data[$key] = $val;
        }

        return $this;
    }

    public function removeData($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }

        return $this;
    }

    public function clearData()
    {
        $this->data = [];

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function addDependency($deps)
    {
        $this->deps = array_merge($this->deps, (array) $deps);

        return $this;
    }

    public function removeDependency(string $dep)
    {
        if ($key = array_search($dep, $this->deps)) {
            unset($this->deps[$key]);
        }

        return $this;
    }

    public function clearDependencies($deps)
    {
        $this->deps = [];

        return $this;
    }

    public function compile()
    {
        $compiler = $this->manager->getCompiler();

        if (! $compiler instanceof AssetCompiler) {
            return false;
        }

        return $compiler->compile($this);
    }
}
