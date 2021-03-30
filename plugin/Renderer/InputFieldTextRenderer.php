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

namespace onOffice\WPlugin\Renderer;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class InputFieldTextRenderer
	extends InputFieldRenderer
{

	/** @var string */
	private $_hint = '';


	/**
	 *
	 * @param string $type
	 * @param string $name
	 * @param string $value
	 *
	 */

	public function __construct($type, $name, $value = null)
	{
		if (!in_array($type, array('text', 'hidden'))) {
			throw new \Exception(' wrong type!');
		}
		parent::__construct($type, $name, $value);
	}


	/**
	 *
	 */

	public function render()
	{
		$textHtml = '';
		if (!empty($this->getHint())) {
			$textHtml = '<p class="hint-fallback-email">' . esc_html($this->getHint()) . '</p>';
		}
		echo '<input type="'.esc_html($this->getType()).'" name="'.esc_html($this->getName())
			.'" value="'.esc_html($this->getValue()).'" id="'.esc_html($this->getGuiId()).'"'
			.' '.$this->renderAdditionalAttributes()
			.'>'.$textHtml;
	}

	/** @return string */
	public function getHint()
	{ return $this->_hint; }

	/** @param string $hint */
	public function setHint(string $hint)
	{ $this->_hint = $hint; }
}
