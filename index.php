<?php

include __DIR__ . '/bootstrap.php';

// Define home route
$app->get('/', function ($request, $response) {
	return $this->view->render($response, 'about.twig', [
		//'name' => $args['name']
	]);
})->setName('home');

include __DIR__ . '/routes_territories.php';

include __DIR__ . '/routes_buildings.php';

include __DIR__ . '/routes_people.php';

// $app->get('/contact', function ($request, $response) {
	// return $this->view->render($response, 'contact.twig', [
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