<?php

$app->get('/signouts', function ($request, $response) {
	
	$messages['fail'][] = 'View not yet implemented';	
	return $this->view->render($response->withStatus(404), 'error.twig', [
		'messages' => $messages,
		'error_detail' => $this->flash->getMessages(),
	]);
})->setName('signouts');

$app->get('/signouts/{id:[0-9]+}/{auth:secret}', function ($request, $response) {
	$this->flash->addMessage('404', 'View not yet implemented');
	return $response->withRedirect( $this->router->pathFor('error') );
})->setName('signout');
