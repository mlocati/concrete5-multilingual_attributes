<select name="<?php echo $this->field('searchlocale'); ?>">
	<option value="*">all languages</option>
	<?php
	foreach($locales as $localeID => $localeName) {
		?><option value="<?php echo h($localeID) ?>"><?php echo tc('Only for language', 'Only for %s', $localeName); ?></option><?php
	}
	?>
</select>
<input type="text" name="<?php echo $this->field('searchtext'); ?>" placeholder="<?php echo t('Enter search text'); ?>" />