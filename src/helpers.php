<?php

if (! function_exists('asset'))
{
    function asset($file)
    {
        return app('assets')->get($file);
    }
}
