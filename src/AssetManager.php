<?php namespace Tekton\Assets;

use Tekton\Support\Contracts\Manifest;
use Tekton\Assets\AssetsQueue;

class AssetManager
{
    use \Tekton\Support\Traits\LibraryWrapper;

    protected $manifest;
    protected $queues = [];

    public function __construct(Manifest $manifest = null)
    {
        $this->setManifest($manifest);
    }

    public function setManifest(Manifest $manifest)
    {
        $this->manifest = $this->library = $manifest;
    }

    public function getManifest()
    {
        return $this->manifest;
    }

    public function queue(string $id = 'default')
    {
        if (! isset($this->queues[$id])) {
            $this->queues[$id] = new AssetQueue($id);
        }

        return $this->queues[$id];
    }
}
