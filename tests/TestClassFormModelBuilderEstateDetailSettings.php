<?php

/**
 *
 *    Copyright (C) 2020 onOffice GmbH
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

use onOffice\WPlugin\DataView\DataDetailViewHandler;
use onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilderEstateDetailSettings;
use onOffice\WPlugin\Model\InputModel\InputModelOptionFactoryDetailView;
use onOffice\WPlugin\Record\RecordManagerReadForm;
use onOffice\WPlugin\WP\WPOptionWrapperTest;
use WP_UnitTestCase;
use wpdb;

class TestClassFormModelBuilderEstateDetailSettings
	extends WP_UnitTestCase
{

	/** */
	const VALUES_BY_ROW = [
		'fields' => [
			'Objektnr_extern',
			'wohnflaeche',
			'kaufpreis',
		],
		'similar_estates_template' => '/test/similar/template.php',
		'same_kind' => true,
		'same_maketing_method' => true,
		'show_archived' => true,
		'show_reference' => true,
		'radius' => 35,
		'amount' => 13,
		'enablesimilarestates' => true,
		'show_status' => true
	];

	/** @var InputModelOptionFactoryDetailView */
	private $_pInputModelDetailViewFactory;

	/** @var DataSimilarView */
	private $_pDataDetailView = null;

	/**
	 * @before
	 */
	public function prepare()
	{
		$this->_pInputModelDetailViewFactory = new InputModelOptionFactoryDetailView('onoffice');
	}

	/**
	 * @covers onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilderEstateDetailSettings::CreateInputModelShortCodeForm
	 */
	public function testCreateInputModelShortCodeForm()
	{
		$row = self::VALUES_BY_ROW;

		$pWPOptionsWrapper = new WPOptionWrapperTest();
		$pDataDetailViewHandler = new DataDetailViewHandler($pWPOptionsWrapper);
		$this->_pDataDetailView = $pDataDetailViewHandler->createDetailViewByValues($row);

		$pInstance = $this->getMockBuilder(FormModelBuilderEstateDetailSettings::class)
			->disableOriginalConstructor()
			->setMethods(['getValue', 'readNameShortCodeForm'])
			->getMock();
		$pInstance->expects($this->exactly(1))
			->method('readNameShortCodeForm')
			->willReturnOnConsecutiveCalls(['' => '[oo_form form="Default Form"]'],['' => '[oo_form form="Default Form"]']);
		$pInstance->generate('test');

		$pInputModelDB = $pInstance->createInputModelShortCodeForm();
		$this->assertEquals($pInputModelDB->getHtmlType(), 'select');
	}

	/**
	 * @covers onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilderEstateDetailSettings::createInputModelPictureTypes
	 */
	public function testCreateInputModelPictureTypes()
	{
		$row = self::VALUES_BY_ROW;

		$pWPOptionsWrapper = new WPOptionWrapperTest();
		$pDataSimilarEstatesSettingsHandler = new DataDetailViewHandler($pWPOptionsWrapper);
		$this->_pDataDetailView = $pDataSimilarEstatesSettingsHandler->createDetailViewByValues($row);


		$pInstance = $this->getMockBuilder(FormModelBuilderEstateDetailSettings::class)
			->disableOriginalConstructor()
			->setMethods(['getValue'])
			->getMock();
		$pInstance->generate('test');

		$pInputModelDB = $pInstance->createInputModelPictureTypes();
		$this->assertEquals($pInputModelDB->getHtmlType(), 'checkbox');
	}

	/**
	 * @covers onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilderEstateDetailSettings::createInputModelMovieLinks
	 */
	public function testCreateInputModelMovieLinks()
	{
		$row = self::VALUES_BY_ROW;

		$pWPOptionsWrapper = new WPOptionWrapperTest();
		$pDataSimilarEstatesSettingsHandler = new DataDetailViewHandler($pWPOptionsWrapper);
		$this->_pDataDetailView = $pDataSimilarEstatesSettingsHandler->createDetailViewByValues($row);


		$pInstance = $this->getMockBuilder(FormModelBuilderEstateDetailSettings::class)
			->disableOriginalConstructor()
			->setMethods(['getValue'])
			->getMock();
		$pInstance->generate('test');

		$pInputModelDB = $pInstance->createInputModelMovieLinks();
		$this->assertEquals($pInputModelDB->getHtmlType(), 'select');
	}

	/**
	 * @covers onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilderEstateDetailSettings::createInputModelShowStatus
	 */
	public function testCreateInputModelShowStatus()
	{
		$row = self::VALUES_BY_ROW;

		$pWPOptionsWrapper = new WPOptionWrapperTest();
		$pDataDetailViewHandler = new DataDetailViewHandler($pWPOptionsWrapper);
		$this->_pDataDetailView = $pDataDetailViewHandler->createDetailViewByValues($row);

		$pInstance = $this->getMockBuilder(FormModelBuilderEstateDetailSettings::class)
			->disableOriginalConstructor()
			->setMethods(['getValue'])
			->getMock();
		$pInstance->generate('test');

		$pInputModelDB = $pInstance->createInputModelShowStatus();
		$this->assertEquals($pInputModelDB->getHtmlType(), 'checkbox');
	}

}