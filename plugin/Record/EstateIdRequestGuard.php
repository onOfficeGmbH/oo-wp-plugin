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

namespace onOffice\WPlugin\Record;

use onOffice\WPlugin\Factory\EstateDetailFactory;

/**
 *
 * Checks if an estate ID exists
 *
 */

class EstateIdRequestGuard
{
	/** @var EstateDetailFactory */
	private $_pEstateDetailFactory;


	/**
	 *
	 * @param EstateDetailFactory $pEstateDetailFactory
	 *
	 */

	public function __construct(EstateDetailFactory $pEstateDetailFactory)
	{
		$this->_pEstateDetailFactory = $pEstateDetailFactory;
	}


	/**
	 *
	 * @param int $estateId
	 * @return bool
	 *
	 */

	public function isValid(int $estateId): bool
	{
		$pEstateDetail = $this->_pEstateDetailFactory->createEstateDetail($estateId);
		$pEstateDetail->loadEstates();
		return $pEstateDetail->estateIterator() !== false;
	}
}