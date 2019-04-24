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

namespace onOffice\WPlugin\Controller\ContentFilter;

use onOffice\WPlugin\AddressList;
use onOffice\WPlugin\DataView\DataListViewAddress;
use onOffice\WPlugin\DataView\DataListViewFactoryAddress;
use onOffice\WPlugin\Impressum;
use onOffice\WPlugin\Template;


/**
 *
 * interface for ContentFilterShortCodeAddressEnvironment
 *
 */

interface ContentFilterShortCodeAddressEnvironment
{

	/**
	 *
	 * @param DataListViewAddress $pAddressListView
	 *
	 */

	public function createAddressList(DataListViewAddress $pAddressListView): AddressList;


	/**
	 *
	 */

	public function getDataListFactory(): DataListViewFactoryAddress;


	/**
	 *
	 * @param string $templateName
	 *
	 */

	public function getTemplate(string $templateName): Template;


	/**
	 *
	 */

	public function getImpressum(): Impressum;


	/**
	 *
	 */

	public function getPage(): int;
}