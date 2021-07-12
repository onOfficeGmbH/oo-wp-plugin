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

use onOffice\WPlugin\Filter\SearchParameters\SearchParameters;
use onOffice\WPlugin\Filter\SearchParameters\SearchParametersModel;
use WP_UnitTestCase;


/**
 *
 * test class for SearchParameters.php
 *
 */

class TestClassSearchParameters
	extends WP_UnitTestCase
{

	/** @var SearchParametersModel */
	private $_pModel = null;


	/**
	 *
	 * @before
	 *
	 */

	public function prepare()
	{
		$this->_pModel = new SearchParametersModel();
		$this->_pModel->setParameters(['ort' => 'Aachen']);
		$this->_pModel->setAllowedGetParameters(['ort']);
		$this->set_permalink_structure('/%postname%/');
		add_option('onoffice-pagination-paginationbyonoffice', 1);
	}


	/**
	 *
	 * @covers \onOffice\WPlugin\Filter\SearchParameters\SearchParameters::linkPagesLink
	 * @covers \onOffice\WPlugin\Filter\SearchParameters\SearchParameters::getLinkSnippetForPage
	 * @covers \onOffice\WPlugin\Filter\SearchParameters\SearchParameters::geturl
	 *
	 */

	public function testLinkPagesLink()
	{
		$params = [
			'before' => '<div class="page-links">Seiten:',
			'after' => '</div>',
			'link_before' => '',
			'link_after' => '' ,
			'aria_current' => 'page',
			'next_or_number' => 'number',
			'separator' => ' ',
			'nextpagelink' => 'Nächste Seite',
			'previouspagelink' => 'Vorherige Seite',
			'pagelink' => '%',
			'echo' => 1];

		$pInstance = new SearchParameters();
		$this->_pModel->populateDefaultLinkParams($params);
		$this->assertEquals('<a href="/page/1/?ort=Aachen">1</a>', $pInstance->linkPagesLink('asd', 1, $this->_pModel));

		global $more;
		$more = true;

		global $page;
		$page = 1;

		$params = [
			'before' => '<div class="page-links">Seiten:',
			'after' => '</div>',
			'link_before' => '',
			'link_after' => '' ,
			'aria_current' => 'page',
			'next_or_number' => '',
			'separator' => ' ',
			'nextpagelink' => 'Nächste Seite',
			'previouspagelink' => 'Vorherige Seite',
			'pagelink' => '%',
			'echo' => 1];

		$pInstance = new SearchParameters();
		$this->_pModel->populateDefaultLinkParams($params);
		$this->assertEquals('<a href="/page/1/?ort=Aachen">Nächste Seite</a>', $pInstance->linkPagesLink('asd', 1, $this->_pModel));
	}
}