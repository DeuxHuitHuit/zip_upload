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
			if (is_string($data)) {
				// not a new upload
			}
			return parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);
		}
	}