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
			Field::displaySettingsPanel($wrapper, $errors);

			// Destination Folder
			$ignore = array(
				'/workspace/events',
				'/workspace/data-sources',
				'/workspace/text-formatters',
				'/workspace/pages',
				'/workspace/utilities'
			);
			$directories = General::listDirStructure(WORKSPACE, null, true, DOCROOT, $ignore);

			$label = Widget::Label(__('Destination Directory'));

			$options = array();
			$destinationFound = false;

			if (!empty($directories) && is_array($directories)) {
				foreach ($directories as $d) {
					$d = '/' . trim($d, '/');

					if (!in_array($d, $ignore)) {
						$isSelected = $this->get('destination') === $d;
						if ($isSelected) {
							$destinationFound = true;
						}
						$options[] = array($d, $isSelected, $d);
					}
				}
			}
			if (!$destinationFound) {
				array_unshift($options, array($this->get('destination'), false, $this->get('destination')));
			}

			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][destination]', $options));

			if (isset($errors['destination'])) {
				$wrapper->appendChild(Widget::Error($label, $errors['destination']));
			} else {
				$wrapper->appendChild($label);
			}

			// Validation rule
			$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]', 'upload', $errors);

			// Requirements and table display
			$this->appendStatusFooter($wrapper);
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
			if ($simulate) {
				return $ret;
			}
			
			// parent went well
			if (is_array($data) && $status === self::__OK__) {
				$existing_file = null;
				$exitingLocations = null;
				
				// Check to see if the entry already has a file associated with it:
				if (is_null($entry_id) === false) {
					$row = Symphony::Database()->fetchRow(0, sprintf(
						"SELECT *
						FROM `tbl_entries_data_%s`
						WHERE `entry_id` = %d
						LIMIT 1",
						$this->get('id'),
						$entry_id
					));

					$existing_file = isset($row['file']) ? $this->getFilePath($row['file']) : null;
					$exitingLocations = $this->getFilesLocations(array('file' => $existing_file));

					// File was removed:
					if (!empty($exitingLocations) &&
						$data['error'] == UPLOAD_ERR_NO_FILE
						&& !is_null($existing_file)
						&& is_dir($exitingLocations['dest'])
					) {
						Symphony::Log()->pushToLog('Attempting to delete removed file ' . $exitingLocations['dest'], E_NOTICE, true);
						General::deleteDirectory($exitingLocations['dest'], false);
					}
				}
				
				// new upload
				if (is_array($data) && !empty($data['tmp_name']) && $data['error'] === UPLOAD_ERR_OK) {
					// Get our locations
					$locations = $this->getFilesLocations($ret);
					// Valid locations ?
					if (empty($locations)) {
						$message = __('Failed to find file location');
						$status = self::__ERROR_CUSTOM__;
						return $ret;
					}
					$file = $locations['file'];
					$dest = $locations['dest'];

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
				
				// File has been replaced:
				if (!empty($exitingLocations) &&
					isset($existing_file)
					&& $existing_file !== $file
					&& is_dir($exitingLocations['dest'])
				) {
					Symphony::Log()->pushToLog('Attempting to replaced removed file ' . $exitingLocations['dest'], E_NOTICE, true);
					General::deleteDirectory($exitingLocations['dest'], false);
				}
			}
			return $ret;
		}
		
		/*-------------------------------------------------------------------------
			Output:
		-------------------------------------------------------------------------*/

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
		{
			if (!is_array($data) || !isset($data['file']) || is_null($data['file'])) {
				return;
			}
			$fakeWrapper = new XMLElement('fake');
			parent::appendFormattedElement($fakeWrapper, $data, $encode, $mode, $entry_id);
			$fieldxml = $fakeWrapper->getChild(0);
			if (!$fieldxml) {
				return;
			}
			
			$locations = $this->getFilesLocations($data);
			if (!empty($locations)) {
				$structure = General::listStructure($locations['dest'], array(), true, 'asc', DOCROOT);
				if ($structure != null) {
					$files = $this->mergeFiles($structure);
					$xmlFiles = new XMLElement('files');
					foreach ($files as $file) {
						$xmlFiles->appendChild(new XMLElement('file', $file));
					}
					$fieldxml->appendChild($xmlFiles);
				}
			}
			else {
				$fieldxml->appendChild(new XMLElement('error'), 'Failed to find file location');
			}
			$wrapper->appendChild($fieldxml);
		}
		
		/*-------------------------------------------------------------------------
			Utilities:
		-------------------------------------------------------------------------*/

		public function getFilesLocations($ret)
		{
			$dest = trim($this->get('destination'), '');
			if (empty($dest)) {
				return null;
			}
			if (!is_array($ret) || empty($ret['file'])) {
				return null;
			}
			$base = basename($ret['file'], '.zip');
			if (empty($base)) {
				return null;
			}
			$root = DOCROOT . $dest . '/';
			return array(
				'file' => $root . $ret['file'],
				'dest' => $root . $base,
			);
		}

		public function entryDataCleanup($entry_id, $data = null)
		{
			parent::entryDataCleanup($entry_id, $data);
			if (is_array($data)) {
				$locations = $this->getFilesLocations($data);
				if (!empty($locations)) {
					Symphony::Log()->pushToLog('Attempting to delete cleanup file ' . $locations['dest'], E_NOTICE, true);
					General::deleteDirectory($locations['dest'], false);
				}
			}
			return true;
		}
		
		private function mergeFiles(array $structure, &$files = array())
		{
			if ($strucutre != null) {
				foreach ($structure['filelist'] as $file) {
					$files[] = $file;
				}
				foreach ($structure['dirlist'] as $dir) {
					$this->mergeFiles($structure[$dir], $files);
				}
			}
			return $files;
		}
		
		private function unzipFile($dest, $file)
		{
			$zip = new ZipArchive();
			if (!$zip->open($file)) {
				return false;
			}
			$zip->extractTo($dest);
			$zip->close();
			$structure = General::listStructure($dest);
			$files = $this->mergeFiles($structure);
			$blacklist = Symphony::Configuration()->get('upload_blacklist', 'admin');
			foreach ($files as $file) {
				if (!empty($blacklist) && General::validateString($file, $blacklist)) {
					@unlink($file);
				}
			}
			return true;
		}
	}