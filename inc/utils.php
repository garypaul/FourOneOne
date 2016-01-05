<?php 
use linclark\MicrodataPHP\MicrodataPhp;

function print_p($data, $wrapper = 'pre'){
	echo '<'. $wrapper .'>';
	print_r($data);
	echo '</'. $wrapper .'>';
}

function savePersonToBuilding( $p, $building ){
	//print_p( $p );
	$person = R::dispense('person');
	$person->name = trim($p['name']);
	$person->address = trim($p['address']);
	$person->unit = trim($p['unit']);
	$person->postcode = trim($p['postcode']);
	$person->number = trim($p['number']);
	$person->last_updated = R::isoDate();

	$building->xownPersonList[] = $person;
}
function cmpPeople($a, $b){

	if($a['unit'] == $b['unit'])
		return strcmp($a['name'], $b['name']);
	else 
		return strcmp($a['unit'], $b['unit']);
}
function cmpPeopleByAddress($a, $b){

	if($a['address'] == $b['address'])
		return strcmp($a['unit'], $b['unit']);
	else 
		return strcmp($a['address'], $b['address']);
}
function getPeopleByAddress( $address ){
	$PAGE_SIZE = 25;
	$MAX_PAGES = 5;
	$url = 'https://411.ca/search?q='. urlencode( strtolower( $address ) . ', vancouver, bc') .'&st=reverse';

	// Get first page of data and extract the items
	$md = new MicrodataPhp($url);
	$items = $md->obj()->items;

	if( count($items) >= $PAGE_SIZE ) {
		// We have more than 1 page, so try getting pages 2 to 5
		for ($i=2; $i <= $MAX_PAGES; $i++) { 
			$md = new MicrodataPhp($url.'&p=' . $i);
			$items = array_merge($items, $md->obj()->items);
			
			// If we had less than 25 items, then this is the last page
			if( count($md->obj()->items) < $PAGE_SIZE ) break;
		}		
	}

	// print_p( count($items) . ' items.');
	// print_p( $items ); die();

	$people = array();

	$postalcode_pattern = '/[a-zA-Z]\d[a-zA-Z]\s?\d[a-zA-Z]\d/';
	foreach ($items as $key => $item) {
		$type = $item->type[0];
		$properties = $item->properties;
		$address = trim( $properties['address'][0] );
		if ( preg_match($postalcode_pattern, $properties['address'][0], $matches) ){
			$postcode = $matches[0];
			$address = preg_replace($postalcode_pattern, '', $address);
		}

		$address = preg_replace('/, Vancouver, BC,/', '', $address);	

		if ( $type == 'http://schema.org/Person'){
			$p = R::dispense('person');
			$p->name = trim ($properties['name'][1] );
			$p->address = $address;
			$p->postcode = $postcode;
			$p->number = trim ($properties['telephone'][0] );

			$people[] = $p;		
		}
	}
	return $people;
}