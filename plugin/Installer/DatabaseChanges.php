<?php

/**
 *
 *    Copyright (C) 2019 onOffice GmbH
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace onOffice\WPlugin\Installer;

use onOffice\WPlugin\DataView\DataSimilarView;
use onOffice\WPlugin\WP\WPOptionWrapperBase;
use wpdb;
use function dbDelta;
use function esc_sql;
use const ABSPATH;

class DatabaseChanges implements DatabaseChangesInterface
{
	/** @var int */
	const MAX_VERSION = 18;

	/** @var WPOptionWrapperBase */
	private $_pWpOption;

	/** @var wpdb */
	private $_pWPDB;

	/**
	 * @param WPOptionWrapperBase $pWpOption
	 */
	public function __construct(WPOptionWrapperBase $pWpOption, \wpdb $pWPDB)
	{
		$this->_pWpOption = $pWpOption;
		$this->_pWPDB = $pWPDB;
	}

	/**
	 *
	 */
	public function install()
	{
		if (get_site_option('oo_plugin_db_version') == DatabaseChanges::MAX_VERSION) {
			return;
		}
		// If you are modifying this, please also make sure to edit the test
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$dbversion = $this->_pWpOption->getOption('oo_plugin_db_version', null);
		if ($dbversion === null) {
			dbDelta( $this->getCreateQueryCache() );

			$dbversion = 1.0;
			$this->_pWpOption->addOption( 'oo_plugin_db_version', $dbversion, true );
		}

		if ($dbversion == 1.0) {
			dbDelta( $this->getCreateQueryListviews() );
			dbDelta( $this->getCreateQueryFieldConfig() );
			dbDelta( $this->getCreateQueryPictureTypes() );
			dbDelta( $this->getCreateQueryListViewContactPerson() );
			dbDelta( $this->getCreateQueryForms() );
			dbDelta( $this->getCreateQueryFormFieldConfig() );

			$dbversion = 2.0;
		}

		if ($dbversion == 2.0) {
			dbDelta( $this->getCreateQueryListViewsAddress() );
			dbDelta( $this->getCreateQueryAddressFieldConfig() );
			$dbversion = 3.0;
		}

		if ($dbversion == 3.0) {
			// new column: captcha
			dbDelta( $this->getCreateQueryForms() );
			$dbversion = 4;
		}

		if ($dbversion == 4.0) {
			// new column: newsletter
			dbDelta( $this->getCreateQueryForms() );
			$dbversion = 5;
		}

		if ($dbversion == 5.0) {
			// new columns: filterable, hidden
			dbDelta( $this->getCreateQueryAddressFieldConfig() );
			$dbversion = 6;
		}

		if ($dbversion == 6.0) {
			// new columns: availableOptions
			dbDelta( $this->getCreateQueryFieldConfig() );
			dbDelta( $this->getCreateQueryFormFieldConfig() );
			$dbversion = 7;
		}

		if ($dbversion >= 7.0 && $dbversion <= 11) {
			// version 8: new columns: {country,zip,street,radius}_active
			// version 9: new columns: radius
			// version 10 new columns: city_active
			// version 11: new columns: geo_order
			// version 12: new column in getCreateQueryForms: show_estate_context
			dbDelta( $this->getCreateQueryListviews() );
			dbDelta( $this->getCreateQueryForms() );
			$dbversion = 12;
		}

		if ( $dbversion == 12 || $dbversion == 13)	{
			dbDelta( $this->getCreateQueryListviews() );
			dbDelta( $this->getCreateQuerySortByUserValues() );
			$dbversion = 14;
		}

		if ( $dbversion == 14) {
			$this->updateSortByUserDefinedDefault();
			$dbversion = 15;
		}

		if ($dbversion == 15) {
			dbDelta( $this->getCreateQueryFieldConfigDefaults() );
			dbDelta( $this->getCreateQueryFieldConfigDefaultsValues() );
			$dbversion = 16;
		}

		if ($dbversion == 16) {
			$this->migrationsDataSimilarEstates();
			$dbversion = 17;
		}

		if ($dbversion == 17) {
			dbDelta($this->getCreateQueryFieldConfigCustomsLabels());
			dbDelta($this->getCreateQueryFieldConfigTranslatedLabels());
			$dbversion = 18;
		}

		$this->_pWpOption->updateOption( 'oo_plugin_db_version', $dbversion, true);
	}

	/**
	 * @return mixed
	 */
	public function getDbVersion()
	{
		return $this->_pWpOption->getOption('oo_plugin_db_version', null);
	}

	/**
	 *
	 * @return string
	 *
	 */

	private function getCreateQueryCache()
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_cache";
		$sql = "CREATE TABLE $tableName (
			cache_id bigint(20) NOT NULL AUTO_INCREMENT,
			cache_parameters text NOT NULL,
			cache_parameters_hashed varchar(32) NOT NULL,
			cache_response mediumtext NOT NULL,
			cache_created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (cache_id),
			UNIQUE KEY cache_parameters_hashed (cache_parameters_hashed),
			KEY cache_created (cache_created)
		) $charsetCollate;";

		return $sql;
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getCreateQueryListviews()
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_listviews";
		$sql = "CREATE TABLE $tableName (
			`listview_id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(191) NOT NULL,
			`filterId` int(11),
			`sortby` tinytext NOT NULL,
			`sortorder` enum('ASC','DESC') NOT NULL DEFAULT 'ASC',
			`show_status` tinyint(1) NOT NULL DEFAULT '0',
			`list_type` ENUM('default', 'reference', 'favorites', 'units') NOT NULL DEFAULT 'default',
			`template` tinytext NOT NULL,
			`expose` tinytext,
			`recordsPerPage` INT( 10 ) NOT NULL DEFAULT '10',
			`random` tinyint(1) NOT NULL DEFAULT '0',
			`country_active` tinyint(1) NOT NULL DEFAULT '1',
			`zip_active` tinyint(1) NOT NULL DEFAULT '1',
			`city_active` tinyint(1) NOT NULL DEFAULT '0',
			`street_active` tinyint(1) NOT NULL DEFAULT '1',
			`radius_active` tinyint(1) NOT NULL DEFAULT '1',
			`radius` INT( 10 ) NULL DEFAULT NULL,
			`geo_order` VARCHAR( 255 ) NOT NULL DEFAULT 'street,zip,city,country,radius',
			`sortBySetting` ENUM('0','1') NOT NULL DEFAULT '0' COMMENT 'Sortierung nach Benutzerwahl: 0 means preselected, 1 means userDefined',
			`sortByUserDefinedDefault` VARCHAR(200) NOT NULL COMMENT 'Standardsortierung',
			`sortByUserDefinedDirection` ENUM('0','1') NOT NULL DEFAULT '0' COMMENT 'Formulierung der Sortierrichtung: 0 means highestFirst/lowestFirt, 1 means descending/ascending',
			PRIMARY KEY (`listview_id`),
			UNIQUE KEY `name` (`name`)
		) $charsetCollate;";

		return $sql;
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getCreateQueryForms()
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_forms";
		$sql = "CREATE TABLE $tableName (
			`form_id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(191) NOT NULL,
			`form_type` enum('owner', 'interest', 'contact', 'applicantsearch') NOT NULL DEFAULT 'contact',
			`template` tinytext NOT NULL,
			`recipient` varchar(255) NULL,
			`subject` mediumtext NULL,
			`createaddress` tinyint(1) NOT NULL DEFAULT '0',
			`limitresults` int,
			`checkduplicates` tinyint(1) NOT NULL DEFAULT '0',
			`pages` int NOT NULL DEFAULT '0',
			`captcha` tinyint(1) NOT NULL DEFAULT '0',
			`newsletter` tinyint(1) NOT NULL DEFAULT '0',
			`country_active` tinyint(1) NOT NULL DEFAULT '1',
			`zip_active` tinyint(1) NOT NULL DEFAULT '1',
			`city_active` tinyint(1) NOT NULL DEFAULT '0',
			`street_active` tinyint(1) NOT NULL DEFAULT '1',
			`radius_active` tinyint(1) NOT NULL DEFAULT '1',
			`radius` INT( 10 ) NULL DEFAULT NULL,
			`geo_order` VARCHAR( 255 ) NOT NULL DEFAULT 'street,zip,city,country,radius',
			`show_estate_context` tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (`form_id`),
			UNIQUE KEY `name` (`name`)
		) $charsetCollate;";

		return $sql;
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getCreateQueryFieldConfig()
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_fieldconfig";
		$sql = "CREATE TABLE $tableName (
			`fieldconfig_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`listview_id` int(11) NOT NULL,
			`order` int(11) NOT NULL,
			`fieldname` tinytext NOT NULL,
			`filterable` tinyint(1) NOT NULL DEFAULT '0',
			`hidden` tinyint(1) NOT NULL DEFAULT '0',
			`availableOptions` tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (`fieldconfig_id`)
		) $charsetCollate;";

		return $sql;
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getCreateQueryFormFieldConfig()
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_form_fieldconfig";
		$sql = "CREATE TABLE $tableName (
			`form_fieldconfig_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`form_id` int(11) NOT NULL,
			`order` int(11) NOT NULL,
			`fieldname` tinytext NOT NULL,
			`fieldlabel` varchar(255) NULL,
			`module` tinytext NULL,
			`individual_fieldname` tinyint(1) NOT NULL DEFAULT '0',
			`required` tinyint(1) NOT NULL DEFAULT '0',
			`availableOptions` tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (`form_fieldconfig_id`)
		) $charsetCollate;";

		return $sql;
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getCreateQueryAddressFieldConfig()
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_address_fieldconfig";
		$sql = "CREATE TABLE $tableName (
			`address_fieldconfig_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`listview_address_id` int(11) NOT NULL,
			`order` int(11) NOT NULL,
			`fieldname` tinytext NOT NULL,
			`filterable` tinyint(1) NOT NULL DEFAULT '0',
			`hidden` tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (`address_fieldconfig_id`)
		) $charsetCollate;";

		return $sql;
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getCreateQueryListViewContactPerson()
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_listview_contactperson";
		$sql = "CREATE TABLE $tableName (
				`contactperson_id` int(11) NOT NULL AUTO_INCREMENT,
				`listview_id` int(11) NOT NULL,
				`order` int(11) NOT NULL,
				`fieldname` tinytext NOT NULL,
				PRIMARY KEY (`contactperson_id`)
		) $charsetCollate;";

		return $sql;
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getCreateQueryPictureTypes()
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_picturetypes";
		$sql = "CREATE TABLE $tableName (
			`picturetype_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`listview_id` int(11) NOT NULL,
			`picturetype` tinytext NOT NULL,
			PRIMARY KEY (`picturetype_id`)
		) $charsetCollate;";

		return $sql;
	}

	/**
	 *
	 * @return string
	 *
	 */

	private function getCreateQuerySortByUserValues()
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_sortbyuservalues";
		$sql = "CREATE TABLE $tableName (
			`sortbyvalue_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`listview_id` int(11) NOT NULL,
			`sortbyuservalue` varchar(100) NOT NULL,
			PRIMARY KEY (`sortbyvalue_id`)
		) $charsetCollate;";

		return $sql;
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getCreateQueryListViewsAddress()
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_listviews_address";
		$sql = "CREATE TABLE $tableName (
			`listview_address_id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(191) NOT NULL,
			`filterId` int(11) DEFAULT NULL,
			`sortby` tinytext NOT NULL,
			`sortorder` enum('ASC','DESC') NOT NULL DEFAULT 'ASC',
			`template` tinytext NOT NULL,
			`recordsPerPage` int(10) NOT NULL DEFAULT '10',
			`showPhoto` tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (`listview_address_id`),
			UNIQUE KEY `name` (`name`)
		) $charsetCollate;";

		return $sql;
	}

	/**
	 * @return string
	 */
	private function getCreateQueryFieldConfigDefaults(): string
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_fieldconfig_form_defaults";
		$sql = "CREATE TABLE $tableName (
			`defaults_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`form_id` bigint(20) NOT NULL,
			`fieldname` tinytext NOT NULL,
			PRIMARY KEY (`defaults_id`)
		) $charsetCollate;";

		return $sql;
	}

	/**
	 * @return string
	 */
	private function getCreateQueryFieldConfigDefaultsValues(): string
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix."oo_plugin_fieldconfig_form_defaults_values";
		$sql = "CREATE TABLE $tableName (
			`defaults_values_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`defaults_id` bigint(20) NOT NULL,
			`locale` tinytext NULL DEFAULT NULL,
			`value` text,
			PRIMARY KEY (`defaults_values_id`)
		) $charsetCollate;";

		return $sql;
	}

	/**
	 * @return string
	 */
	private function getCreateQueryFieldConfigCustomsLabels(): string
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix . "oo_plugin_fieldconfig_form_customs_labels";
		$sql = "CREATE TABLE $tableName (
			`customs_labels_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`form_id` bigint(20) NOT NULL,
			`fieldname` tinytext NOT NULL,
			PRIMARY KEY (`customs_labels_id`)
		) $charsetCollate;";

		return $sql;
	}

	/**
	 * @return string
	 */
	private function getCreateQueryFieldConfigTranslatedLabels(): string
	{
		$prefix = $this->getPrefix();
		$charsetCollate = $this->getCharsetCollate();
		$tableName = $prefix . "oo_plugin_fieldconfig_form_translated_labels";
		$sql = "CREATE TABLE $tableName (
			`translated_label_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`input_id` bigint(20) NOT NULL,
			`locale` tinytext NULL DEFAULT NULL,
			`value` text,
			PRIMARY KEY (`translated_label_id`)
		) $charsetCollate;";

		return $sql;
	}


	/**
	 *
	 */

	private function updateSortByUserDefinedDefault()
	{
		$prefix = $this->getPrefix();
		$tableName = $prefix."oo_plugin_listviews";

		$this->_pWPDB->query("UPDATE $tableName 
				SET `sortByUserDefinedDefault` = CONCAT(`sortByUserDefinedDefault`, '#ASC') 
				WHERE `sortByUserDefinedDefault` != '' AND
				`sortByUserDefinedDefault`  NOT LIKE '%#ASC' AND 
				`sortByUserDefinedDefault` NOT LIKE '%#DESC'");
	}

	/**
	 *
	 */

	public function migrationsDataSimilarEstates()
	{
		$pDataDetailViewOptions = get_option('onoffice-default-view');
		if (!empty($pDataDetailViewOptions)) {
			$dataDetailViewActive = $pDataDetailViewOptions->getDataDetailViewActive();

			$dataDetailViewSimilarEstates = $pDataDetailViewOptions->getDataViewSimilarEstates();
			$pDataSimilarViewOptions = new DataSimilarView();
			$pDataSimilarViewOptions->setDataSimilarViewActive($dataDetailViewActive);

			$pDataViewSimilarEstatesNew = $pDataSimilarViewOptions->getDataViewSimilarEstates();
			$pDataViewSimilarEstatesNew->setFields($dataDetailViewSimilarEstates->getFields());
			$pDataViewSimilarEstatesNew->setSameEstateKind($dataDetailViewSimilarEstates->getSameEstateKind());
			$pDataViewSimilarEstatesNew->setSameMarketingMethod($dataDetailViewSimilarEstates->getSameMarketingMethod());
			$pDataViewSimilarEstatesNew->setSamePostalCode($dataDetailViewSimilarEstates->getSamePostalCode());
			$pDataViewSimilarEstatesNew->setRadius($dataDetailViewSimilarEstates->getRadius());
			$pDataViewSimilarEstatesNew->setRecordsPerPage($dataDetailViewSimilarEstates->getRecordsPerPage());
			$pDataViewSimilarEstatesNew->setTemplate($dataDetailViewSimilarEstates->getTemplate());
			$pDataSimilarViewOptions->setDataViewSimilarEstates($pDataViewSimilarEstatesNew);
			$this->_pWpOption->addOption('onoffice-similar-estates-settings-view', $pDataSimilarViewOptions);
		}
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getPrefix()
	{
		return $this->_pWPDB->prefix;
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getCharsetCollate()
	{
		return $this->_pWPDB->get_charset_collate();
	}


	/**
	 *
	 * Callback for plugin uninstall hook
	 *
	 * @global wpdb $wpdb
	 *
	 */

	public function deinstall()
	{
		$prefix = $this->getPrefix();
		$tables = array(
			$prefix."oo_plugin_cache",
			$prefix."oo_plugin_listviews",
			$prefix."oo_plugin_fieldconfig",
			$prefix."oo_plugin_picturetypes",
			$prefix."oo_plugin_forms",
			$prefix."oo_plugin_form_fieldconfig",
			$prefix."oo_plugin_listview_contactperson",
			$prefix."oo_plugin_listviews_address",
			$prefix."oo_plugin_address_fieldconfig",
			$prefix."oo_plugin_sortbyuservalues",
			$prefix."oo_plugin_fieldconfig_form_defaults",
			$prefix."oo_plugin_fieldconfig_form_defaults_values",
			$prefix."oo_plugin_fieldconfig_form_customs_labels",
			$prefix."oo_plugin_fieldconfig_form_translated_labels",
		);

		foreach ($tables as $table)	{
			$this->_pWPDB->query("DROP TABLE IF EXISTS ".esc_sql($table));
		}

		$this->_pWpOption->deleteOption('oo_plugin_db_version');
	}
}