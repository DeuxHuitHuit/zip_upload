<?php
	/*
	Copyright: Deux Huit Huit 2016
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	/**
	 *
	 * Field class that will represent relationships between entries
	 * @author Deux Huit Huit
	 *
	 */
	class FieldZip_Upload extends FieldUpload
	{
		/*------------------------------------------------------------------------------------------------*/
		/*  Definition  */
		/*------------------------------------------------------------------------------------------------*/
		
		public function __construct()
		{
			parent::__construct();
			$this->_name = __('Zip Upload');
		}
		
		public function get($key)
		{
			if ($key === 'validator') {
				return '/\.(?:zip)$/i';
			}
			return parent::get($key);
		}
		
		/*------------------------------------------------------------------------------------------------*/
		/*  Settings  */
		/*------------------------------------------------------------------------------------------------*/
		
		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
		{
			parent::displaySettingsPanel($wrapper, $errors);
		}
		
		public function buildValidationSelect(XMLElement &$wrapper, $selected = null, $name = 'fields[validator]', $type = 'input', array $errors = null)
		{
			// do nothing
		}
		
		/*------------------------------------------------------------------------------------------------*/
		/*  Input  */
		/*------------------------------------------------------------------------------------------------*/
		
		public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
		{
			$ret = parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);
			// new upload
			if ($status === self::__OK__ && is_array($data) && !empty($data['tmp_name']) && $data['error'] === UPLOAD_ERR_OK) {
				$root = DOCROOT . trim($this->get('destination'), '') . '/';
				$file = $root . $ret['file'];
				$dest = $root . basename($ret['file'], '.zip');
				if (@file_exists($dest)) {
					$message = __("Destination folder `%s` already exists.", array($dest));
					$status = self::__ERROR_CUSTOM__;
					return $ret;
				}
				General::realiseDirectory($dest, Symphony::Configuration()->get('write_mode', 'directory'));
				if (!$this->unzipFile($dest, $file)) {
					$message = __("Failed to unzip `%s`.", array(basename($file)));
					$status = self::__ERROR_CUSTOM__;
					return $ret;
				}
			}
			return $ret;
		}
		
		private function unzipFile($dest, $file)
		{
			$zip = new ZipArchive();
			if (!$zip->open($file)) {
				return false;
			}
			$zip->extractTo($dest);
			$zip->close();
			return true;
		}
	}