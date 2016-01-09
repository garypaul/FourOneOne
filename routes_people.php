<?php 

// // ******************
// // PEOPLE ROUTES      
// // ******************
$app->group('/people/{id:[0-9]+}', function(){


	$this->get('/edit', function($request, $response, $args){

		$item = R::load( 'person', $args['id'] );
		return $this->view->render($response, 'person_edit.twig', [
			'person' => $item,
			'messages' => $this->flash->getMessages(),
		]);
	})->setName('person_edit');

	// UPDATE /people/1/edit
	$this->put('/edit', function($request, $response, $args){

		$formdata = $request->getParsedBody();

		$p = $formdata['person'];

		try {
			if (empty($p)) throw new Exception("Nothing to submit");
			$person = R::load( 'person', $args['id'] );

			$differences = array_diff_assoc($p, $person->export());
			if( empty($differences) ) throw new Exception("Nothing changed");
			foreach ($differences as $key => $value) {
				$person[$key] = $value;
			}

			R::store( $person );

			$this->flash->addMessage('success', 'Updated ' . $p['name'] );
			return $response->withRedirect( $request->getUri() );
			
		} catch (Exception $e) {
			$this->flash->addMessage('fail', $e->getMessage());
			return $response->withRedirect( $request->getUri() );
		}
	});
});

$app->get('/people', function($request, $response){

	$items = R::findAll('person');

	usort( $items, cmpByKey('address') );

	return $this->view->render($response, 'people.twig', [
		'people' => $items,
		'messages' => $this->flash->getMessages(),
	]);
})->setName('people');