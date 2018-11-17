<?php namespace Tekton\Assets\Filters;

use Tekton\Assets\Filters\AbstractFilter;
use MatthiasMullie\Minify;

class MinifyFilter extends AbstractFilter
{
    protected $types;

    public function getResultingType(string $type)
    {
        return $type;
    }

    public function process(string $type, $content, string $path = '')
    {
        if ($type == 'js') {
            return (new Minify\JS)->add($content)->minify();
        }
        elseif ($type == 'css') {
            return (new Minify\CSS)->add($content)->minify();
        }

        return $content;
    }
}
