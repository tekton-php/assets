<?php namespace Tekton\Assets\Filters;

interface FilterInterface
{
    public function getResultingType(string $type);

    public function process(string $type, $content, string $path = '');
}
