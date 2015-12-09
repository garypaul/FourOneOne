<?php 
	function print_r_tree($data)
	{
	    // capture the output of print_r
	    $out = print_r($data, true);

	    // replace something like '[element] => <newline> (' with <a href="javascript:toggleDisplay('...');">...</a><div id="..." style="display: none;">
	    $out = preg_replace('/([ \t]*)(\[[^\]]+\][ \t]*\=\>[ \t]*[a-z0-9 \t_]+)\n[ \t]*\(/iUe',"'\\1<a href=\"javascript:toggleDisplay(\''.(\$id = substr(md5(rand().'\\0'), 0, 7)).'\');\">\\2</a><div id=\"'.\$id.'\" style=\"display: none;\">'", $out);

	    // replace ')' on its own on a new line (surrounded by whitespace is ok) with '</div>
	    $out = preg_replace('/^\s*\)\s*$/m', '</div>', $out);

	    // print the javascript function toggleDisplay() and then the transformed output
	    echo '<script language="Javascript">function toggleDisplay(id) { document.getElementById(id).style.display = (document.getElementById(id).style.display == "block") ? "none" : "block"; }</script>'."\n$out";
	}

	// Create the API URL
	function get_api_url( $address, $page = 1){			

		$microdata_endpoint = 'http://getschema.org/microdataextractor';
		
		// Encode the address into the query string
		$address_qs = urlencode( $address ) . '&st=reverse' . '&p=' . $page;		
		$directory_url = 'https://411.ca/search?q=' . $address_qs;
		
		// encode the 411.ca url and extract microdata endpoint URL
		// http://www.getschema.org/microdataextractor/about#ntriples
		return $microdata_endpoint . '?url=' . urlencode($directory_url) . '&out=json';
	}

	// Just a regular CURL function
	function get_json_from_url( $url ){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 25);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($ch);
		curl_close($ch);
		// if(curl_errno($ch)) {
		//     echo 'error:' . curl_error($ch);
		// }
		return $data;
	}

	// Create a nice person array
	function create_person ( $person_data ){
		
		$person = array();

		// divide data into parts
		$_type = $person_data->{'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'};
		$_name = $person_data->{'http://schema.org/name'};
    	$_url = $person_data->{'http://schema.org/url'};
    	$_address = $person_data->{'http://schema.org/address'};
    	$_telephone = $person_data->{'http://schema.org/telephone'};

    	// separate address by commas
    	$address_parts = explode( ', ', get_microdata_part( $_address ) );

    	// find the postcode
    	foreach ($address_parts as $part) {
    		if( preg_match('/V[0-9][A-Z] \d[A-Z]\d/', $part, $matches)){
    			$postcode = $matches[0];
    	 	}
    	}

    	//Create the Array
    	$person['name'] 			= get_microdata_part( $_name );
    	$person['street_address'] 	= $address_parts[0];
    	$person['city'] 			= count($address_parts) > 1 ? $address_parts[1] : "";
    	$person['province'] 		= count($address_parts) > 2 ? $address_parts[2] : "";
    	$person['post_code'] 		= isset($postcode) ? $postcode : '';
    	$person['phone'] 			= get_microdata_part( $_telephone );
    	$person['url']				= get_microdata_part( $_name, 'uri' );

    	return $person;
	}

	function get_all_people_one_page( $address, $page ){
			$all_people = array();

			$url = get_api_url( $address, $page );

			echo $url;

			// Get raw JSON data
			$data = get_json_from_url( $url );

			// Create PHP Array from JSON
			$people_data = json_decode( $data );

			// Go through data IF it's not empty
			if ( !empty( $people_data ) ):
				foreach ($people_data as $person_data) {
					// echo '<pre>';
					// print_r_tree( $person_data );
					// echo '</pre>';

					// extract the data and put it into a standard array
					$person = create_person( $person_data );

					// If this is a person ( and not a business, pus it onto the array)
					if( strpos( $person['url'], '/person/' ) !== false ){
						array_push( $all_people , $person );
					}
				}				
			endif;
			return $all_people;
	}

	// recursively get all pages.
	// 
	function get_all_people( $address, $single_page = false ){

		$pages = array();
		$page = 1;
		if( is_numeric($single_page) ){
		 	
		 	$all_people = get_all_people_one_page( $address, $single_page );
		 	array_push($pages, $all_people);

		} else {
			for ($i=1; $i < 4; $i++) { 
				$all_people = get_all_people_one_page( $address, $i );	
				array_push($pages, $all_people);
			}

		}

		return $pages;
	}



	/*
	 * $type can be 'literal' or 'uri'
	*/
	// returns the 1st value found of type
	function get_microdata_part( $data, $type='literal' ){
		foreach ($data as $obj) {
			if ( $obj->type == $type ){
				return $obj->value;
			}
		}
		// if type not found, returns false.
		return false;
	}

?>

<!DOCTYPE html>
<html>
<head>
	<title>Phone Territory Easyfier</title>
	<style>
	body {
		font-family: sans-serif;
	}
	input[type='submit']{
		padding: 5px;
		font-size: 1.5em;
	}
	input[type='text']{
		width: 300px;
		padding: 5px;
		font-size: 1.5em;
		border-radius: 3px;
		border: 1px solid #999;
	}
	input.small {
		width: 100px;
	}
	input.large {
		width: 600px;
	}
	.people {
		width: 100%;
	}
	.people th {
		background: #fefddf;
	}
	</style>
</head>
<body>
<?php
	$posted_address = isset($_POST['address']) ? $_POST['address'] : false;
	$posted_page = isset($POST['page']) ? $_POST['page'] : false;
?>

	<form method="POST" action="">
		<table>
			<tr>
				<td><label for='address'>Full Address</label></td>
				<td><label for='page'>Single Page</label></td>
			</tr>
			<tr>
				<td><input id='address' class='large' placeholder='e.g. 123 Sesame Street, Vancouver BC, V5T 1B1' name='address' type='text' value="<?= $posted_address ? $_POST['address'] : '' ?>" /></td>
				<td><input id='page' name='page' class='small' type='text' placeholder='e.g. 1' value='<?= $posted_page ?>' /></td>
			</tr>
		</table>
		<input type='submit' value="submit">
	</form>

<?php

	if ( $posted_address ):

		$all_pages = get_all_people( $posted_address, $posted_page ); ?>

		<table class='people'>
			<thead>
				<tr>
					<th colspan='6'><?= $posted_address ?></th>
				</tr>
				<tr>
					<th>Name</th>
					<th>Number</th>
					<th>Address</th>
					<th>City</th>
					<th>Province</th>
					<th>Postal Code</th>
				</tr>
			</thead>
			<tbody>
		<?php	
			foreach ($all_pages as $all_people) {
				foreach ($all_people as $person) { ?>
				<tr>
					<td><a href="<?= $person['url'] ?>"><?= $person['name']; ?></a></td>
					<td><?= $person['phone']; ?></td>
					<td><?= $person['street_address']; ?></td>
					<td><?= $person['city']; ?></td>
					<td><?= $person['province'] ?></td>
					<td><?= $person['post_code']; ?></td>
				</tr>
			<?php 
				} 
			}
			?>
			</tbody>
		</table>
	<?php
	endif;

?>
 </body>
</html>
