<?php 

// // ******************
// // PEOPLE ROUTES      
// // ******************
$app->group('/people/{id:[0-9]+}', function(){

	$this->put( '', function($request, $response, $args){

		//print_p( $request->getParsedBody() ); die();

		$person = null;
		try {
			
			$person = R::load('person', $args['id']);
			$person_export = $person->export();
			
			//$formdata = ;
			$request_body = $request->getParsedBody();
			// print_p($request_body); die();
			// form data is held inside a person array
			if( $request->isXhr() ){
				//$edited_person = $request->getQueryParams();
			}
			else {
				$edited_person = $request_body['person'];
			}



			if ( empty( $edited_person ) ) throw new Exception("Nothing to submit");
			
			// Find the delta of changes
			$differences = array_diff_assoc($edited_person, $person_export);

			if( empty($differences) ) throw new Exception("Nothing changed");

			// Update changed fields
			foreach ($differences as $key => $value) { $person[$key] = $value; }

			// save updated person
			//R::store( $person );
			
			$success_msg = 'Updated ' . $person['name'];
			
			if( $request->isXhr() ){

				//$body = new StringStream(json_encode(['data' => $success_msg]));
				echo json_encode(['person' => [
				    'success' => $success_msg,
				    'id' => $args['id'],
				]]);

				$ajax_response = $response
					->withStatus(200)
					->withHeader('Content-Type', 'application/json');

				return $ajax_response;
			} else {
				$this->flash->addMessage('success', $success_msg );
				return $response->withRedirect( $this->router->pathFor('building', ['id' => $person->building_id] ) . "?pid={$person->id}" );					
			}


		} catch (Exception $e) {

			if( $request->isXhr() ){
				//$body = new StringStream(json_encode(['data' => $success_msg]));
				echo json_encode(['person' => [
				    'fail' => $e->getMessage(),
				    'id' => $args['id'],
				]]);

				$ajax_response = $response
					->withStatus(400)
					->withHeader('Content-Type', 'application/json');

				return $ajax_response;				
			} else {
				$this->flash->addMessage( 'fail', $e->getMessage() );
				return $response->withRedirect( $this->router->pathFor('person_edit', ['id' => $args['id'] ] ) );				
			}
		}
		// Success, redirect to the building
		

	})->setName('person');

	// delete person
	$this->delete( '', function( $request, $response, $args ){
		try {
			// Get Person from DB
			$person = R::load('person', $args['id'] );
			
			// Export to manageable array
			$person_export = $person->export();

			// temp store building id
			$b_id = $person->building_id; 

			// print_p( $body ); die();
			$person->published = false;
			R::store( $person );
			// DELETE IT
			// R::trash( $person );

			$success_msg = 'Baleeted ' . $person_export['name'];

			$this->flash->addMessage('success', $success_msg );
			return $response->withRedirect( $this->router->pathFor('building', ['id' => $b_id] ) . "?pid={$p_id}" );	

		} catch (Exception $e) {
			$this->flash->addMessage( 'fail', $e->getMessage() );
			return $response->withRedirect( $this->router->pathFor('person_edit', ['id' => $args['id'] ] ) );			
		}
	});


	// URI : /people/123/edit
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
		// print_p( $new_person ); die();
		if(empty( $new_person['building_id'] ) || $new_person['building_id'] == 0 ) throw new Exception("Person must be added to a valid building");

		$person = R::dispense('person');
		
		$differences = array_diff_assoc($new_person, $person->export());
		
		if( empty($differences) ) throw new Exception("Nothing changed");
		foreach ($differences as $key => $value) {
			$person[$key] = $value;
		}

		$id = R::store( $person );
		$this->flash->addMessage('success', "Added {$person->name} ({$person->id}) to building $person->building_id ");
		
	} catch (Exception $e) {
		$this->flash->addMessage('fail', $e->getMessage());
		return $response->withRedirect( $this->router->pathFor('error') );
	}
	// echo 'SAVED';
	// print_p( $building->ownPersonList ); die();
	return $response->withRedirect( $this->router->pathFor('building', [ 'id' => $person->building_id ]) . "?pid={$id}" ); 

});