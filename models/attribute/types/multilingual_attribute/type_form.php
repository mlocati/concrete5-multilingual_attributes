<?php defined('C5_EXECUTE') or die('Access denied.');

$fh = Loader::helper('form');
/* @var $fh FormHelper */

$associableAttributes = array();

if(isset($category) && is_object($category)) {
	/* @var $category AttributeKeyCategory */
	if(array_key_exists($category->getAttributeKeyCategoryHandle(), $specialAssociableAttributes)) {
		$associableAttributes = $specialAssociableAttributes[$category->getAttributeKeyCategoryHandle()];
	}
	switch($category->getAttributeKeyCategoryHandle()) {
		case 'file':
		case 'collection':
		case 'user':
			foreach(AttributeKey::getList($category->getAttributeKeyCategoryHandle()) as $otherAttribute) {
				/* @var $otherAttribute AttributeKey */
				switch($otherAttribute->getAttributeKeyType()->getAttributeTypeHandle()) {
					case 'boolean':
					case 'date_time':
					case 'image_file':
					case 'number':
					case 'rating':
					case 'select':
					case 'address':
					case 'multilingual_attribute':
						break;
					default:
						$associableAttributes[$otherAttribute->getAttributeKeyHandle()] = $otherAttribute->getAttributeKeyDisplayName();
						break;
				}
			}
			break;
	}
}
natcasesort($associableAttributes);
$associableAttributes = array_merge(array('' => tc('No attribute', 'none')), $associableAttributes);
foreach($locales as $localeID => $localeName) {
	$associableAttributes["*$localeID*"] = tc('%s is a language', 'Fallback to %s', $localeName);
}
if(strlen($akAssociatedAttribute) && (!array_key_exists($akAssociatedAttribute, $associableAttributes))) {
	$akAssociatedAttribute = '';
}
if((!isset($akType)) && (!array_key_exists($text, $selectableTypes))) {
	$akType = reset(array_keys($selectableTypes));
}
?>
<fieldset>
	<legend><?php echo t('Attribute options'); ?></legend>
	<div class="clearfix">
		<label><?php echo t('Type')?></label>
		<div class="input">
			<ul class="inputs-list">
				<li><label><?php echo $fh->select(
					'akType',
					$selectableTypes,
					$akType
				); ?></li>
			</ul>
		</div>
	</div>
	<div class="clearfix">
		<label><?php echo t('When not set for a language, use the value of:'); ?></label>
		<div class="input">
			<ul class="inputs-list">
				<li><label><?php echo $fh->select(
					'akAssociatedAttribute',
					$associableAttributes,
					$akAssociatedAttribute
				); ?></li>
			</ul>
		</div>
	</div>
</fieldset>
