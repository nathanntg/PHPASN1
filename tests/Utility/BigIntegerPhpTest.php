<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\Test\Utility;

class BigIntegerPhpTest extends BigIntegerTest
{
	protected function _isSupported()
	{
		return true;
	}

	protected function _getMode()
	{
		return 'php';
	}
}
