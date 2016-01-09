<?php 

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/inc/RedBean/rb.php';
require __DIR__ . '/inc/utils.php';
require __DIR__ . '/inc/Exceptions.php';
require __DIR__ . '/Models/models.php';

date_default_timezone_get('America/Vancouver');
// Create and configure Slim app
session_cache_limiter(false);
session_start();

// Setup database connection
R::setup('sqlite:/' . __DIR__ . '/data/fouroneone.db');
//R::debug( TRUE );
R::setAutoResolve( TRUE );  
// R::freeze(true); // Freeze DB schema in production

$config = [
	'settings' => [
		'displayErrorDetails' => true,
	],
];


$c = new \Slim\Container($config);
$app = new \Slim\App($c);

$app->add(function (Request $request, Response $response, callable $next) {
	$uri = $request->getUri();
	$path = $uri->getPath();
	if ($path != '/' && substr($path, -1) == '/') {
		// permanently redirect paths with a trailing slash
		// to their non-trailing counterpart
		$uri = $uri->withPath(substr($path, 0, -1));
		return $response->withRedirect((string)$uri, 301);
	}

	return $next($request, $response);
});


// Fetch DI Container
$container = $app->getContainer();

//R::wipe('territory');

// Register Twig View helper
$container['view'] = function ($c) {
	$view = new \Slim\Views\Twig('templates', [
		// 'cache' => dirname(__FILE__) .'/cache',
		'debug' => true
	]);

	// Instantiate and add Slim specific extension
	$view->addExtension(new Slim\Views\TwigExtension(
		$c['router'],
		$c['request']->getUri()
	));

	return $view;
};

// Register Flash provider
$container['flash'] = function($c){
	return new \Slim\Flash\Messages();
};