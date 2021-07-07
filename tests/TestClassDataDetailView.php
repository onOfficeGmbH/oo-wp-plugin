<?php

/**
 *
 *    Copyright (C) 2018 onOffice GmbH
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

use Closure;
use onOffice\WPlugin\DataView\DataDetailView;
use onOffice\WPlugin\DataView\DataViewSimilarEstates;
use onOffice\WPlugin\Types\MovieLinkTypes;
use TypeError;
use WP_UnitTestCase;

class TestClassDataDetailView
	extends WP_UnitTestCase
{
	/** */
	const DEFAULT_FIELDS_ESTATE = [
		'objekttitel',
		'objektart',
		'objekttyp',
		'vermarktungsart',
		'plz',
		'ort',
		'bundesland',
		'objektnr_extern',
		'wohnflaeche',
		'grundstuecksflaeche',
		'nutzflaeche',
		'anzahl_zimmer',
		'anzahl_badezimmer',
		'kaufpreis',
		'kaltmiete',
		'objektbeschreibung',
		'lage',
		'ausstatt_beschr',
		'sonstige_angaben',
		'vermarktungsstatus'
	];

	/** */
	const DEFAULT_FIELDS_ADDRESS = [
		'Anrede',
		'Vorname',
		'Name',
		'Zusatz1',
		'Strasse',
		'Plz',
		'Ort',
		'Telefon1',
		'mobile',
	];
	/**
	 *
	 */
	public function testDefaultValues()
	{
		$pDataDetailView = new DataDetailView();

		$this->assertEquals(self::DEFAULT_FIELDS_ADDRESS, $pDataDetailView->getAddressFields());
		$this->assertEquals(self::DEFAULT_FIELDS_ESTATE, $pDataDetailView->getFields());
		$this->assertEquals('', $pDataDetailView->getExpose());
		$this->assertEquals(MovieLinkTypes::MOVIE_LINKS_NONE, $pDataDetailView->getMovieLinks());
		$this->assertEquals('detail', $pDataDetailView->getName());
		$this->assertEquals(0, $pDataDetailView->getPageId());
		$this->assertEquals([], $pDataDetailView->getPictureTypes());
		$this->assertEquals('', $pDataDetailView->getTemplate());
		$this->assertEquals('', $pDataDetailView->getShortCodeForm());
		$this->assertFalse($pDataDetailView->getShowStatus());
	}

	/**
	 *
	 */
	public function testGetterSetter()
	{
		$pDataDetailView = new DataDetailView();

		$pDataDetailView->setAddressFields(['testaddressfield1', 'testaddressfield2']);
		$this->assertEquals(['testaddressfield1', 'testaddressfield2'],
			$pDataDetailView->getAddressFields());
		$pDataDetailView->setFields(['testfield1', 'testfield2']);
		$this->assertEquals(['testfield1', 'testfield2'], $pDataDetailView->getFields());
		$pDataDetailView->setExpose('testexpose1');
		$this->assertEquals('testexpose1', $pDataDetailView->getExpose());
		$pDataDetailView->setMovieLinks(MovieLinkTypes::MOVIE_LINKS_PLAYER);
		$this->assertEquals(MovieLinkTypes::MOVIE_LINKS_PLAYER, $pDataDetailView->getMovieLinks());
		$pDataDetailView->setPageId(12);
		$this->assertEquals(12, $pDataDetailView->getPageId());
		$pDataDetailView->setPictureTypes(['testpicturetype1', 'testpicturetype2']);
		$this->assertEquals(['testpicturetype1', 'testpicturetype2'],
			$pDataDetailView->getPictureTypes());
		$pDataDetailView->setTemplate('/test/template1.test');
		$this->assertEquals('/test/template1.test', $pDataDetailView->getTemplate());
		$pDataDetailView->setShortCodeForm('[oo_form form="Contact Form"]');
		$this->assertEquals('[oo_form form="Contact Form"]', $pDataDetailView->getShortCodeForm());
		$pDataDetailView->setShowStatus(true);
		$this->assertTrue($pDataDetailView->getShowStatus());
	}

	/**
	 *
	 */
	public function testEnableSimilarEstates()
	{
		$pDataDetailView = new DataDetailView();

		$this->assertFalse($pDataDetailView->getDataDetailViewActive());
		$pDataDetailView->setDataDetailViewActive(true);
		$this->assertTrue($pDataDetailView->getDataDetailViewActive());
	}

	/**
	 *
	 */
	public function testRandom()
	{
		$pDataDetailView = new DataDetailView();
		$this->assertFalse($pDataDetailView->getRandom());

	}
}
