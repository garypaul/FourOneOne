<?php 
// Get 1 building and display people
$app->get('/buildings/{id:[0-9]+}', function($request, $response, $args){
	//print_p($args);

	$building = R::load('building', $args['id']);
	$people = $building->ownPersonList;
	usort($people, cmpByKey('unit') );

	return $this->view->render($response, 'building.twig', [
		'building' => $building,
		'territory_title' => $building->territory->title,
		'people' => $people,
		'messages' => $this->flash->getMessages(),
	]);
})->setName('building');


$app->get('/buildings/{id:[0-9]+}/edit', function($request, $response, $args){
	$type = 'building';
	$item = R::load($type, $args['id']);
	$people = $item->ownPersonList;

	return $this->view->render($response, "{$type}_edit.twig", [
		$type => $item,
		'messages' => $this->flash->getMessages(),
	]);

})->setName("building_edit");


$app->put('/buildings/{id:[0-9]+}/edit', function($request, $response, $args){
	$formdata = $request->getParsedBody();
	try {
		if ( save_entity_with_array( 'building', $formdata, $args['id'] ) ){
			$this->flash->addMessage('success', "Building address changed from " . $old . " to " . $formdata['address'] );
		}
		// if ( empty($formdata['address']) ){
		// 	$this->flash->addMessage('fail','Building address can not be blank.');
		// 	$response = $response->withRedirect( $request->getUri() );
		// }
		// else {
		// 	$building = R::load('building', $args['id']);
		// 	$old = $building->address;
		// 	$building->address = $formdata['address']; 	// Update title
		// 	$building->last_updated = R::isoDate(); 	// Update Date
		// 	R::store($building); 						// Save

		// 	$this->flash->addMessage('success', "Building address changed from " . $old . " to " . $formdata['address'] );
		// 	$response = $response->withRedirect( $request->getUri() );
		// }
	}
	catch(Exception $e){
		$response = $response->withStatus(400);
		$response = $response->withHeader('X-Status-Reason', $e->getMessage());
	}

	return $response;

})->setName('building_edit');




$app->get('/buildings', function($request, $response){
	//print_p($args);

	$buildings = R::findAll('building');
	return $this->view->render($response, 'buildings.twig', [
		'buildings' => $buildings,
		'messages' => $this->flash->getMessages(),
	]);
})->setName('buildings');

// Go out to the internet and find people!
$app->post('/buildings/{id:[0-9]+}/findpeople', function($request, $response, $args){
	$this->flash->addMessage('fail', '"/buildings/[id]/findpeople" not yet implemented.');
	return $response->withRedirect( $this->router->pathFor('error') );
});

$app->post('/buildings/{id:[0-9]+}', function($request, $response, $args){
	try {
		
		$formdata = $request->getParsedBody();
		// print_p($formdata); die();
		$building = R::load( 'building', $args['id'] );

		if( !empty($formdata['form_get_people_by_address']) ){
			// Make a Form to enter new people
			if( empty($formdata['address']) ){
				$this->flash->addMessage('fail', 'Please enter an address');
				return $response->withRedirect( $request->getUri() );
			}
			// Query 411.ca to get people
			$new_people = getPeopleByAddress( $formdata['address'] );
			
			if( count($new_people) < 1 ){
				$this->flash->addMessage('fail', 'Sorry, no people could be retrieved at: ' . $formdata['address']);
				return $response->withRedirect( $request->getUri() );
			}
			
			$people = $building->ownPersonList;
			usort($new_people, cmpByKey('phone'));

			return $this->view->render($response, 'building.twig', [
				'new_people' => $new_people, // people to add
				'building' => $building,
				'messages' => $this->flash->getMessages(),
				'people' => $people,
			]);		
		}

		elseif ( $formdata['form_add_people']) {
			// Add people to building
			$new_people = $formdata['people'];
			usort($new_people, cmpByKey('unit') ); 
			foreach ($new_people as $p) {
				$person = createEntityWithArray( 'person', $p );
				$building->xownPersonList[] = $person;
			}
			// Save building
			R::store($building);
			$this->flash->addMessage('success', 'Added ' . count( $formdata['people'] ) . ' new people to building.');
		}
	} catch (Exception $e) {
		$this->flash->addMessage( $e->getMessage() );
		return $response->withRedirect( $request->getUri() );
	}
	return $response->withRedirect( $request->getUri() );

	// elseif ( !empty($formdata['form_add_person']) ) {
	// 	// echo 'NOT YET';
	// 	// print_p( $building->ownPersonList ); 
	// 	savePersonToBuilding( $formdata['person'], $building );
	// 	// echo 'ADDED:';
	// 	// print_p( $building->ownPersonList ); 
	// 	R::store( $building );
	// 	// echo 'SAVED';
	// 	// print_p( $building->ownPersonList ); die();
	// 	$this->flash->addMessage('success', 'Added 1 person');
	// 	return $response->withRedirect( $request->getUri() ); 
	// }
	// else {
	// 	$this->flash->addMessage('fail', 'No action done.');
	// 	return $this->view->render($response, 'building.twig', [
	// 		'messages' => $this->flash->getMessages(),
	// 	]);		
	// }
});