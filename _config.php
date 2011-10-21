<?php
if( Director::is_cli() ) {
	Director::addRules(50, array(
		'geolocator' => 'GeoLocationCLIController',
	));
}
/**
 * Default route:
Director::addRules(50, array(
	'geolocator' => 'GeoLocatorPage_Controller',
));
 */