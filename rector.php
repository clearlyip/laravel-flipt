<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\FuncCall\ClassOnObjectRector;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/config', __DIR__ . '/src'])
    ->withSkip([
        ClassOnObjectRector::class, //Unsupported in Prettier
    ])
    ->withPhpSets();
