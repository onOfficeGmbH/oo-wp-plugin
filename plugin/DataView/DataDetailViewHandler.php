<?php

/**
 *
 *    Copyright (C) 2017 onOffice GmbH
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

namespace onOffice\WPlugin\DataView;

use onOffice\WPlugin\Types\MovieLinkTypes;
use onOffice\WPlugin\WP\WPOptionWrapperBase;
use onOffice\WPlugin\WP\WPOptionWrapperDefault;


/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class DataDetailViewHandler
{
	/** */
	const DEFAULT_VIEW_OPTION_KEY = 'onoffice-default-view';

	/** @var WPOptionWrapperBase */
	private $_pWPOptionWrapper;


	/**
	 * @param WPOptionWrapperBase $pWPOptionWrapper
	 */
	public function __construct(WPOptionWrapperBase $pWPOptionWrapper = null)
	{
		$this->_pWPOptionWrapper = $pWPOptionWrapper ?? new WPOptionWrapperDefault();
	}


	/**
	 *
	 * @return DataDetailView
	 *
	 */

	public function getDetailView(): DataDetailView
	{
		$optionKey = self::DEFAULT_VIEW_OPTION_KEY;
		$pAlternate = new DataDetailView();
		$pResult = $this->_pWPOptionWrapper->getOption($optionKey, $pAlternate);

		if ($pResult == null)
		{
			$pResult = $pAlternate;
		}

		return $pResult;
	}


	/**
	 *
	 * @param DataDetailView $pDataDetailView
	 *
	 */

	public function saveDetailView(DataDetailView $pDataDetailView)
	{
		$pWpOptionsWrapper = $this->_pWPOptionWrapper;
		$viewOptionKey = self::DEFAULT_VIEW_OPTION_KEY;

		if ($pWpOptionsWrapper->getOption($viewOptionKey) !== false) {
			$pWpOptionsWrapper->updateOption($viewOptionKey, $pDataDetailView);
		} else {
			$pWpOptionsWrapper->addOption($viewOptionKey, $pDataDetailView);
		}
	}


	/**
	 *
	 * @param array $row
	 * @return DataDetailView
	 *
	 */

	public function createDetailViewByValues(array $row): DataDetailView
	{
		$pDataDetailView = $this->getDetailView();
		$pDataDetailView->setTemplate($row['template'] ?? '');
		$pDataDetailView->setShortCodeForm($row['shortcodeform'] ?? '');
		$pDataDetailView->setFields($row[DataDetailView::FIELDS] ?? []);
		$pDataDetailView->setPictureTypes($row[DataDetailView::PICTURES] ?? []);
		$pDataDetailView->setExpose($row['expose'] ?? '');
		$pDataDetailView->setAddressFields($row[DataDetailView::ADDRESSFIELDS] ?? []);
		$pDataDetailView->setMovieLinks($row['movielinks'] ?? MovieLinkTypes::MOVIE_LINKS_NONE);
		$pDataDetailView->setShowStatus($row['show_status'] ?? false);
		return $pDataDetailView;
	}
}
