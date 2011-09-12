<?php

class GeoLocation extends DataObject {

	public static $db = array(
		'Name' => 'Varchar',
		'State' => 'Varchar',
		'Type' => 'Varchar',
		'Postcode' => 'Varchar',
		'Latitude' => 'Float',
		'Longitude' => 'Float'
	);

	public static $belongs_many_many = array(
		'Regions' => 'Region'
	);

	public static $summary_fields = array('Name', 'Postcode');

	public function getFullTitle() {
		return sprintf(
			"%s, %s %s",
			$this->Name,
			$this->State,
			$this->Postcode
		);
	}

	public function getNameCapitalised() {
		return ucwords( strtolower( $this->getField('Name') ) );
	}

	/**
	 * Returns a list of GeoLocations for the given postcode
	 * @param string $postcode The Postcode to search for
	 * @return DataObjectSet
	 */
	static public function getByPostcode($postcode) {
		return DataObject::get('GeoLocation', "`Postcode` = '$postcode'");
	}
	
	/**
	 * Gets a single GeoLocation instance, by its unique Postcode
	 * @param string $postcode The Postcode to search for
	 * @return GeoLocation
	 */
	static public function getFirstByPostcode($postcode) {
		return DataObject::get_one('GeoLocation', "`Postcode` = '$postcode'");
	}
	
	/**
	 * Retrieve a list of GeoLocation's that match $keyword.
	 *
	 * @param string $keyword
	 * @return DataObjectSet
	 *
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	static public function getByKeyword($keyword, $limit = false) {
		$keyword = Convert::raw2sql('%' . $keyword . '%');
		$postcode = substr($keyword, 1);
		return DataObject::get(
			'GeoLocation', 
			sprintf(
				"`Postcode` LIKE '%s' OR `State` LIKE '%s' OR `Name` LIKE '%s'", 
				$postcode, 
				$keyword, 
				$keyword
			),
			'', // Sort
			'', // Join
			$limit ? $limit : ''
		);
	}

	/**
	 * @param DataObject $object The object containing the Relationship
	 * @param string $postcodes The List of postcodes
	 */
	static public function updatePostcodeRelationship( DataObject $object, $postcodes ) {
		//* debug */ $_REQUEST['showqueries'] = true;
		$geoLocations = $object->GeoLocations(); /* @var $geoLocations DataObjectSet */
		$geoLocations->removeAll();

		$postcodes = preg_split('/[ ,]+/', $postcodes);
		foreach ($postcodes as $postcode) {
			if ($postcode = trim($postcode)) {
				if ($geoLocation = self::getFirstByPostcode($postcode)) {
					$geoLocations->add($geoLocation);
				}
			}
		}
		$geoLocations->write();
	}

	/**
	 * @param int $radius The distance from this GeoLocation (in km's)
	 * @return DataObjectSet
	 */
	public function getByDistance( $radius ) {
		$formula = $this->getDistanceFormula($this->Latitude, $this->Longitude);
		$sql = new SQLQuery();
		$sql->select('GeoLocation.*, '.$formula.' as Distance')
			->from('GeoLocation')
			->having('Distance < '.$radius)
			->orderby('Distance');
		return singleton('GeoLocation')->buildDataObjectSet($sql->execute());
	}

	/**
	 * @param int $radius
	 * @return SQLQuery
	 */
	public function getDistanceSQLQuery( $radius ) {
		$formula = $this->getDistanceFormula($this->Latitude, $this->Longitude);
		$sql = new SQLQuery();
		$sql->from('GeoLocation')->where($formula.' < '.$radius);
		return $sql;
	}

	/**
	 * @param int $latitude
	 * @param int $longitude
	 * @param int $table
	 * @return string
	 */
	public function getDistanceFormula( 
			$latitudeField = 'GeoLocation.Latitude', $longitudeField = 'GeoLocation.Longitude' ) {
		return self::getDistanceFormulaForCoords($this->Latitude, $this->Longitude, $latitudeField, $longitudeField);
	}

	public static function getDistanceFormulaForCoords( $latitude, $longitude,
			$latitudeField, $longitudeField ) {
		$factor = 6371; // for kilometres
		return "$factor * ACOS(COS(RADIANS('$latitude')) * COS(RADIANS($latitudeField)) "
				."* COS(RADIANS($longitudeField) - RADIANS('$longitude')) + "
				."SIN(RADIANS($latitude)) * SIN(RADIANS($latitudeField)))";
	}

}
