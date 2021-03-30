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

declare (strict_types=1);

namespace onOffice\tests;

use onOffice\WPlugin\Installer\DatabaseChanges;
use onOffice\WPlugin\Utility\__String;
use onOffice\WPlugin\WP\WPOptionWrapperTest;
use WP_UnitTestCase;
use function add_filter;
use function get_option;
use function update_option;
use function remove_filter;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2018, onOffice(R) GmbH
 *
 * @covers onOffice\WPlugin\Installer\DatabaseChanges
 *
 * Please note $wpdb won't create any tables in testing mode
 *
 */

class TestClassDatabaseChanges
	extends WP_UnitTestCase
{
	/** amount of tables created */
	const NUM_NEW_TABLES = 9;

	/** @var string[] */
	private $_createQueries = [];

	/** @var string[] */
	private $_dropQueries = [];

	/** @var WPOptionWrapperTest */
	private $_pWpOption;

	/** @var DatabaseChanges */
	private $_pDbChanges;

	/** @var string[] */
	private $_fields = [
		'Field 1',
		'Field 2',
		'Field 3'
	];

	/**
	 * @before
	 */
	public function prepare()
	{
		global $wpdb;

		$this->_pWpOption = new WPOptionWrapperTest();
		$this->_pDbChanges = new DatabaseChanges($this->_pWpOption, $wpdb);

		$dataSimilarViewOptions = new \onOffice\WPlugin\DataView\DataDetailView();
		$dataSimilarViewOptions->name = "onoffice-default-view";
		$dataSimilarViewOptions->setDataDetailViewActive(true);

		$dataViewSimilarEstates = $dataSimilarViewOptions->getDataViewSimilarEstates();
		$dataViewSimilarEstates->setFields($this->_fields);
		$dataViewSimilarEstates->setSameEstateKind(true);
		$dataViewSimilarEstates->setSameMarketingMethod(true);
		$dataViewSimilarEstates->setSamePostalCode(true);
		$dataViewSimilarEstates->setRadius(35);
		$dataViewSimilarEstates->setRecordsPerPage(13);
		$dataViewSimilarEstates->setTemplate('/test/similar/template.php');
		$dataSimilarViewOptions->setDataViewSimilarEstates($dataViewSimilarEstates);
		add_option('onoffice-default-view', $dataSimilarViewOptions);
	}

	/**
	 * @covers \onOffice\WPlugin\Installer\DatabaseChanges::install
	 */

	public function testInstall(): array
	{
		add_filter('query', [$this, 'saveCreateQuery'], 1);
		$this->_pDbChanges->install();

		remove_filter('query', [$this, 'saveCreateQuery'], 1);
		$this->assertGreaterThanOrEqual(self::NUM_NEW_TABLES, count($this->_createQueries));

		$dbversion = $this->_pDbChanges->getDbVersion();
		$this->assertEquals(18, $dbversion);
		return $this->_createQueries;
	}

	public function testInstallMigrationsDataSimilarEstates(): array
	{
		add_option('oo_plugin_db_version', '16');
		add_filter('query', [$this, 'saveCreateQuery'], 1);
		$this->_pDbChanges->install();
		remove_filter('query', [$this, 'saveCreateQuery'], 1);

		$pSimilarViewOptions = $this->_pWpOption->getOption('onoffice-similar-estates-settings-view');
		$newEnableSimilarEstates = $pSimilarViewOptions->getDataSimilarViewActive();

		$pNewDataViewSimilarEstates = $pSimilarViewOptions->getDataViewSimilarEstates();
		$newFields = $pNewDataViewSimilarEstates->getFields();
		$newRadius = $pNewDataViewSimilarEstates->getRadius();
		$newSameKind = $pNewDataViewSimilarEstates->getSameEstateKind();
		$newSameMarketingMethod = $pNewDataViewSimilarEstates->getSameMarketingMethod();
		$newSamePostalCode = $pNewDataViewSimilarEstates->getSamePostalCode();
		$newAmount = $pNewDataViewSimilarEstates->getRecordsPerPage();
		$newSimilarEstatesTemplate = $pNewDataViewSimilarEstates->getTemplate();

		$this->assertTrue($newEnableSimilarEstates);
		$this->assertEquals('Field 1', $newFields[0]);
		$this->assertEquals('Field 2', $newFields[1]);
		$this->assertEquals('Field 3', $newFields[2]);
		$this->assertTrue(true, $newRadius);
		$this->assertTrue(true, $newSameKind);
		$this->assertTrue(true, $newSameMarketingMethod);
		$this->assertEquals(35, $newSamePostalCode);
		$this->assertEquals(13, $newAmount);
		$this->assertEquals('/test/similar/template.php', $newSimilarEstatesTemplate);

		return $this->_createQueries;
	}


	/**
	 *
	 */
	public function testMaxVersion()
	{
		$this->assertEquals(18, DatabaseChanges::MAX_VERSION);
	}


	/**
	 *
	 * @depends testInstall
	 * @param array $createQueries from testInstall test.
	 *
	 */

	public function testUninstall(array $createQueries)
	{
		add_filter('query', [$this, 'saveDropQuery'], 1);
		$this->_pDbChanges->deinstall();
		remove_filter('query', [$this, 'saveDropQuery'], 1);

		$this->assertGreaterThanOrEqual(self::NUM_NEW_TABLES, count($this->_dropQueries));

		// assert that as many tables have been removed as have been created
		$uniqueCreateQueries = array_unique($createQueries);
		$uniqueDropQueries = array_unique($this->_dropQueries);
		$this->assertEquals(count($uniqueCreateQueries), count($uniqueDropQueries));
		$dbversion = $this->_pWpOption->getOption('oo_plugin_db_version', null);
		$this->assertNull($dbversion);
		$this->assertNull($this->_pDbChanges->getDbVersion());
	}


	/**
	 *
	 * @param string $query
	 * @return string
	 *
	 */

	public function saveCreateQuery(string $query): string
	{
		if (__String::getNew($query)->startsWith('CREATE TABLE')) {
			$this->_createQueries []= $query;
		}

		return $query;
	}


	/**
	 *
	 * @param string $query
	 * @return string
	 *
	 */

	public function saveDropQuery(string $query): string
	{
		if (__String::getNew($query)->startsWith('DROP TABLE')) {
			$this->_dropQueries []= $query;
		}

		return $query;
	}
}
