<?php 
use linclark\MicrodataPHP\MicrodataPhp;

function print_p($data, $wrapper = 'pre'){
	echo '<'. $wrapper .'>';
	print_r( $data );
	echo '</'. $wrapper .'>';
}
/**
 * @param  String Entity name ( 'territory', 'building', 'person')
 * @param  Array  Array of form data. Single item, not nested.
 * @return Object RedBean entity
 */
function createEntityWithArray( $type, $array ){
	// New up the entity
	$entity = R::dispense($type);

	// Get all valid field names and foreign keys
	$fieldnames = R::inspect( $type );

	// strip out invalid keys from formdata
	$valid_fields = array_intersect_key($fieldnames, $array);
	
	// iterate over valid keys and add each one
	foreach ($valid_fields as $key => $value ) {
		$entity[$key] = trim($array[$key]);
	}
	return $entity;
}

/**
 * @param  String  Entity name 'territory', 'building', 'person', etc.
 * @param  Numeric Id of entity to compare against in db
 * @param  Array  Array of form data. Single item, not nested.
 * @return Object RedBean entity
 */
function updateEntityWithArray( $type, $id, $array ){
	// New up the entity
	$entity = R::load($type, $id);
	
	// Get changed data only
	$differences = array_diff_assoc( $array, $entity->export() );
	
	// get valid field names
	$fieldnames = R::inspect( $type );

	// find the valid fields from updaed values and valid fieldnames
	$valid_fields = array_intersect_key($fieldnames, $differences);
	
	// only iterate over valid fields that are also updated.
	foreach ($valid_fields as $key => $value ) {
		$entity[$key] = trim($array[$key]);
	}
	return $entity;
}

/**
 * @param  string $type Entity type name ( lower case, please )
 * @param  Array $formdata Assoc Array of $key and $value you want to update  
 * @param  integer $id ID of entity you want to update. Default is 0 ( new ).
 * @return bean 
 */	
function save_entity_with_array( $type = '', Array $formdata, $id = 0 ){
	if ( !$type ) throw new Exception("No entity specified.");
	
	$bean = $id ? R::load( $type, $id ) : R::dispense( $type );
	// This compares the values in the exported version of the entity and the submitted values
	// If a new bean has been dispensed.
	// NOTE: Essentially, this discards any invalid properties in the formdata. 
	$changes = array_diff_assoc($bean->export(), $formdata);
	if( empty($changes) ) throw new Exception("No changes made");
	
	// Iterate through the keys that have changed and update bean.
	foreach ($changes as $key => $value) {
		$bean[$key] = $formdata[$key];
	}
	$id = R::store( $bean ); // Save bean and return ID of saved bean.
	return $bean;
}


function cmpByKey( $key, $sort = 'ASC' ){
	return function($a, $b) use($key, $sort) {
		return ($sort == 'ASC') 
			? $a[$key] > $b[$key] 
			: $a[$key] < $b[$key];  
	};
}
function cmpByStreetName( $sort = 'ASC' ){
	// return delegate function
	return function($a, $b) use( $sort ) {
			// switch if it's descending sort order
			if ($sort == 'DESC'){
				$tmp = $a;
				$a = $b;
				$b = $tmp;
			}

			$key = 'address';
			// test if addresses are the same
			$comp = cmpBy2ndWord($a[$key], $b[$key]);
			
			// if street names are == then sort by unit ( secondary sort )
			if($comp == 0)
				return $a['unit'] > $b['unit'];
			else
				return $comp;
	};
}

function cmpBy2ndWord($a, $b){
	return strcmp( substr($a, strpos($a, ' ')) , substr($b, strpos($b, ' ')) );
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

function getBuildingFullDetails( $id, $isPersonPublished = true ){

	$b = R::load('building', $id );
	$building = $b->export();

	$people = R::exportAll($b->ownPersonList);
	usort($people, cmpByKey('unit') );
	
	$building['people'] = $people;
	$building['territory_title'] = $b->territory['title'];

	return $building;

}

function getTerritoryFullDetails( $id, $isPersonPublished = true ){
		$t = R::load('territory', $id);
		$territory = $t->export(); // simple territory copy
				
		$ownBuildings = $t->ownBuildingList; // building 'beans'
		
		foreach ($ownBuildings as $b) {
			// simple building copy
			$building = $b->export(); 

			// make simple people copy
			$people = R::exportAll($b->ownPersonList);
			
			// sort by unit
			usort($people, cmpByKey('unit') );

			// Add to simple building
			$building['people'] = $people; 
			
			// add building to array
			$buildings[] = $building; 
		}

		usort($buildings, function($a, $b){
			return count($a['people']) < count($b['people']);
		});

		$territory['buildings'] = $buildings;
		
		return $territory;
}