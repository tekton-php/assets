<?php namespace Tekton\Assets;

use Iterator;
use Tekton\Assets\AssetManager;
use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\Implementations\StringSort;

class AssetQueue implements Iterator
{
    protected $queue = [];
    protected $sorted = false;

    function __construct(string $id, array $items = [])
     {
        foreach ($items as $key => $val) {
            $this->add($key, $val['asset'] ?? $val, $val['dependencies'] ?? [], $val['data'] ?? []);
        }
    }

    function rewind()
    {
        if (! $this->sorted) {
            $this->sort();
        }

        return reset($this->queue);
    }

    function current()
    {
        return current($this->queue);
    }

    function key() {
        return key($this->queue);
    }

    function next()
    {
        return next($this->queue);
    }

    function valid()
    {
        return key($this->queue) !== null;
    }

    function add(string $id, string $asset, array $deps = [], array $data = [])
    {
        $this->sorted = false;

        $this->queue[$id] = [
            'asset' => $asset,
            'dependencies' => $deps,
            'data' => $data,
        ];

        return $this;
    }

    function injectDependency(string $id, $deps)
    {
        $this->sorted = false;
        $this->queue[$id]['dependencies'] = array_merge($this->queue[$id]['dependencies'], (array) $deps);

        return $this;
    }

    function remove(string $id)
    {
        $this->sorted = false; // Only since a required dependency might now be missing

        if (isset($this->queue[$id])) {
            $val = $this->queue[$id];
            unset($this->queue[$id]);
            return $val;
        }

        return null;
    }

    function clear()
    {
        $this->queue = [];
    }

    function isEmpty()
    {
        return empty($this->queue);
    }

    function get(string $id)
    {
        return (isset($this->queue[$id])) ? $this->queue[$id] : null;
    }

    function all()
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

    function getSortOrder($items)
    {
        // Set up sorter
        $sorter = new StringSort();

        foreach ($items as $id => $item) {
            $sorter->add($id, $item['dependencies']);
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
            foreach ($items as $id => $item) {
                if (($key = array_search($dep, $items[$id]['dependencies'])) !== false) {
                    unset($items[$id]['dependencies'][$key]);
                }
            }

            // Redo it until we have a resolvable set of ordered dependencies
            return $this->getSortOrder($items);
        }
    }
}
