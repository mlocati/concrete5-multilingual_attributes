<?php defined('C5_EXECUTE') or die('Access Denied.');

/** The controller of the Multilingual attribute */
class MultilingualAttributeAttributeTypeController extends AttributeTypeController {

	// Constants

	/** Value of akType for single-line simple text
	* @var string
	*/
	const VALUETYPE_TEXT = 'text';

	/** Value of akType for multi-line simple text
	* @var string
	*/
	const VALUETYPE_TEXTAREA = 'textarea';

	/** Value of akType for rich text
	* @var string
	*/
	const VALUETYPE_HTML = 'html';

	// Properties

	/** The type of the value (one of the MultilingualAttributeAttributeTypeController::VALUETYPE_ values)
	* @var string
	*/
	protected $akType;

	/** Return the type of the value (one of the MultilingualAttributeAttributeTypeController::VALUETYPE_ values)
	* @var string
	*/
	public function getType() {
		if(!isset($this->akType)) {
			$this->loadOptions();
		}
		return $this->akType;
	}

	/** The handle of the fallback attribute
	* @var string
	*/
	protected $akAssociatedAttribute;

	/** Return the handle of the fallback attribute
	 * @var string
	 */
	public function getAssociatedAttribute() {
		if(!isset($this->akAssociatedAttribute)) {
			$this->loadOptions();
		}
		return $this->akAssociatedAttribute;
	}

	// General type-related stuff

	/** Delete this attribute type */
	public function deleteType() {
		$db = Loader::db();
		$db->Execute('drop table if exists atMultilingualAttributeOptions');
		$db->Execute('drop table if exists atMultilingualAttribute');
	}

	// Stuff related to create/edit/delete/duplicate an attribute key

	/** Prepare the data for the form used when defining a new attribute and when editing an existing attribute */
	public function type_form() {
		$this->set('selectableTypes', self::getSelectableTypes());
		$this->set('akType', $this->getType());
		$this->set('akAssociatedAttribute', $this->getAssociatedAttribute());
		$this->set('specialAssociableAttributes', array(
			'file' => array(
				'*title*' => t('Title'),
				'*description*' => t('Description')
			)
		));
	}

	/** Save the data associated to a new attribute / update existing attributes */
	public function saveKey($data) {
		$ak = $this->getAttributeKey();
		$db = Loader::db();
		$akType = $data['akType'];
		if(!array_key_exists($akType, self::getSelectableTypes())) {
			$akType = self::VALUETYPE_TEXT;
		}
		$akAssociatedAttribute = is_string($data['akAssociatedAttribute']) ? trim($data['akAssociatedAttribute']) : '';
		$db->Replace(
			'atMultilingualAttributeOptions',
			array(
				'akID' => $ak->getAttributeKeyID(),
				'akType' => $akType,
				'akAssociatedAttribute' => $akAssociatedAttribute
			),
			array('akID'),
			true
		);
		$this->loadOptions(true);
	}

	/** Delete the attribute and all its associated data */
	public function deleteKey() {
		$ak = $this->getAttributeKey();
		$db = Loader::db();
		$db->Execute('delete from atMultilingualAttributeOptions where akID = ?', array($ak->getAttributeKeyID()));
		foreach(array_chunk($ak->getAttributeValueIDList(), 20) as $chunk) {
			$ids = array();
			foreach($chunk as $id) {
				$ids[] = @intval($id);
			}
			$db->Execute('delete from atMultilingualAttribute where (avID = ' . implode(') or (avID = ', $ids) . ')');
		}
	}

	/** Duplicates this key into a new key
	* @param AttributeKey $ak
	*/
	public function duplicateKey($ak) {
		Loader::db()->Execute(
			'insert into atMultilingualAttributeOptions (akID, akType, akAssociatedAttribute) values (?, ?, ?)',
			array($ak->getAttributeKeyID(), $this->getType(), $this->getAssociatedAttribute())
		);
	}

	// Stuff related to create/edit/delete/duplicate an attribute value
	
	/** Prepare the data for the form used when editing an attribute value or during search */
	public function form() {
		$attributeValue = $this->getAttributeValue();
		$value = is_object($attributeValue) ? $attributeValue->getValue() : MultilingualAttributeAttributeTypeValue::getEmpty($this, null);
		$localizedValues = array();
		$localeFlags = array();
		foreach(self::getAvailableLanguages() as $localeID => $localeName) {
			$localizedValues[$localeID] = is_object($value) ? $value->getLocalizedValueFor($localeID) : '';
			if(is_array($localeFlags)) {
				$flag = self::getLocaleFlag($localeID, true);
				if(strlen($flag)) {
					$localeFlags[$localeID] = $flag;
				}
				else {
					$localeFlags = false;
				}
			}
		}
		$locales = array();
		foreach(self::getAvailableLanguages() as $localeID => $localeName) {
			if(is_array($localeFlags)) {
				$locales[$localeID] = $localeFlags[$localeID] . ' ' . h($localeName);
			}
			else {
				$locales[$localeID] = h($localeName);
			}
		}
		$this->set('key', $this->getAttributeKey());
		$this->set('akType', $this->getType());
		$this->set('locales', $locales);
		$this->set('localizedValues', $localizedValues);
	}

	/** Validate the new attribute values
	* @param array $data The new values
	* @return boolean
	*/
	public function validateForm($data) {
		return true;
	}
	
	/** Save the new attribute values inserted in the edit form
	* @param array $data The new values
	*/
	public function saveForm($data) {
		$localizedValues = array();
		foreach(self::getAvailableLanguages(true) as $localeID) {
			$localizedValue = '';
			if(array_key_exists($localeID, $data) && is_string($data[$localeID])) {
				switch($this->getType()) {
					case self::VALUETYPE_HTML:
						if(is_null($ch)) {
							$ch = Loader::helper('content');
						}
						/* @var $ch ContentHelper */
						$localizedValue = trim($ch->translateTo($data[$localeID]));
						break;
					default:
						$localizedValue = trim($data[$localeID]);
						break;
				}
			}
			if(strlen($localizedValue)) {
				$localizedValues[$localeID] = trim($localizedValue);
			}
		}
		$this->saveValue($localizedValues);
	}

	// Handling attribute values

	/** Save the new values to the DB
	* @param array|MultilingualAttributeAttributeTypeValue $data
	*/
	public function saveValue($data) {
		$localizedValues = array();
		foreach(self::getAvailableLanguages(true) as $localeID) {
			$localizedValue = '';
			if($data instanceof MultilingualAttributeAttributeTypeValue) {
				$localizedValue = $data->getLocalizedValueFor($localeID);
			}
			elseif(is_array($data)) {
				if(array_key_exists($localeID, $data)) {
					$localizedValue = $data[$localeID];
				}
			}
			if(is_string($localizedValue)) {
				$localizedValue = trim($localizedValue);
				if(strlen($localizedValue)) {
					$localizedValues[$localeID] = trim($localizedValue);
				}
			}
		}
		Loader::db()->Replace(
			'atMultilingualAttribute',
			array(
				'avID' => $this->getAttributeValueID(),
				'json' => empty($localizedValues) ? '{}' : Loader::helper('json')->encode($localizedValues)
			),
			'avID',
			true
		);
	}

	/** Delete the value from the DB */
	public function deleteValue() {
		Loader::db()->Execute('delete from atMultilingualAttribute where avID = ?', array($this->getAttributeValueID()));
	}

	/** Loads the attribute from the DB
	* @return MultilingualAttributeAttributeTypeValue
	*/
	public function getValue() {
		$associatedObject = null;
		$av = $this->getAttributeValue();
		if(is_a($av, 'CollectionAttributeValue')) {
			/* @var $av CollectionAttributeValue */
			$associatedObject = $av->getCollection();
		}
		elseif(is_a($av, 'FileAttributeValue')) {
			/* @var $av FileAttributeValue */
			$associatedObject = $av->getFile();
		}
		elseif(is_a($av, 'UserAttributeValue')) {
			/* @var $av UserAttributeValue */
			$associatedObject = $av->getUser();
		}
		$v = MultilingualAttributeAttributeTypeValue::getByID($this->getAttributeValueID(), $this, $associatedObject);
		return $v ? $v : MultilingualAttributeAttributeTypeValue::getEmpty($this, $associatedObject);
	}
	
	public function getDisplaySanitizedValue() {
		return $this->getValue()->toHTML();
	}
	public function getDisplayValue() {
		return $this->getValue()->toHTML();
	}
	public function getUserValue() {
		return $this->getValue()->getDisplayValue();
	}
	
	// Search-related stuff

	/** The definition of the search field
	* @var array
	*/
	protected $searchIndexFieldDefinition = array(
		'searchtext' => 'X2 NULL'
	);

	/** Returns the searchable text associated to this attribute
	* @return array
	*/
	public function getSearchIndexValue() {
		$valuesArray = array();
		$value = $this->getValue();
		foreach(self::getAvailableLanguages(true) as $localeID) {
			$localizedValue = $value->getLocalizedValueFor($localeID);
			if(strlen($localizedValue)) {
				if($this->getType() === self::VALUETYPE_HTML) {
					$localizedValue = trim(strip_tags($localizedValue));
				}
				if(strlen($localizedValue)) {
					$valuesArray[] = "_StartOf_$localeID::$localizedValue::_EndOf_$localeID";
				}
			}
		}
		return array('searchtext' => implode("\n", $valuesArray));
	}

	/** Renders the search form */
	public function search() {
		echo $this->form();
		$this->set('search', true);
		$this->getView()->render('search_form');
	}

	/** Apply the filter specified in the search form
	* @param unknown $list
	* @return unknown
	*/
	public function searchForm($list) {
		$localeID = $this->request('searchlocale');
		if(!array_key_exists($localeID, self::getAvailableLanguages())) {
			$localeID = '';
		}
		$text = $this->request('searchtext');
		$text = is_string($text) ? trim($text) : '';
		if(strlen($localeID) || strlen($text)) {
			$q = strlen($text) ? "%$text%" : '%';
			if(strlen($localeID)) {
				$q = "%_StartOf_$localeID::$q::_EndOf_$localeID%";
			}
			$list->filterByAttribute(array('searchtext' => $this->attributeKey->getAttributeKeyHandle()), $q, 'like');
		}
		return $list;
	}

	/** Returns the query chunk to search for text
	* @param string $keywords
	* @return string
	*/
	public function searchKeywords($keywords) {
		return '(ak_' . $this->getAttributeKey()->getAttributeKeyHandle() . '_searchtext like ' . Loader::db()->quote('%' . $keywords . '%') . ')';
	}

	// 

	protected function loadOptions($forceReload = false) {
		static $loaded = false;
		if((!$loaded) || $forceReload) {
			$this->akType = self::VALUETYPE_TEXT;
			$this->akAssociatedAttribute = '';
			$ak = $this->getAttributeKey();
			if(is_object($ak) && (!$ak->isError())) {
				$row = Loader::db()->GetRow('select * from atMultilingualAttributeOptions where akID = ?', $ak->getAttributeKeyID());
				if(array_key_exists($row['akType'], self::getSelectableTypes())) {
					$this->akType =  $row['akType'];
				}
				if(is_string($row['akAssociatedAttribute'])) {
					$this->akAssociatedAttribute = $row['akAssociatedAttribute'];
				}
			}
		}
	}

	// Import-export keys

	/** Export the attribute definition
	* @param SimpleXMLElement $attributeKeyNode The node to which we'll append the attribute key data
	* @return SimpleXMLElement Returns $attributeKeyNode
	*/
	public function exportKey($attributeKeyNode) {
		$type = $attributeKeyNode->addChild('type');
		$type->addAttribute('type', $this->getType());
		$type->addAttribute('associated-attribute', $this->getAssociatedAttribute());
		return $attributeKeyNode;
	}

	/** Import the attribute definition
	* @param SimpleXMLElement $attributeKeyNode
	*/
	public function importKey($attributeKeyNode) {
		if(isset($attributeKeyNode->type)) {
			$this->saveKey(array(
				'akType' => strval($attributeKeyNode->type['type']),
				'akAssociatedAttribute' => strval($attributeKeyNode->type['associated-attribute'])
			));
		}
	}

	// Import-export values

	/** Export the attribute value 
	* @param SimpleXMLElement $attributeKeyNode The XML node to add the value to
	*/
	public function exportValue($attributeKeyNode) {
		$node = $attributeKeyNode->addChild('value');
		$value = $this->getValue();
		$ch = null;
		foreach(self::getAvailableLanguages(true) as $localeID) {
			$localizedValue = $value->getLocalizedValueFor($localeID);
			switch($this->getType()) {
				case self::VALUETYPE_HTML:
					if(is_null($ch)) {
						$ch = Loader::helper('content');
					}
					/* @var $ch ContentHelper */
					$localizedValue = $ch->export($localizedValue);
					break;
			}
			if(strlen($localizedValue)) {
				$node->addAttribute($localeID, $localizedValue);
			}
		}
	}

	/** Import the attribute value
	* @param SimpleXMLElement $attributeKeyNode
	* @return NULL|multitype:string
	*/
	public function importValue($attributeKeyNode) {
		if(!isset($attributeKeyNode->value)) {
			return null;
		}
		$ch = null;
		$dictionary = array();
		foreach(self::getAvailableLanguages(true) as $localeID) {
			$v = @strval($attributeKeyNode->value[$localeID]);
			if(strlen($v)) {
				switch($this->getType()) {
					case self::VALUETYPE_HTML:
						if(is_null($ch)) {
							$ch = Loader::helper('content');
						}
						/* @var $ch ContentHelper */
						$v = $ch->import($v);
						break;
				}
				if(strlen($v)) {
					$dictionary[$localeID] = $v;
				}
			}
		}
		return array(
			'json' => empty($dictionary) ? '{}' : Loader::helper('json')->encode($dictionary)
		);
	}

	// Helper functions
	
	/** Returns the available field types
	 * @param bool $onlyKeys = false Set to true to retrieve only the type IDs; set to false (default) to retrieve a list of id - name
	 * @return array
	 */
	public static function getSelectableTypes($onlyKeys = false) {
		static $types;
		if(!isset($types)) {
			$types = array(
				self::VALUETYPE_TEXT => t('Text'),
				self::VALUETYPE_TEXTAREA => t('Text Area'),
				self::VALUETYPE_HTML => t('Rich Text')
			);
		}
		if($onlyKeys) {
			$keys = array_keys($types);
			return $keys;
		}
		else {
			return $types;
		}
	}
	
	/** Returns the currently available locales
	 * @param bool $onlyKeys = false Set to true to retrieve only the locale IDs; set to false (default) to retrieve a list of id - name
	 * @return array
	 */
	public static function getAvailableLanguages($onlyKeys = false) {
		static $langs;
		if(!isset($langs)) {
			$langs = Localization::getAvailableInterfaceLanguageDescriptions(Localization::activeLocale());
		}
		if($onlyKeys) {
			$keys = array_keys($langs);
			natcasesort($keys);
			return $keys;
		}
		else {
			return $langs;
		}
	}
	
	/** Returns the flag associated to a locale (if found)
	 * @param string $localeID The ID of the locale
	 * @param bool $buildHtml Set to true to retrieve the HTML code for the flag; set to false to retrieve only the relative URL of the flag
	 * @return string Returns an empty string if the flag is not found; the flag URL or HTML if found
	 */
	public static function getLocaleFlag($localeID, $buildHtml) {
		static $hasMultilingual;
		if(!isset($hasMultilingual)) {
			$pkg = Package::getByHandle('multilingual');
			$hasMultilingual = (is_object($pkg) && !$pkg->isError()) ? true : false;
		}
		$flag = '';
		if($hasMultilingual) {
			if(preg_match('/^[a-z]+_([A-Z]+)/i', $localeID, $m)) {
				$region = strtolower($m[1]);
				$finalPath = '/' . DIRNAME_IMAGES . '/' . DIRNAME_IMAGES_LANGUAGES . '/' . $region . '.png';
				if(is_file(DIR_BASE . $finalPath)) {
					$flag = DIR_REL . $finalPath;
				}
				else {
					$finalPath = '/multilingual/' . DIRNAME_IMAGES . '/' . DIRNAME_IMAGES_LANGUAGES . '/' . $region . '.png';
					if(is_file(DIR_PACKAGES_CORE . $finalPath)) {
						$flag = ASSETS_URL . '/' . DIRNAME_PACKAGES . $finalPath;
					}
					else {
						$finalPath = '/' . DIRNAME_PACKAGES . '/multilingual/' . DIRNAME_IMAGES . '/' . DIRNAME_IMAGES_LANGUAGES . '/' . $region . '.png';
						if(is_file(DIR_BASE . $finalPath)) {
							$flag = DIR_REL . $finalPath;
						}
					}
				}
			}
		}
		if(strlen($flag) && $buildHtml) {
			$flag = '<img src="' . $flag . '" alt="' . h($localeID) . '" style="vertical-align:middle" />';
		}
		return $flag;
	}
}

class MultilingualAttributeAttributeTypeValue extends Object {

	/** The attribute value ID
	* @var int|null
	*/
	protected $avID;

	/** The attribute controller
	* @var MultilingualAttributeAttributeTypeController
	*/
	protected $controller;

	/** The object associated to this attribute
	* @var Collection|File|UserInfo|null
	*/
	protected $associatedObject;

	/** The array with localeID-values
	* @var array
	*/
	protected $dictionary;

	/** Initializes the instance
	 * 
	* @param int|null $avID The attribute value ID
	* @param MultilingualAttributeAttributeTypeController $controller The attribute controller
	* @param string $json The JSON representation of the array with localeID-values
	* @param Collection|File|UserInfo|null $associatedObject The object associated to this attribute
	 */
	protected function __construct($avID, $controller, $json, $associatedObject) {
		$this->avID = $avID;
		$this->controller = $controller;
		$this->dictionary = Loader::helper('json')->decode($json, true);
		$this->associatedObject = $associatedObject;
		if(!is_array($this->dictionary)) {
			$this->dictionary = array();
		}
	}

	/** Returns an empty instance
	* @param MultilingualAttributeAttributeTypeController $controller The attribute controller
	* @param Collection|File|UserInfo|null $associatedObject The object associated to this attribute
	* @return MultilingualAttributeAttributeTypeValue
	*/
	public static function getEmpty($controller, $associatedObject = null) {
		return new MultilingualAttributeAttributeTypeValue(null, $controller, '{}', $associatedObject);
	}

	/** Returns the instance associated to the specified attribute value
	* @param int $avID The attribute value ID
	* @param MultilingualAttributeAttributeTypeController $controller The attribute controller
	* @param Collection|File|UserInfo|null $associatedObject The object associated to this attribute
	* @return MultilingualAttributeAttributeTypeValue
	*/
	public static function getByID($avID, $controller, $associatedObject = null) {
		$db = Loader::db();
		$row = $db->GetRow("select avID, json from atMultilingualAttribute where avID = ?", array($avID));
		if(!$row) {
			return null;
		}
		return new MultilingualAttributeAttributeTypeValue(intval($row['avID']), $controller, $row['json'], $associatedObject);
	}

	/** Return the localized value as stored in DB
	* @param string $localeID The locale for which you want the value
	* @return string
	 */
	public function getLocalizedValueFor($localeID) {
		return (array_key_exists($localeID, $this->dictionary) && is_string($this->dictionary[$localeID])) ? $this->dictionary[$localeID] : '';
	}
	
	public function getFinalValue() {
		$v = $this->getLocalizedValueFor(Localization::activeLocale());
		if(strlen($v)) {
			switch($this->controller->akType) {
				case MultilingualAttributeAttributeTypeController::VALUETYPE_HTML:
					return Loader::helper('content')->translateFrom($v);
				case MultilingualAttributeAttributeTypeController::VALUETYPE_TEXTAREA:
					return nl2br(h($v));
				default:
					return h($v);
			}
		}
		$fallbackAttribute = $this->controller->getAssociatedAttribute();
		if(is_object($this->associatedObject) && strlen($fallbackAttribute)) {
			$object = $this->associatedObject;
			if(is_a($object, 'File')) {
				$object = $object->getApprovedVersion();
				if((!is_object($object)) || $object->isError()) {
					$object = null;
				}
				switch($fallbackAttribute) {
					case '*title*':
						$fallbackAttribute = '';
						if(is_object($object)) {
							$v = $object->getTitle();
						}
						break;
					case '*description*':
						$fallbackAttribute = '';
						if(is_object($object)) {
							$v = $object->getDescription();
						}
						break;
				}
			}
			if(is_object($object) && strlen($fallbackAttribute)) {
				$v = $fv->getAttribute($fallbackAttribute, 'display');
			}
		}
		return is_string($v) ? $v : '';
	}

	/**
	* @return string
	*/
	public function __toString() {
		$l = array();
		foreach(MultilingualAttributeAttributeTypeController::getAvailableLanguages() as $localeID => $localeName) {
			$v = $this->getLocalizedValueFor($localeID);
			if(strlen($v)) {
				$l[] = "$localeName: $v";
			}
		}
		return implode("\n", $l);
	}

	/**
	* @return string
	*/
	public function toHTML() {
		$allHaveFlags = true;
		$l = array();
		foreach(MultilingualAttributeAttributeTypeController::getAvailableLanguages() as $localeID => $localeName) {
			$v = $this->getLocalizedValueFor($localeID);
			if(strlen($v)) {
				switch($this->controller->getType()) {
					case MultilingualAttributeAttributeTypeController::VALUETYPE_HTML:
						$v = Loader::helper('content')->translateFrom($v);
						break;
					case MultilingualAttributeAttributeTypeController::VALUETYPE_TEXTAREA:
						$v = nl2br(h($v));
						break;
					default:
						$v = h($v);
						break;
				}
				$flag = MultilingualAttributeAttributeTypeController::getLocaleFlag($localeID, true);
				if(!strlen($flag)) {
					$allHaveFlags = false;
				}
				$l[] = array('localeID' => $localeID, 'localeName' => $localeName, 'html' => $v, 'flag' => $flag);
			}
		}
		$m = array();
		foreach($l as $i) {
			if($allHaveFlags) {
				$m[] = '<span title="' . h($i['localeName']) . '">' . $i['flag'] . ' ' . $i['html'] . '</span>';
			}
			else {
				$m[] = h($i['localeName']) . ': ' . $i['html'];
			}
		}
		return implode('<br />', $m);
	}

}
