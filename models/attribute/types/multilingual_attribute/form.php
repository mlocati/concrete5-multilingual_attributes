<?php defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this AttributeTypeView */

$fh = Loader::helper('form');
/* @var $fh FormHelper */

$jh = Loader::helper('json');
/* @var $jh JsonHelper */

if($akType === MultilingualAttributeAttributeTypeController::VALUETYPE_HTML) {
	?><script src="<?php echo $this->controller->attributeType->getAttributeTypeFileURL('mce_workaround.js'); ?>"></script><?php
}
?><fieldset>
	<?php
	$firstLocale = true;
	foreach($locales as $localeID => $localeName) {
		?><div style="padding-right:10px;padding-top:4px;padding-bottom:4px;<?php echo $firstLocale ? '' : 'border-top:1px dotted #aaa'?>"><?php
			$fieldID = $this->field($localeID);
			echo $fh->label($fieldID, $localeName);
			switch($akType) {
				case MultilingualAttributeAttributeTypeController::VALUETYPE_TEXTAREA:
					echo $fh->textarea($fieldID, $localizedValues[$localeID]);
					break;
				case MultilingualAttributeAttributeTypeController::VALUETYPE_HTML:
					Loader::element('editor_config');
					$mceFieldID = str_replace(']', '_', str_replace('[', '_', $fieldID));
					echo Loader::helper('form')->textarea($mceFieldID, Loader::helper('content')->translateFromEditMode($localizedValues[$localeID]), array('class' => 'ccm-advanced-editor', 'data-original-field-id' => $fieldID));
					?><script type="text/javascript">
					$(document).ready(function() {
						setTimeout(function() {
							multilingual_attribute_mceWorkaround(<?php echo $jh->encode($mceFieldID); ?>);
						}, 0);
					});
					</script><?php
					break;
				case MultilingualAttributeAttributeTypeController::VALUETYPE_TEXT:
				default:
					echo $fh->text($fieldID, $localizedValues[$localeID]);
					break;
			}
		?></div><?php
		$firstLocale = false;
	}
	?>
</fieldset>