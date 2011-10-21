<?php

/**
 * Provides methods for performing a query on a table containing latitudes and longitudes, such as
 * the GeoLocation table. Also provides an abstraction of the names of those fields 
 * (latitudeField and longitudeField) and the ability to control which fields should be included
 * in the markers that are returned by the GeoLocatorController methods (extraFields). 
 * @author simonwade
 */
class GeoLocator {

	protected $defaultRadius = 20;
	protected $dataObject = 'GeoLocation';
	protected $latitudeField = 'GeoLocation.Latitude';
	protected $longitudeField = 'GeoLocation.Longitude';
	protected $extraFields = array();

	public function __construct( $dataObject = null, $extraFields = null ) {
		if( $dataObject ) {
			$this->dataObject = $dataObject;
			$this->latitudeField = "$dataObject.Latitude";
			$this->longitudeField = "$dataObject.Longitude";
		}
		if( $extraFields ) {
			$this->setExtraFields($extraFields);
		}
	}

	public function setFieldNames( $latitudeField, $longitudeField ) {
		$this->latitudeField = $latitudeField;
		$this->longitudeField = $longitudeField;
	}

	public function setExtraFields( $fields ) {
		$this->extraFields = $fields;
	}

	public function getResultsByDistance( $geoLocation, $radius = null ) {
		$formula = $geoLocation->getDistanceFormula($this->latitudeField, $this->longitudeField);
		if( !$radius ) {
			$radius = $this->defaultRadius;
		}
		$sql = new SQLQuery();
		$sql->select("$this->dataObject.*, $formula AS Distance")
			->from($this->dataObject)
			->having('Distance <= '.Convert::raw2sql($radius))
			->groupby("$this->dataObject.ID")
			->orderby('Distance ASC');
		return singleton($this->dataObject)->buildDataObjectSet($sql->execute());
	}

	public function getMarkerAttributes( $result ) {
		$rv = array(
			'name' => $result->Title,
			'lat' => $result->Latitude,
			'lng' => $result->Longitude
		);
		foreach( $this->extraFields as $name => $value ) {
			$rv[is_integer($name) ? $value : $name] = $result->$value;
		}
		return $rv;
	}

}

/**
 * GeoLocator implementation for use with the Geolocatable decorator from the addressable module.
 * @author simonwade
 */
class GeolocatableLocator extends GeoLocator {

	public function __construct( $dataObject = null, $extraFields = null ) {
		parent::__construct($dataObject);
		$this->setFieldNames("$dataObject.Lat", "$dataObject.Lng");
		$this->setExtraFields(array_merge(array(
			'address' => 'FullAddress'
		), $extraFields));
	}

}
