<?php

class GeoLocatorPage extends Page {}

/**
 * Provides actions for querying a database of geographical coordinates.
 * @author simonwade
 */
class GeoLocatorPage_Controller extends Page_Controller {

	/**
	 * Provides the configuration for queries.
	 * @var GeoLocator
	 */
	protected $geoLocator;

	public function __construct( GeoLocator $locator = null ) {
		$locator && $this->setGeoLocator($locator);
		parent::__construct();
	}

	public function Nearest() {
		$locator = $this->getGeoLocator();
		if( $matches = GeoLocation::getByPostcode(Convert::raw2sql($this->request->requestVar('near'))) ) {
			$this->Origin = $matches->pop();
			return $locator->getResultsByDistance($this->Origin, $this->request->requestVar('radius'));
		}
	}

	public function getGeoLocator() {
		if( !$this->geoLocator ) {
			$this->geoLocator = new GeoLocator();
		}
		return $this->geoLocator;
	}

	public function setGeoLocator( $locator ) {
		$this->geoLocator = $locator;
	}

}

?>