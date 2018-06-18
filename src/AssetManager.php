<?php namespace Tekton\Assets;

use Tekton\Support\Contracts\Manifest;
use Tekton\Assets\AssetsQueue;

class AssetManager
{
    use \Tekton\Support\Traits\LibraryWrapper;

    protected $manifest;
    protected $queues = [];

    function __construct(Manifest $manifest = null)
    {
        $this->setManifest($manifest);
    }

    function setManifest(Manifest $manifest)
    {
        $this->manifest = $this->library = $manifest;
    }

    function getManifest()
    {
        return $this->manifest;
    }

    function queue(string $id = 'default')
    {
        if (! isset($this->queues[$id])) {
            $this->queues[$id] = new AssetQueue($id);
        }

        return $this->queues[$id];
    }
}
