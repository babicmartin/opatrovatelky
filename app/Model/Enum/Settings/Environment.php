<?php declare(strict_types=1);

namespace App\Model\Enum\Settings;

enum Environment: string
{
    case DEVELOPMENT = 'development';
    case PRODUCTION = 'production';
}
