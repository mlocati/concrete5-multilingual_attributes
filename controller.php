<?php defined('C5_EXECUTE') or die('Access denied.');

class MultilingualAttributesPackage extends Package {

	protected $pkgHandle = 'multilingual_attributes';
	protected $appVersionRequired = '5.6.3';
	protected $pkgVersion = '0.9.0';

	public function getPackageName() {
		return t('Multilingual attributes');
	}

	public function getPackageDescription() {
		return t('Allows users to specify attributes in multiple languages.');
	}

	public function install() {
		$pkg = parent::install();
		$this->installReal('', $pkg);
	}

	public function upgrade() {
		$currentVersion = $this->getPackageVersion();
		parent::upgrade();
		$this->installReal($currentVersion, $this);
	}
	private function installReal($fromVersion, $pkg) {
		$fromScratch = ($fromVersion === '') ? true : false;
		if($fromScratch || version_compare($fromVersion, '0.9.0', '<=')) {
			$at = AttributeType::getByHandle('multilingual_attribute');
			if((!is_object($at)) || $at->isError()) {
				$at = AttributeType::add('multilingual_attribute', tc('AttributeTypeName', 'Multilingual attribute'), $pkg);
			}
			$akcCollection = AttributeKeyCategory::getByHandle('collection');
			if(is_object($akcCollection) && (!$akcCollection->isError())) {
				if(!$akcCollection->hasAttributeKeyTypeAssociated($at)) {
					$akcCollection->associateAttributeKeyType($at);
				}
			}
			$akcFile = AttributeKeyCategory::getByHandle('file');
			if(is_object($akcFile) && (!$akcFile->isError())) {
				if(!$akcFile->hasAttributeKeyTypeAssociated($at)) {
					$akcFile->associateAttributeKeyType($at);
				}
			}
			$akcUser = AttributeKeyCategory::getByHandle('user');
			if(is_object($akcUser) && (!$akcUser->isError())) {
				if(!$akcUser->hasAttributeKeyTypeAssociated($at)) {
					$akcUser->associateAttributeKeyType($at);
				}
			}
		}
	}
}
