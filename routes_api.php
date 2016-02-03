<?php

/*

	API routes

 */

$app->group('/api/people/{id:[0-9]+}', function(){

	$this->put( '', function($request, $response, $args){


		$person = null;
		try {
			
			$person = R::load('person', $args['id']);
			$person_export = $person->export();
			
			//$formdata = ;
			$request_body = $request->getParsedBody();
			print_p($request_body); die();
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