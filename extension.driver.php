<?php
	/*
	Copyight: Deux Huit Huit 2016
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/
	
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");
	
	/**
	 *
	 * @author Deux Huit Huit
	 * https://deuxhuithuit.com/
	 *
	 */
	class extension_zip_upload extends Extension {

		/**
		 * Name of the extension
		 * @var string
		 */
		const EXT_NAME = 'Field: Zip Upload';
		
		/* ********* INSTALL/UPDATE/UNINSTALL ******* */

		/**
		 * Creates the table needed for the settings of the field
		 */
		public function install() {
			// create table "alias"
			return Administration::instance()->Database()->query("CREATE OR REPLACE VIEW `tbl_fields_zip_upload` AS
				SELECT * FROM `tbl_fields_upload`;");
		}

		/**
		 * This method will update the extension according to the
		 * previous and current version parameters.
		 * @param string $previousVersion
		 */
		public function update($previousVersion = false) {
			$ret = true;
			return $ret;
		}

		public function uninstall() {
			Administration::instance()->Database()->query("DROP VIEW IF EXISTS `tbl_fields_zip_upload`");
			return true;
		}

	}