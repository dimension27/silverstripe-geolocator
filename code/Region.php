<?php

class Region extends DataObject {

	public static $db = array(
		'Name' => 'Varchar',
		'State' => 'Varchar',
		'Postcodes' => 'Text'
	);

	public static $many_many = array(
		'GeoLocations' => 'GeoLocation'
	);

	public static $summary_fields = array('Name', 'State');
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField('GeoLocations', $field = new ManyManyDataObjectManager(
			$controller = $this,
			$name = 'GeoLocations',
			$sourceClass = 'GeoLocation',
			$fieldList = array('Name' => 'Name', 'State' => 'State', 'Postcode' => 'Postcode') //, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = ""
		));
		$field->setPermissions(array('only_related', 'view'));
		return $fields;
	}

	protected function onBeforeWrite() {
		// Explode the Postcodes and add them to the relationship
		GeoLocation::updatePostcodeRelationship($this, $this->Postcodes);
		return parent::onBeforeWrite();
	}

	/**
	 * Gets all the Regions that have the postcode
	 * @return DataObjectSet
	 */
	static public function getByPostcode($postcode) {
		$q = new SQLQuery();
		$q->from('Region');
		$q->where('Postcodes LIKE \'%'.$postcode.'%\'');
		$result = $q->execute();
		return singleton('Region')->buildDataObjectSet($result);
	}
	
	/**
	 * @param GeoLocation $geoLocation
	 * @param integer $distanceLimit
	 * @return DataObjectSet
	 */
	public static function get_by_distance( GeoLocation $geoLocation, $distanceLimit ) {
		$sql = new SQLQuery();
		$regionsSql = $geoLocation->getDistanceSQLQuery($distanceLimit)->select('ID');
		$sql->select('Region.*') // can we add $formula.' as Distance'?
			->from('Region')
			->innerJoin('Region_GeoLocations', 'R_GL.RegionID = Region.ID', 'R_GL')
			->where("R_GL.GeoLocationID IN (".$regionsSql->sql().")")
			->groupby('ID');
		return singleton('Region')->buildDataObjectSet($sql->execute());
	}

	public static function get_state_for_postcode( $countryCode, $postcode ) {
		switch( $countryCode ) {
			case 'au':
				$first = substr($postcode, 0, 1);
				switch( $first ) {
					case '2':
						return 'NSW';
					case '4':
						return 'QLD';
					case '5':
						return 'SA';
					case '7':
						return 'TAS';
					case '3':
						return 'VIC';
					case '6':
						return 'WA';
				}
				if( ($postcode >= 2600 && $postcode <= 2618) || substr($postcode, 0, 2) == '29' ) {
					return 'ACT';
				}
				else if( in_array(substr($postcode, 0, 2), array('08', '09')) ) {
					return 'NT';
				}
				throw new Exception("Couldn't find state for postcode '$postcode'");
			default:
				throw new Execption("Unsupported country code '$countryCode' specified");
		}
	}

	/**
	 * Returns a list of states as key/value pairs.
	 * 
	 * @return ComponentSet
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public static function getStates() {
		return array(
			'ACT' => 'Australian Capital Territory', 
			'NSW' => 'New South Wales', 
			'VIC' => 'Victoria',
			'QLD' => 'Queensland',
			'SA'  => 'South Australia',
			'TAS' => 'Tasmania',
			'WA'  => 'Western Australia',
			'NT'  => 'Northern Territory'
		);
	}

	/**
	 * Gets regions grouped by state.
	 * 
	 * @see GroupedDropdownField
	 * @return array
	 */
	static public function getGroupedByState() {
		$grouped = array();
		foreach( self::getStates() as $abbr => $label ) {
			if( $regions = DataObject::get('AgRegion', "`State` = '$abbr'") ) {
				$grouped[$label] = $regions->map();				
			}
		}
		return $grouped;
	}

}
