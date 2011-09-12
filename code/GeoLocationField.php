<?php 

class GeoLocationField extends FormField {
	
	public static $url_handlers = array (
		'$Action!/$ID' => '$Action'
	);
	
	public static $allowed_actions = array (
		'search'
	);

	/**
	 * @param string $name the field name
	 * @param string $title the field label
	 * @param string $sourceObject The object-type to list in the tree.
	 * @param string $keyField to field on the source class to save as the field value (default ID).
	 * @param string $labelField the field name to show as the human-readable value on the tree (default Title).
	 * @param string $showSearch enable the ability to search the tree by entering the text in the input field.
	 */
	public function __construct( $name, $title = null ) {
			// , $sourceObject = 'GeoLocation', $keyField = 'ID', $labelField = 'Title' ) {
		/*
		$this->sourceObject = $sourceObject;
		$this->keyField     = $keyField;
		$this->labelField   = $labelField;
		*/
		parent::__construct($name, $title);
	}

	/* *
	 * Set a callback used to filter the values before displaying to the user.
	 * @param callback $callback
	 * /
	public function setFilterFunction($callback) {
		if( !is_callable($callback, true) ) {
			throw new InvalidArgumentException('TreeDropdownField->setFilterCallback(): not passed a valid callback');
		}
		$this->filterCallback = $callback;
	}
	
	/**
	 * Set a callback used to search the hierarchy globally, even before applying the filter.
	 * @param callback $callback
	 * /
	public function setSearchFunction($callback) {
		if(!is_callable($callback, true)) {
			throw new InvalidArgumentException('TreeDropdownField->setSearchFunction(): not passed a valid callback');
		}
		$this->searchCallback = $callback;
	}
	*/

	/**
	 * @return string
	 */
	public function Field() {
		$themeDir = Utils::ThemeDir();
		Requirements::javascript("sapphire/thirdparty/jquery/jquery.js");
		Requirements::javascript("{$themeDir}/js/jquery-ui-1.8.13.custom/js/jquery-ui-1.8.13.custom.min.js");
		Requirements::javascript("geolocator/js/GeoLocationField.js");
		Requirements::css("{$themeDir}/js/jquery-ui-1.8.13.custom/css/flick/jquery-ui-1.8.13.custom.css");
		
		$prefix = 'GeoLocationField-'.$this->id();
		$value = null;
		if( @$this->value && $geoLocation = DataObject::get_by_id('GeoLocation', $this->value) ) {
			$value = $geoLocation->getFullTitle();
		}
		return "
<div id='$prefix-container' class='GeoLocationField'>
	<input id='$prefix' type='text' name='$prefix-location' value='{$value}' rel='{$this->Link()}/search' class='text'>
	<input type='hidden' name='$this->name' value='$this->value'>
</div>
";
	}
	
	/**
	 * Performs the lookup and returns the results as AJAX.
	 * @param SS_HTTPRequest $request
	 * @return string
	 */
	public function search( SS_HTTPRequest $request ) {
		$controller = new GeoLocationController();
		return $controller->search($request);
	}

	/* *
	 * Marking function for the tree, which combines different filters sensibly. If a filter function has been set,
	 * that will be called. If the source is a folder, automatically filter folder. And if search text is set, filter on that
	 * too. Return true if all applicable conditions are true, false otherwise.
	 * @param $node
	 * @return unknown_type
	 * /
	function filterMarking($node) {
		if ($this->filterCallback && !call_user_func($this->filterCallback, $node)) return false;
		if ($this->sourceObject == "Folder" && $node->ClassName != 'Folder') return false;
		if ($this->search != "") {
			return isset($this->searchIds[$node->ID]) && $this->searchIds[$node->ID] ? true : false;
		}
		
		return true;
	}
	
	/* *
	 * Populate $this->searchIds with the IDs of the pages matching the searched parameter and their parents.
	 * Reverse-constructs the tree starting from the leaves. Initially taken from CMSSiteTreeFilter, but modified
	 * with pluggable search function.
	 * /
	protected function populateIDs() {
		// get all the leaves to be displayed
		if ( $this->searchCallback )
			$res = call_user_func($this->searchCallback, $this->sourceObject, $this->labelField, $this->search);
		else
			$res = DataObject::get($this->sourceObject, "\"$this->labelField\" LIKE '%$this->search%'");
		
		if( $res ) {
			// iteratively fetch the parents in bulk, until all the leaves can be accessed using the tree control
			foreach($res as $row) {
				if ($row->ParentID) $parents[$row->ParentID] = true;
				$this->searchIds[$row->ID] = true;
			}
			while (!empty($parents)) {
				$res = DB::query('SELECT "ParentID", "ID" FROM "' . $this->sourceObject . '" WHERE "ID" in ('.implode(',',array_keys($parents)).')');
				$parents = array();

				foreach($res as $row) {
					if ($row['ParentID']) $parents[$row['ParentID']] = true;
					$this->searchIds[$row['ID']] = true;
					$this->searchExpanded[$row['ID']] = true;
				}
			}
		}
	}

	/* *
	 * Get the object where the $keyField is equal to a certain value
	 *
	 * @param string|int $key
	 * @return DataObject
	 * /
	protected function objectForKey($key) {
		if($this->keyField == 'ID') {
			return DataObject::get_by_id($this->sourceObject, $key);
		} else {
			return DataObject::get_one($this->sourceObject, "\"{$this->keyField}\" = '" . Convert::raw2sql($key) . "'");
		}
	}

	/**
	 * Changes this field to the readonly field.
	 * /
	function performReadonlyTransformation() {
		return new TreeDropdownField_Readonly($this->name, $this->title, $this->sourceObject, $this->keyField, $this->labelField);
	}
	*/

}

/* *
 * @package forms
 * @subpackage fields-relational
 * /
class TreeDropdownField_Readonly extends TreeDropdownField {
	protected $readonly = true;
	
	function Field() {
		$fieldName = $this->labelField;
		if($this->value) {
			$keyObj = $this->objectForKey($this->value);
			$obj = $keyObj ? $keyObj->$fieldName : '';
		} else {
			$obj = null;
		}

		$source = array(
			$this->value => $obj
		);

		$field = new LookupField($this->name, $this->title, $source);
		$field->setValue($this->value);
		$field->setForm($this->form);
		return $field->Field();
	}
}
*/

?>