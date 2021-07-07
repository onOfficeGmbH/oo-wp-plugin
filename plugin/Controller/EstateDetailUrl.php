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

namespace onOffice\WPlugin\Controller;


class EstateDetailUrl
{
	const MAXIMUM_WORD_TITLE = 5;
	/**
	 * @param string $url
	 * @param int $estateId
	 * @return string
	 */
	public function createEstateDetailLink(string $url, int $estateId, string $title = null): string
	{
		$urlLsSwitcher = $url;

		if ($estateId !== 0){
			$arguments = parse_url($url, PHP_URL_QUERY);
			$getParameters = [];

			if ($arguments != null) {
				parse_str($arguments, $getParameters);
			}

			$urlTemp = $estateId;

			if ($this->isOptionShowTitleUrl() && !empty($title)) {
				$urlTemp .= $this->getSanitizeTitle($title);
			}

			if (array_key_exists('lang', $getParameters) && $getParameters['lang'] != null) {
				$urlLsSwitcher = str_replace('?', $urlTemp.'?', $url);
			} else {
				$urlLsSwitcher .= $urlTemp;
			}
		}

		return $urlLsSwitcher;
	}

	/**
	 *
	 * @return bool
	 *
	 */
	public function isOptionShowTitleUrl()
	{
		return get_option('onoffice-detail-view-showTitleUrl',  false);
	}

	/**
	 * @param string $title
	 * @return string
	 */
	public function getSanitizeTitle(string $title): string
	{
		$sanitizeTitle = sanitize_title($title);
		$arrSanitizeTitle = explode('-', $sanitizeTitle);
		if (count($arrSanitizeTitle) > self::MAXIMUM_WORD_TITLE) {
			$sanitizeTitle = implode('-', array_splice($arrSanitizeTitle, 0, self::MAXIMUM_WORD_TITLE));
		}

		return '-' . $sanitizeTitle;
	}
}