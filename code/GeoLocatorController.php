<?php

/**
 * Provides actions for querying a database of geographical coordinates.
 * @author simonwade
 */
class GeoLocatorController extends Controller {

	static $url_handlers = array(
		'search' => 'search',
		'nearest/$Postcode' => 'nearest',
	);

	/**
	 * Provides the configuration for queries.
	 * @var GeoLocator
	 */
	protected $geoLocator;

	/**
	 * Defines the way that results should be formatted. Supported formats are: xml
	 * @var string
	 */
	protected $outputFormat = 'json';

	public function __construct( GeoLocator $locator = null ) {
		if( $locator ) {
			$this->setGeoLocator($locator);
		}
		parent::__construct();
	}

	public function nearest( SS_HTTPRequest $request ) {
		if( $format = $request->requestVar('format') ) {
			$this->outputFormat = $format;
		}
		$locator = $this->getGeoLocator();
		if( $matches = GeoLocation::getByKeyword(Convert::raw2sql($request->param('Postcode'))) ) {
			$this->Origin = $matches->pop();
			$results = $locator->getResultsByDistance($this->Origin, $request->requestVar('Radius'));
			
		}
		if( !$results ) {
			$results = new DataObjectSet();
		}
		if( !$this->response ) {
			$this->response = new SS_HTTPResponse();
		}
		$response = $this->getResultsMarkup($results);
		$this->response->setBody($response);
		return $this->response;
	}

	public function getResultsMarkup( $results ) {
		$locator = $this->getGeoLocator();
		switch( $this->outputFormat ) {
		case 'xml':
			$this->response->addHeader('Content-type', 'text/xml');
			$response = "<?xml version=\"1.0\"?>\n<markers>\n";
			foreach( $results as $result ) { /* @var $result DataObject */
				$response .= "\t<marker";
				foreach( $locator->getMarkerAttributes($result) as $name => $value ) {
					$response .= " $name=\"".Convert::raw2xml($value)."\"";
				}
				$response .= "/>\n";
			}
			$response .= '</markers>';
			return $response;
			break;
		case 'json':
			$response = array();
			foreach( $results as $result ) { /* @var $result DataObject */
				$response[] = $locator->getMarkerAttributes($result);
			}
			return json_encode($response);
		default:
			throw new Exception("Unsupported output format '$this->outputFormat'");
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
