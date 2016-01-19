<?php

$app->get('/checkouts', function ($request, $response) {
	
	$messages = $this->flash->getMessages();
	// $messages['fail'][] = 'View not yet implemented';
	$checkouts = R::findAll('checkout');
	foreach ($checkouts as $c) {
		$c->territory; // get the territory associated with Checkout
	}
	print_p( R::exportAll( $checkouts ) ); 

	usort( $checkouts, function($a, $b) {
		if ($a['checkin_at'] == $b['checkin_at'])
			return $a['checkout_at'] > $b['checkout_at'];
		else
			return $a['checkin_at'] < $b['checkin_at'];
	});

	return $this->view->render($response, 'checkouts.twig', [
		'messages' => $messages,
		'checkouts' => $checkouts,
	]);
})->setName('checkouts');

$app->group('/checkouts/{id:[0-9]+}/{auth}', function () {
	$this->get( '', function($request, $response, $args){
		try {
			$checkout = R::load( 'checkout', $args['id'] );
			$checkout->territory;
			$territory = getTerritoryFullDetails( $checkout->territory_id );


			// R::load('territory', $checkout->territory_id);
			// $territory->ownBuildingList();
			// $territory = R::exportAll( $territory );		

// print_p( $territory ); die();

			$messages = $this->flash->getMessages();
			if ($checkout->token != $args['auth']) 
				throw new ForbiddenException("Checkout uses Invalid Key");

		} catch ( ForbiddenException $e ) {
			$messages['notice'][] =  $e->getMessage() ;
			$messages['error'][] =  'Ummm...'.$e->getMessage();
			return $this->view->render($response->withStatus(403), 'error.twig', [
				'messages' => $messages,
			]);
		} catch ( Exception $e ) {
			$messages['fail'][] =  $e->getMessage();
			$response = $response->withStatus(400);
		}
		return $this->view->render($response, 'checkout.twig', [
			'checkout' => $checkout,
			'messages' => $messages,
			'territory' => $territory,
		]);		
	})->setName('checkout');

	$this->put('', function($request, $response, $args){
		try {
			//R::find( 'book', ' rating < :rating ', [ ':rating' => 2 ] );
			$checkout = R::findOne('checkout', 'id = :id AND token = :token', [ 
				':id' => $args['id'], 
				':token' => $args['auth'] 
			]);
			if( empty($checkout) )throw new ResourceNotFoundException("Couldn't find a checkout record or invalid authentication");
			
			$date = date_create_from_format('Y-m-d', $request->getParsedBodyParam('checkin_at'));
			if(!$date){
				$date = R::isoDate();
				$this->flash->addMessage('fail', $formdata['checkout_at'] . ' not a valid date, so I used today.');
			}

			$checkout->territory->checked_out = false;
			$checkout->checkin_at = $date;
			$checkout->token = NULL;
			
			R::begin();
			R::store( $checkout );
			R::commit();
			
			$this->flash->addMessage('success', 'Territory ' . $checkout->territory->title . ' checked back in.' );

		} catch ( ResourceNotFoundException $e){
			$messages['fail'][] =  'Resource not found' ;
			$messages['error'][] =  'Ummm... '.$e->getMessage();
			return $this->view->render($response->withStatus(403), 'error.twig', [
				'messages' => $messages,
			]); 
		} catch ( RedException $e ){
			R::rollback();
			$messages['fail'][] =  $e->getMessage();
			return $this->view->render($response->withStatus(400), 'error.twig', [
				'messages' => $messages,
			]); 			

		} catch ( Exception $e ) {
			$messages['fail'][] =  $e->getMessage();
			return $this->view->render($response->withStatus(400), 'error.twig', [
				'messages' => $messages,
			]); 
		}

		// Send back to territory page.
		return $response->withRedirect( 
			$this->router->pathFor('territory', [ 'id' => $checkout->territory_id ] ));

	});
});
