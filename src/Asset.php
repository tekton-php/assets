<?php namespace Tekton\Assets;

use Tekton\Assets\AssetManager;
use Tekton\Assets\AssetCompiler;

class Asset
{
    protected $uri;
    protected $deps;
    protected $data;
    protected $type;
    protected $rootPath;
    protected $rootUrl;

    public function __construct(string $uri, string $rootPath, string $rootUrl, array $deps = [], array $data = [], AssetCompiler $compiler = null)
    {
        $this->uri = $uri;
        $this->rootPath = $rootPath;
        $this->rootUrl = $rootUrl;
        $this->deps = $deps;
        $this->data = $data;
        $this->compiler = $compiler;

        $this->type = pathinfo($this->uri, PATHINFO_EXTENSION);
    }

    public function __toString()
    {
        return $this->uri();
    }

    public function uri()
    {
        return $this->uri;
    }

    public function path()
    {
        return $this->rootPath.DS.$this->uri;
    }

    public function url()
    {
        return $this->rootUrl.'/'.$this->uri;
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

    public function getDependencies()
    {
        return $this->deps;
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

    public function compile($filters = [], $tests = [])
    {
        if ($this->compiler) {
            $result = $this->compiler->compile($this, (array) $filters, false, (array) $tests);

            if (! empty($result)) {
                return reset($result);
            }
        }

        return false;        
    }
}
