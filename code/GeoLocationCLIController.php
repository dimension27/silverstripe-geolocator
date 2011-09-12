<?php
class GeoLocationCLIController {

	static $url_handlers = array(
		'load-postcodes' => 'loadPostcodes',
		'load-regions' => 'loadRegions',
	);

	public function loadPostcodes( SS_HTTPRequest $request ) {
		$args = $request->getVar('args');
		if( !$file = array_shift($args) ) {
			return 'Please pass a file argument';
		}
		$oneLocationPerPostcode = in_array('one-location-per-postcode', $args);
		
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
						$region->State = Region::getStateForPostcode('au', $line[1]);
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

}

?>