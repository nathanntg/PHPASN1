<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\Utility;

/**
 * Class BigIntegerPhp
 * Integer representation of big numbers using a pure PHP implementation. (Inefficient, just removing external
 * dependencies.)
 * @package FG\Utility
 * @internal
 */
class BigIntegerPhp extends BigInteger
{
	protected $_neg;
	protected $_len; // number of digits
	protected $_str;

	public function __clone()
	{
		// nothing needed to copy
	}

	protected function _fromString($str)
	{
		if ('-' === $str[0]) {
			$negative = true;
			$this->_str = substr($str, 1);
		}
		else {
			$negative = false;
			$this->_str = $str;
		}
		$this->_len = strlen($this->_str);
		$this->_neg = $negative && '' !== ltrim($this->_str, '0');
	}

	public function __toString()
	{
		$ret = ltrim($this->_str, '0');
		if ('' === $ret) {
			$ret = '0';
		}
		if ($this->_neg) {
			return '-' . $ret;
		}
		return $ret;
	}

	public function toInteger()
	{
		$ret = (int)$this->_str;
		if ($this->_neg) {
			return 0 - $ret;
		}
		return $ret;
	}

	public function isNegative()
	{
		return $this->_neg;
	}

	protected function _wrap($number)
	{
		if ($number instanceof self) {
			return $number;
		}
		$ret = new self();
		$ret->_fromString((string)$number);
		return $ret;
	}

	protected static function _equalize(self $a, self $b) {
		if ($a->_len < $b->_len) {
			return array($b->_len, str_pad($a->_str, $b->_len, '0', STR_PAD_LEFT), $b->_str);
		}
		if ($a->_len > $b->_len) {
			return array($a->_len, $a->_str, str_pad($b->_str, $a->_len, '0', STR_PAD_LEFT));
		}
		return array($a->_len, $a->_str, $b->_str);
	}

	protected static function _raw_compare($sl, $a, $b) {
		$ret = 0;
		for ($i = 0; $i < $sl; ++$i) {
			$ret = (int)$a[$i] - (int)$b[$i];
			if (0 !== $ret) break;
		}

		if ($ret < 0) {
			return -1;
		}
		if ($ret > 0) {
			return 1;
		}
		return 0;
	}

	protected static function _raw_add($sl, $a, $b) {
		$ret = str_repeat('0', $sl);
		$carry = 0;
		for ($i = $sl - 1; $i >= 0; --$i) {
			$cur = $carry + (int)$a[$i] + (int)$b[$i];
			$carry = 0;
			while ($cur >= 10) { // can only happen once
				$cur -= 10;
				++$carry;
			}
			$ret[$i] = (string)$cur;
		}

		// final carry?
		if ($carry) {
			return array($sl + 1, '1' . $ret);
		}
		return array($sl, $ret);
	}

	/**
	 * Subtract $b from $a, assuming $a is larger than $b.
	 * @param int $sl
	 * @param string $a
	 * @param string $b
	 * @return array
	 */
	protected static function _raw_sub($sl, $a, $b) {
		$ret = str_repeat('0', $sl);
		$borrow = 0;
		for ($i = $sl - 1; $i >= 0; --$i) {
			$cur = (int)$a[$i] - (int)$b[$i] - $borrow;
			$borrow = 0;
			while ($cur < 0) { // can only happen once
				$cur += 10;
				++$borrow;
			}
			$ret[$i] = (string)$cur;
		}

		// clean leading zeros
		if ('0' === $ret[0]) {
			$ret = ltrim($ret, '0');
			if ('' === $ret) $ret = '0';
			return array(strlen($ret), $ret);
		}

		return array($sl, $ret);
	}

	public function compare($number)
	{
		$number = $this->_wrap($number);

		// shortcut based on sign
		if ($this->_neg !== $number->_neg) {
			return $this->_neg ? -1 : 1;
		}

		// same sign
		$sgn = $this->_neg ? -1 : 1;

		// compare digit by digit
		list($sl, $a, $b) = self::_equalize($this, $number);
		return $sgn * self::_raw_compare($sl, $a, $b);
	}

	public function add($b)
	{
		$b = $this->_wrap($b);
		list($sl, $a_str, $b_str) = self::_equalize($this, $b);

		$ret = new self();

		if ($this->_neg === $b->_neg) {
			// same sign?

			// perform add
			list($ret->_len, $ret->_str) = self::_raw_add($sl, $a_str, $b_str);

			// same sign
			$ret->_neg = $this->_neg;
		}
		else {
			// compare raw
			switch (self::_raw_compare($sl, $a_str, $b_str)) {
				case 0:
					// equal value, different sign: easy
					$ret->_str = '0';
					$ret->_len = 1;
					$ret->_neg = false;
					break;
				case -1:
					list($ret->_len, $ret->_str) = self::_raw_sub($sl, $b_str, $a_str);
					$ret->_neg = $b->_neg;
					break;
				case 1:
					list($ret->_len, $ret->_str) = self::_raw_sub($sl, $a_str, $b_str);
					$ret->_neg = $this->_neg;
					break;
			}
		}

		return $ret;
	}

	public function subtract($b)
	{
		$b = $this->_wrap($b);
		list($sl, $a_str, $b_str) = self::_equalize($this, $b);

		$ret = new self();

		if ($this->_neg !== $b->_neg) {
			// same sign?

			// perform add
			list($ret->_len, $ret->_str) = self::_raw_add($sl, $a_str, $b_str);

			// same sign
			$ret->_neg = $this->_neg;
		}
		else {
			// compare raw
			switch (self::_raw_compare($sl, $a_str, $b_str)) {
				case 0:
					// equal value, same sign: easy
					$ret->_str = '0';
					$ret->_len = 1;
					$ret->_neg = false;
					break;
				case -1:
					list($ret->_len, $ret->_str) = self::_raw_sub($sl, $b_str, $a_str);
					$ret->_neg = !$b->_neg;
					break;
				case 1:
					list($ret->_len, $ret->_str) = self::_raw_sub($sl, $a_str, $b_str);
					$ret->_neg = $this->_neg;
					break;
			}
		}

		return $ret;
	}

	public function multiply($b)
	{
		$ret = new self();
		$ret->_str = bcmul($this->_str, $this->_unwrap($b), 0);
		return $ret;
	}

	public function modulus($b)
	{
		$ret = new self();
		if ($this->isNegative()) {
			// bcmod handles negative numbers differently
			$b = $this->_unwrap($b);
			$ret->_str = bcsub($b, bcmod(bcsub('0', $this->_str, 0), $b), 0);
		}
		else {
			$ret->_str = bcmod($this->_str, $this->_unwrap($b));
		}
		return $ret;
	}

	public function toPower($b)
	{
		$ret = new self();
		$ret->_str = bcpow($this->_str, $this->_unwrap($b), 0);
		return $ret;
	}

	public function shiftRight($bits = 8)
	{
		$ret = new self();
		$ret->_str = bcdiv($this->_str, bcpow('2', $bits));
		return $ret;
	}

	public function shiftLeft($bits = 8) {
		$ret = new self();
		$ret->_str = bcmul($this->_str, bcpow('2', $bits));
		return $ret;
	}

	public function absoluteValue()
	{
		$ret = clone $this;
		$ret->_neg = false;
		return $ret;
	}
}
