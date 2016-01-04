<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/inc/RedBean/rb.php';
require __DIR__ . '/inc/utils.php';
require __DIR__ . '/inc/Classes.php';

date_default_timezone_get('America/Vancouver');
// Create and configure Slim app
session_cache_limiter(false);
session_start();

// Setup database connection
R::setup();
// R::freeze(true); // Freeze DB schema in production

$configuration = [
	'settings' => [
		'displayErrorDetails' => true,
	],
];


$c = new \Slim\Container($configuration);
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

// Define named route
$app->get('/', function ($request, $response) {
	return $this->view->render($response, 'about.twig', [
		//'name' => $args['name']
	]);
})->setName('home');

// Get 1 building and display people
$app->get('/buildings/{id:[0-9]+}', function($request, $response, $args){
	//print_p($args);

	$building = R::load('building', $args['id']);
	$people = $building->ownPersonList;
	usort($people, 'cmpPeople');

	return $this->view->render($response, 'building.twig', [
		'building' => $building,
		'territory_title' => $building->territory->title,
		'people' => $people,
		'messages' => $this->flash->getMessages(),
	]);
})->setName('building');


$app->get('/territories', function($request, $response){

	$territories = R::findAll('territory');

	return $this->view->render($response, 'territories.twig', [
		'territories' => $territories,
		'messages' => $this->flash->getMessages(),
	]);
})->setName('territories');

//Add a territory
$app->post('/territories', function( $request, $response ){
	
	$postdata = $request->getParsedBody();
	
	$territory = R::dispense('territory');
	$territory->title = $postdata['title'];
	$territory->last_updated = R::isoDate();
	
	$id = R::store($territory);

	if( $id == 0 ){
		// Error. Didn't store territory. try again. Redirect to GET
		$this->flash->addMessage('fail', 'Could not write territory to database.');
		return $response->withRedirect( '/territories' , 301);
	} else {
		$this->flash->addMessage('success', 'Added territory '. $territory->title );
		$newItem = $this->router->pathFor('territory', [ 'id' => $id ] );
		return $response->withRedirect( $newItem , 301);
	}
});

$app->get('/territories/{id:[0-9]+}/details', function($request, $response, $args){
	$territory = R::load('territory', $args['id']);
	$buildings = $territory->ownBuildingList;
	foreach ($buildings as $b) {
		$people[] = $b->ownPersonList;
	}
	// print_p( $territory );
	return $this->view->render($response, 'territory_details.twig', [
		'territory' => $territory,
	]);
})->setName('territory_details');

//Get 1 territory
$app->get('/territories/{id:[0-9]+}', function($request, $response, $args){
	
	$buildings = array();

	try {
		$territory = R::load('territory', $args['id']);
		if( $territory->id == 0 ) {
			$territory = false;
			throw new ResourceNotFoundException('id ' . $args['id']. ' not found in database.');
		}
		else {
			$buildings = $territory->ownBuildingList;
			//print_p( $territory ); die();
		}
	}
	catch(ResourceNotFoundException $e){
		$response = $response->withStatus(404);
	}
	catch(Exception $e){
		$response = $response->withStatus(400);
		$response = $response->withHeader('X-Status-Reason', $e->getMessage());
	}
	return $this->view->render($response, 'territory.twig', [
		'messages' => $this->flash->getMessages(),
		'territory' => $territory,
		'buildings' => $buildings,
	]);
})->setName('territory');

// Add building to territory
$app->post('/territories/{id:[0-9]+}', function($request, $response, $args){

	$postdata = $request->getParsedBody();

	$territory = R::load('territory', $args['id']);
	$address = $postdata['address'];
	$city = $postdata['city'];
	$province = $postdata['province'];

	if( !empty( $address ) ){
		$address = filter_var( $address, FILTER_SANITIZE_STRING );

		$building = R::dispense('building');

		$building->address = $address;
		$building->city = $city;
		$building->province = $province;
		$building->last_updated = R::isoDate();

		$territory->xownBuildingList[] = $building;

		$id = R::store($territory);

		if( $id == 0 ){
			$this->flash->addMessage('fail', 'Could not write to database.');
			return $response->withRedirect( $request->getUri() );
		} else {
			$this->flash->addMessage('success', 'Added building: ' . $building->address);
			$newItem = $this->router->pathFor('building', [ 'id' => $building->id ] );
			return $response->withRedirect( $newItem );
		}

	} else {
		$this->flash->addMessage('fail', 'Please enter a valid address.');
		return $response->withRedirect( $request->getUri() );
	}
});

$app->get('/buildings', function($request, $response){
	//print_p($args);

	$buildings = R::findAll('building');
	return $this->view->render($response, 'buildings.twig', [
		'buildings' => $buildings,
		'messages' => $this->flash->getMessages(),
	]);
})->setName('buildings');

$app->get('/buildings/{id:[0-9]+}/people', function($request, $response, $args){
	echo 'Not yet implemented';
});

$app->post('/buildings/{id:[0-9]+}', function($request, $response, $args){
	
	$postdata = $request->getParsedBody();
	// print_p($postdata); die();
	$building = R::load( 'building', $args['id'] );

	if( !empty($postdata['form_get_people_by_address']) ){
		if( empty($postdata['address']) ){
			$this->flash->addMessage('fail', 'Please enter an address');
			return $response->withRedirect( $request->getUri() );
		}
		$new_people = getPeopleByAddress( $postdata['address'] );
		if( count($new_people) < 1 ){
			$this->flash->addMessage('fail', 'Sorry, no people could be retrieved at: ' . $postdata['address']);
			return $response->withRedirect( $request->getUri() );
		}
		
		return $this->view->render($response, 'building.twig', [
			'new_people' => $new_people, // people to add
			'building' => $building,
			'messages' => $this->flash->getMessages(),
			'people' => $building->ownPersonList,
		]);		
	}

	elseif ( $postdata['form_add_people']) {
		// Add people to building
		$new_people = $postdata['people'];
		usort($new_people, 'cmpPeople'); 
		foreach ($new_people as $p) {
			savePersonToBuilding( $p, $building );
		}
		// Save building
		R::store($building);
		$this->flash->addMessage('success', 'Added ' . count( $postdata['people'] ) . ' new people to building.');
		return $response->withRedirect( $request->getUri() );    
	}

	elseif ( !empty($postdata['form_add_person']) ) {
		// echo 'NOT YET';
		// print_p( $building->ownPersonList ); 
		savePersonToBuilding( $postdata['person'], $building );
		// echo 'ADDED:';
		// print_p( $building->ownPersonList ); 
		R::store( $building );
		// echo 'SAVED';
		// print_p( $building->ownPersonList ); die();
		$this->flash->addMessage('success', 'Added 1 person');
		return $response->withRedirect( $request->getUri() ); 
	}
	else {
		$this->flash->addMessage('fail', 'No action done.');
		return $this->view->render($response, 'building.twig', [
			'messages' => $this->flash->getMessages(),
		]);		
	}
});

// $app->get('/contact', function ($request, $response) {
// 	return $this->view->render($response, 'contact.twig', [
// 		'messages' => $this->flash->getMessages()
// 	]);
// })->setName('contact');

// $app->post('/contact', function ($request, $response, $args) {
// 	$post = $request->getParsedBody();

// 	$name = $post['name'];
// 	$email = $post['email'];
// 	$msg = $post['msg'];

// 	if( !empty($name) && !empty($email) && !empty($msg) ){
// 	   $cleanName   =  filter_var( $name, FILTER_SANITIZE_STRING );
// 	   $cleanEmail  =  filter_var($email, FILTER_SANITIZE_EMAIL );
// 	   $cleanName   =  filter_var(  $msg, FILTER_SANITIZE_STRING );
// 	} else {
// 		$this->flash->addMessage('fail', 'All fields are required.');
// 		//return $response->withStatus(301)->withHeader('Location', '/contact');
// 		return $response->withRedirect('/contact', 301);
// 	}
// });

// Run app
$app->run();