<?php

namespace LangCodeOverride;

/**
 * Utility functions
 *
 * @ingroup Extensions
 */
class Util {

	/**
	 * Find value give a needle and a haystack
	 *
	 * @param string|null $needle to find
	 * @param array|null $haystack to search
	 * @return any|null whats found
	 */
	public static function findValue( $needle, $haystack ) {
		if ( $needle === null ) {
			return null;
		}

		if ( $haystack === null ) {
			return null;
		}

		if ( !array_key_exists( $needle, $haystack ) ) {
			return null;
		}

		$value = $haystack[$needle];

		return $value;
	}
}