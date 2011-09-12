<?php

class GeoLocationController extends Controller {

	static $url_handlers = array(
		'load-postcodes' => 'loadPostcodes',
		'load-regions' => 'loadRegions',
		'$Postcode!/$Object!/$Radius' => 'objects_by_distance',
		'search' => 'search',
	);

	public function loadPostcodes( SS_HTTPRequest $request ) {
		$args = $request->getVar('args');
		if( !$file = array_shift($args) ) {
			return 'Please pass a file argument';
		}
		if( in_array('one-location-per-postcode', $args) ) {
			$oneLocationPerPostcode = true;
		}
		$fh = fopen($file, 'r');
		$header = fgetcsv($fh);
		while($line = fgetcsv($fh)) {
			list($postcode, $state, $name, $type, $latitude, $longitude) = $line;
			$name = ucwords(strtolower($name));
			if( !$gl = DataObject::get_one('GeoLocation',
					"`Postcode` = '$postcode'"
					.($oneLocationPerPostcode ? '' : " AND `Name` = '".Convert::raw2sql($name)."'")) ) {
				$gl = new GeoLocation();
				$gl->Postcode = $postcode;
				$gl->Name = $name;
			}
			$gl->State = $state;
			$gl->Type = $type;
			$gl->Latitude = $latitude;
			$gl->Longitude = $longitude;
			$gl->write();
		}
	}

	public function loadRegions() {
		if( !@$_GET['file'] ) {
			return 'Please pass a file argument';
		}
		$regions = array();
		$file = $_GET['file'];
		$fh = fopen($file, 'r');
		$header = fgetcsv($fh);
		while( $line = fgetcsv($fh) ) {
			$name = trim($line[0]);
			if( strlen($line[1]) == 3 ) {
				$line[1] = str_pad($line[1], 4, 0, STR_PAD_LEFT);
			}
			if( !$region = @$regions[$name] ) {
				if( !$region = DataObject::get_one('Region', "`Name` = '".Convert::raw2sql($name)."'") ) {
					$region = new Region();
					$region->Name = $name;
					echo "Creating region '$region->Name'\n";
					try {
						$region->State = Region::get_state_for_postcode('au', $line[1]);
					}
					catch( Exception $e ) {
						echo "\t".$e->getMessage().NL;
					}
					$region->write();
				}
				$regions[$name] = $region;
			}
			$region->Postcodes .= $line[1].' ';
		}
		foreach( $regions as $name => $region ) {
			echo "Updating GeoLocations for region '$region->Name'\n";
			try {
				$region->write();
			}
			catch( ValidationException $e ) {
				echo "\t".$e->getMessage().': '.implode(', ', $e->getResult()->messageList())."\n";
			}
		}
	}

	public function objects_by_distance($request) {
		// @todo modify output into JSON for AJAX reuqests
		$geoLocation = DataObject::get_one('GeoLocation', "`Postcode` = '".Convert::raw2sql($request->param("Postcode"))."'");
		if (!$geoLocation) {
			// Return an empty Set.
			return print_r(array(), true);
		}
		if (!$radius = $request->param('Radius')) {
			$radius = 50;
		}
		$objects = $geoLocation->getObjectsByDistance($request->param('Object'), $radius);
		$object_array = array();
		foreach($objects as $object) {
			$object_array[$object->ID] = $object->getTitle();
		}

		return print_r($object_array, true);
	}

	/**
	 * Returns a json encoded list of GeoLocation's that match keyword.
	 * @param string|SS_HTTPRequest $request
	 * @return string
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public function search( $request ) {
		if( $keyword = is_object($request) ? $request->requestVar('term') : $request ) {
			$geoLocations = GeoLocation::getByKeyword($keyword, 10);
			$response = array();
			foreach( $geoLocations->map('ID', 'getFullTitle') as $id => $label ) {
				$response[] = array(
					'id' => $id,
					'label' => $label
				);
			}
		}
		else {
			$response = false;
		}
		return json_encode($response);
	}
	
}
