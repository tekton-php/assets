<?php namespace Tekton\Assets\Filters;

use Tekton\Assets\Filters\FilterInterface;

abstract class AbstractFilter implements FilterInterface
{
    abstract public function getResultingType(string $type);

    abstract public function process(string $type, $content, string $path = '');
}
