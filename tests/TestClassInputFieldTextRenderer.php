<?php

/**
 *
 *    Copyright (C) 2021 onOffice GmbH
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

use onOffice\WPlugin\Renderer\InputFieldTextRenderer;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TestClassInputFieldTextRenderer
	extends \WP_UnitTestCase
{
	/**
	 *
	 */
	public function testRenderHintFallbackEmail()
	{
		$pSubject = new InputFieldTextRenderer('text', 'testRenderer');
		$pSubject->setValue('john.doe@example.com');
		$pSubject->setHint('Test Content Hint Fallback Email');
		ob_start();
		$pSubject->render();
		$output = ob_get_clean();
		$this->assertEquals('<input type="text" name="testRenderer" value="john.doe@example.com" id="text_1" ><p class="hint-fallback-email">Test Content Hint Fallback Email</p>',
			$output);
	}
}