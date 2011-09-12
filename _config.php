<?php
if( Director::is_cli() ) {
	Director::addRules(50, array(
		'geolocator' => 'GeoLocationController',
	));
}