<?php

include __DIR__ . '/bootstrap.php';

// Define home route
$app->get('/', function ($request, $response) {
	return $this->view->render($response, 'about.twig', [
		'messages' => $this->flash->getMessages(),
	]);
})->setName('home');

$app->get('/error', function ($request, $response) {
	//$response = $response->withHeader('X-Status-Reason', $this->flash->getMessage('error'));
	print_p( $this->flash->getMessages() );
	return $this->view->render($response->withStatus(400), 'error.twig', [
		'messages' => $this->flash->getMessages(),
		'error_detail' => $this->flash->getMessages(),
	]);
})->setName('error');

include __DIR__ . '/routes_checkouts.php';

include __DIR__ . '/routes_territories.php';

include __DIR__ . '/routes_buildings.php';

include __DIR__ . '/routes_people.php';

// Run app
$app->run();