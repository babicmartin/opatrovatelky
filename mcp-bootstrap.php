<?php declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
return fn() => App\Bootstrap::boot()->createContainer();
