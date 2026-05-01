<?php declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;


	public static function createRouter(): RouteList
	{
		$router = new RouteList();

        $router->withModule('Login')
            ->addRoute('login/', 'Login:default')
            ->addRoute('logout/', 'Login:logout');

        $router->withModule('Admin')
            ->addRoute('partner[/<action>[/<id>]]', 'Partner:default')
            ->addRoute('agency[/<action>[/<id>]]', 'Agency:default')
            ->addRoute('missing-registry[/<action>[/<id>]]', 'MissingRegistry:default')
            ->addRoute('proposal[/<action>[/<id>]]', 'Proposal:default')
            ->addRoute('<presenter>[/<action>[/<id>]]', 'Home:default')
            ->addRoute('', 'Home:default');


        $router->addRoute('home/<presenter>/<action>', 'Home:default');

		return $router;
	}
}
