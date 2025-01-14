<?php

namespace Barryvdh\elFinderFlysystemDriver\Plugin;

use League\Flysystem\Filesystem;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Plugin\AbstractPlugin;
use League\Flysystem\Cached\CachedAdapter;

class HasDir extends AbstractPlugin
{

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var CachedAdapterInstance
     */
    protected $cachedAdapter = null;

    /**
     * @var Adapter has method hasDir()
     */
    private $hasMethod = false;

    /**
     * Set the Filesystem object.
     *
     * @param FilesystemInterface $filesystem
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        parent::setFilesystem($filesystem);

        if ( $filesystem instanceof Filesystem) {
            $this->adapter = $filesystem->getAdapter();

            // For a cached adapter, get the underlying instance
            if ($this->adapter instanceof CachedAdapter) {
                $this->cachedAdapter = $this->adapter;
                $this->adapter = $this->adapter->getAdapter();
            }

            //TODO: Check on actual implementations, not just an existing method
            $this->hasMethod = method_exists($this->adapter, 'hasDir');
        }

    }

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'hasDir';
    }

    /**
     * Get the public url
     *
     * @param string $path  path to file
     *
     * @return string|false
     */
    public function handle($path = null)
    {
        if (is_null($path)) {
            return $this->hasMethod;
        }

        if ( ! $this->hasMethod) {
            return false;
        }

        return $this->getFromMethod($path);
    }

    /**
     * Get the URL using a `hasDir()` method on the adapter.
     *
     * @param  string $path
     * @return string
     */
    protected function getFromMethod($path)
    {
        $res = false;
        if ($this->cachedAdapter) {
            $res = $this->cachedAdapter->getMetadata($path);
        }
        if (!$res || !isset($res['hasdir'])) {
            $res = $this->adapter->hasDir($path);
        }
        if (is_array($res)) {
            return isset($res['hasdir'])? $res['hasdir'] : true;
        } else {
            return $res;
        }
    }

}
