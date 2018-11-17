<?php namespace Tekton\Assets;

use Iterator;
use Closure;
use Tekton\Assets\Asset;
use Tekton\Assets\AssetManager;
use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\Implementations\StringSort;

class AssetQueue implements Iterator
{
    protected $id;
    protected $rootUrl;
    protected $rootPath;
    protected $resolver;
    protected $compiler;
    protected $queue = [];
    protected $sorted = false;

    public function __construct(string $id, string $rootPath, string $rootUrl, array $items = [], callable $resolver = null, AssetCompiler $compiler = null)
    {
        $this->id = $id;
        $this->rootPath = $rootPath;
        $this->rootUrl = $rootUrl;
        $this->compiler = $compiler;

        if (is_null($resolver)) {
            $this->resolver = function ($asset) {
                return $asset;
            };
        }
        else {
            $this->resolver = Closure::fromCallable($resolver);
        }

        foreach ($items as $key => $val) {
            if (! $val instanceof Asset) {
                $uri = $this->resolve($val['uri'] ?? $val);
                $deps = $val['dependencies'] ?? [];
                $data = $val['data'] ?? [];

                $val = new Asset($uri, $this->rootPath, $this->rootUrl, $deps, $data, $this->compiler);
            }

            $this->add($key, $val);
        }
    }

    protected function resolve($asset)
    {
        $resolver = $this->resolver;
        return $resolver($asset);
    }

    public function getId()
    {
        return $this->id;
    }

    public function rewind()
    {
        if (! $this->sorted) {
            $this->sort();
        }

        return reset($this->queue);
    }

    public function current()
    {
        return current($this->queue);
    }

    public function key() {
        return key($this->queue);
    }

    public function next()
    {
        return next($this->queue);
    }

    public function valid()
    {
        return key($this->queue) !== null;
    }

    public function add(string $id, $asset, array $deps = [], array $data = [])
    {
        $this->sorted = false;

        if ($asset instanceof Asset) {
            $this->queue[$id] = $asset;
        }
        else {
            $this->queue[$id] = new Asset($this->resolve($asset), $this->rootPath, $this->rootUrl, $deps, $data, $this->compiler);
        }

        return $this;
    }

    public function injectDependency(string $id, $deps)
    {
        $this->sorted = false;
        $this->queue[$id]->addDependency($deps);

        return $this;
    }

    public function remove(string $id)
    {
        $this->sorted = false; // Only since a required dependency might now be missing

        if (isset($this->queue[$id])) {
            $val = $this->queue[$id];
            unset($this->queue[$id]);
            return $val;
        }

        return null;
    }

    public function clear()
    {
        $this->queue = [];
    }

    public function isEmpty()
    {
        return empty($this->queue);
    }

    public function get(string $id)
    {
        return (isset($this->queue[$id])) ? $this->queue[$id] : null;
    }

    public function all()
    {
        if (! $this->sorted) {
            $this->sort();
        }

        return $this->queue;
    }

    protected function sort($items = null)
    {
        // Don't sort if the queue is empty
        if ($this->isEmpty()) {
            return $this->sorted = true;
        }

        // Use the sorted list of dependencies to order the queue
        $order = $this->getSortOrder($this->queue);
        $this->queue = array_replace(array_flip($order), $this->queue);
        $this->sorted = true;
    }

    public function getSortOrder($items)
    {
        // Set up sorter
        $sorter = new StringSort();

        foreach ($items as $id => $item) {
            $sorter->add($id, $item->getDependencies());
        }

        // If a dependency is not found the sorter throws an exception. In some cases,
        // like with Wordpress, dependencies are loaded in another system (e.g. jquery)
        // and we want to be able to depend on it without crashing the site. So what we do
        // is that if a dependency is missing we simply remove it until we have an ordered set
        // of dependencies and trust that the other are included from somewhere else.
        try {
            return $sorter->sort();
        }
        catch (ElementNotFoundException $e) {
            $dep = $e->getTarget();

            // Remove unresolvable dependency from all dependency lists
            foreach ($items as $item) {
                $item->removeDependency($dep);
            }

            // Redo it until we have a resolvable set of ordered dependencies
            return $this->getSortOrder($items);
        }
    }

    public function compile($filters = [], $combine = false, $tests = [])
    {
        if ($this->compiler) {
            $result = $this->compiler->compile($this, (array) $filters, $combine, (array) $tests);

            return ($combine) ? reset($result) : $result;
        }

        return [];
    }
}
