<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// If you need some environment variables for bootstrapping
// you can load them here if they are not loaded in app/Bootstrap.php
// But app/Bootstrap.php loads Dotenv, so we can use it.

return App\Bootstrap::boot()
	->createContainer();
