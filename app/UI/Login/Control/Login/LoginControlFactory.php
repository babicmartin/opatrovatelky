<?php declare(strict_types=1);

namespace App\UI\Login\Control\Login;

interface LoginControlFactory
{
    public function create(callable $onSuccess): LoginControl;
}