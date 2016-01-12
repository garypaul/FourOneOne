<?php 

// // ******************
// // PEOPLE ROUTES      
// // ******************
$app->group('/people/{id:[0-9]+}', function(){
	$this->map(['PUT', 'DELETE'], '', function($request, $response, $args){
		try {
			
			$person = R::load('person', $args['id']);
			$person_export = $person->export();

			if( $request->isPut() ){
				$formdata = $request->getParsedBody();
				$edited_person = $formdata['person'];

				if (empty($p)) throw new Exception("Nothing to submit");
				
				// Find the delta of changes
				$differences = array_diff_assoc($edited_person, $person_export);

				if( empty($differences) ) throw new Exception("Nothing changed");

				// Update changed fields
				foreach ($differences as $key => $value) { $person[$key] = $value; }

				// save updated person
				R::store( $person );

				$this->flash->addMessage('success', 'Updated ' . $person['name'] );
			}
			else if( $request->isDelete() ){
				// delete person
				R::trash( $person );
				$this->flash->addMessage('success', 'Baleeted ' . $person_export['name'] );
			}

		} catch (Exception $e) {
			$this->flash->addMessage( 'fail', $e->getMessage() );
			return $response->withRedirect( $this->router->pathFor('person_edit', $person->id ) );
		}
		// Success, redirect to the building
		return $response->withRedirect( $this->router->pathFor('building', ['id' => $person->building_id] ) . "?pid={$person->id}" );	

	})->setName('person');

	$this->get('/edit', function($request, $response, $args){

		$item = R::load( 'person', $args['id'] );
		return $this->view->render($response, 'person_edit.twig', [
			'person' => $item,
			'messages' => $this->flash->getMessages(),
		]);
	})->setName('person_edit');

});

$app->get('/people', function($request, $response){

	$items = R::findAll('person');

	usort( $items, cmpByKey('address') );

	return $this->view->render($response, 'people.twig', [
		'people' => $items,
		'messages' => $this->flash->getMessages(),
	]);
})->setName('people');

$app->post('/people', function($request, $response){
	try {
		$new_person = $request->getParsedBody()['person'];

		if(empty( $new_person['building_id'] ) || $new_person['building_id'] == 0 ) throw new Exception("Person must be added to a valid building");

		$person = R::dispense('person');
		
		$differences = array_diff_assoc($new_person, $person->export());
		
		if( empty($differences) ) throw new Exception("Nothing changed");
		foreach ($differences as $key => $value) {
			$person[$key] = $value;
		}

		$id = R::store( $person );
		$this->flash->addMessage('success', "Added {$person->name} to building");
		
	} catch (Exception $e) {
		$this->flash->addMessage('fail', $e->getMessage());
		return $response->withRedirect( $this->router->pathFor('error') );
	}
	// echo 'SAVED';
	// print_p( $building->ownPersonList ); die();
	return $response->withRedirect( $this->router->pathFor('building', [ 'id' => $person->building_id ]) . "?pid={$id}" ); 

});