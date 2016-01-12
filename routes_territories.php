<?php


$app->group('/territories', function(){
	
	// GET /territories
	$this->get('', function($request, $response){
		
		$data = R::findAll('territory');
		
		$territories = array();
		foreach ($data as $t) {
			$terr = $t->export();
			$terr['total_people'] = 0;
			$terr['total_buildings'] = 0;

			foreach ($t->ownBuildingList as $b) {
				$terr['total_people'] += $b->countOwn( 'person' );
			}
			$terr['total_buildings'] = $t->countOwn( 'building' );
			$territories[] = $terr;
		}
		// usort($territories, cmpByKey('total_people', 'DESC'));
		return $this->view->render($response, 'territories.twig', [
			'territories' => $territories,
			'messages' => $this->flash->getMessages(),
		]);
	})->setName('territories');

	//Create a new territories
	$this->post('', function( $request, $response ){
		try {
			$formdata = $request->getParsedBody();
			
			$territory = R::dispense('territory');
			$territory->title = $formdata['title'];
			$territory->created_at = R::isoDate();
			
			$id = R::store($territory);
		}
		catch (Exception $e) {
			$this->flash->addMessage( 'fail', $e->getMessage() );
			$response = $response->withHeader('X-Status-Reason', $e->getMessage());
			return $this->view->render($response->withStatus(400), 'territory_add.twig');
		}
		
		$this->flash->addMessage('success', 'Added territory '. $territory->title );
		$addBuilding = $this->router->pathFor('territory_buildings_add', [ 'id' => $id ] );
		return $response->withRedirect( $addBuilding );
	});	
	// territories/add
	$this->get('/add', function($request, $response){
		try {
			$territories = R::findAll('territory');
		} catch (Exception $e) {
			$this->messages->addMessage( $e->getMessage() );
			return $response->withRedirect( $request->getUri() );
		}
		return $this->view->render($response, 'territory_add.twig', [
			'territories' => R::exportAll( $territories ),
			'messages' => $this->flash->getMessages(),
		]);		
	})->setName('territory_add');

});

// // territory grouping
$app->group('/territories/{id:[0-9]+}', function(){


	// View one (1) territory 
	// GET /territory/123 
	$this->get('', function($request, $response, $args){
		
		$buildings = array();

		try {
			$territory = R::load('territory', $args['id']);
			foreach ($territory->ownBuildingList as $b) {
				$building = $b->export();
				$building['total_people'] = count( $b->ownPersonList );
				$buildings[] = $building;
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

	// Update territory information
	// UPDATE /territories/123
	$this->put('', function($request, $response, $args){
		$formdata = $request->getParsedBody();
		try {
			// if ( empty($formdata['title']) ){
			// 	$this->flash->addMessage('fail','Territory title can not be blank.');
			// 	$response = $response->withRedirect( $request->getUri() );
			// }
			// else {
				$territory = R::load('territory', $args['id']);
				$old_title = $territory->title;
				$territory->title = $formdata['title']; 	// Update title
				$territory->last_updated = R::isoDate(); 	// Update Date
				R::store($territory); 						// Save

				$this->flash->addMessage('success', "Territory title changed from " . $old_title . " to " . $formdata['title'] );
			// }
		}
		catch(ValidationException $e){
			$this->flash->addMessage('fail', $e->getMessage() );
			$response = $response->withRedirect( $this->router->pathFor('territory_edit', [ 'id' => $args['id'] ]));
		}
		catch(Exception $e){
			$response = $response->withStatus(400);
			$response = $response->withHeader('X-Status-Reason', $e->getMessage());
		}

		return $response->withRedirect( $request->getUri() );

	});

	// territories/[id]/buildings/add
	$this->get('/buildings/add', function($request, $response, $args){
		
		$territory = R::load('territory', $args['id']);
		$buildings = $territory->ownBuildingList;
		// foreach ($buildings as $b) {
		// 	$people[] = $b->ownPersonList;
		// }		


		return $this->view->render($response, "territory_buildings_add.twig", [
			// $type => $item,
			'territory' => $territory,
			'buildings' => $buildings,
			'messages' => $this->flash->getMessages(),
		]);

	})->setName("territory_buildings_add");

	// Add building to territory
	// POST territories/123/buildings
	$this->post('/buildings', function($request, $response, $args){

		$formdata = $request->getParsedBody();

		$territory = R::load('territory', $args['id']);
		$address = $formdata['address'];
		$city = $formdata['city'];
		$province = $formdata['province'];

		if( !empty( $address ) ){
			$address = filter_var( $address, FILTER_SANITIZE_STRING );

			$building = R::dispense('building');

			$building->address = $address;
			$building->city = $city;
			$building->province = $province;
			$building->last_updated = R::isoDate();

			$territory->ownBuildingList[] = $building;

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

	// View complete details of territory,
	// GET territories/123/details
	$this->get('/details', function($request, $response, $args){
		$territory = R::load('territory', $args['id']);
		$buildings = $territory->ownBuildingList;
		foreach ($buildings as $b) {
			$people[] = $b->ownPersonList;
			// echo count($b->ownPersonList); 
		}
		
		usort($buildings, function($a, $b){
			return count($a->ownPersonList) < count($b->ownPersonList);
		});

		// print_p( $territory );
		return $this->view->render($response, 'territory_details.twig', [
			'buildings' => $buildings,
			'territory' => $territory,
			'messages' => $this->flash->getMessages(),
		]);
	})->setName('territory_details');


	// Get editing form for territory
	// GET /territores/123/edit
	$this->get('/edit', function($request, $response, $args){
		$type = 'territory';
		$item = R::load($type, $args['id']);
		$buildings = $item->ownBuildingList;

		return $this->view->render($response, "{$type}_edit.twig", [
			$type => $item,
			'messages' => $this->flash->getMessages(),
		]);
	})->setName("territory_edit");	

});