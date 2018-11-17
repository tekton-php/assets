<?php namespace Tekton\Assets\Filters;

use Tekton\Assets\Filters\AbstractFilter;
use Leafo\ScssPhp\Compiler;
use Exception;

class ScssFilter extends AbstractFilter
{
    public $scss;
    protected $types;

    public function __construct($types = ['scss', 'sass'])
    {
        $this->types = $types;
        $this->scss = new Compiler();
    }

    public function getResultingType(string $type)
    {
        if (in_array($type, $this->types)) {
            return 'css';
        }

        return $type;
    }

    public function process(string $type, $content, string $path = '')
    {
        if (in_array($type, $this->types)) {
            try {
                $scss = clone $this->scss;

                if (! empty($path)) {
                    $scss->addImportPath(dirname($path));
                }

                $content = $scss->compile($content);
            }
            catch (Exception $e) {
                // Do nothing
            }

            return $content;
        }

        return $content;
    }
}
