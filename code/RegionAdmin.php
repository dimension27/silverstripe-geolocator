<?php

class RegionAdmin extends ModelAdmin {

	public static $managed_models = array(
		'Region'
	);
	public static $is_active = false;

	static $url_segment = 'regions';
	static $menu_title = 'Regions';

	function __construct() {
		$this->showImportForm = false;
		parent::__construct();
	}

	static function activate( $bool = true ) {
		self::$is_active = $bool;
	}

	function canView( $member ) {
		return (self::$is_active ? parent::canView($member) : false);
	}

}
