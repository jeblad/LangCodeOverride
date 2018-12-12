<?php

namespace LangCodeOverride\Tests;

use \LangCodeOverride\Util;

/**
 * @group LangCodeOverrideNew
 *
 * @covers \LangCodeOverride\Util
 */
class UtilTest extends \MediaWikiTestCase {

	public function provideFindValue() {
		return [
			[ null, 'foo', null ],
			[ null, null, [ 'foo' => 'ping', 'bar' => 'pong' ] ],
			[ 'ping', 'foo', [ 'foo' => 'ping', 'bar' => 'pong' ] ],
			[ null, null, [ 'foo' => 'ping', 'bar' => 'pong' ] ],
			[ [ 'ping' ], 'foo', [ 'foo' => [ 'ping' ], 'bar' => 'pong' ] ],
			[ null, null, [ 'foo' => [ 'ping' ], 'bar' => 'pong' ] ],
			[ null, 'foo', [ 'foo' => null, 'bar' => 'pong' ] ],
			[ [ null ], 'foo', [ 'foo' => [ null ], 'bar' => 'pong' ] ],
		];
	}

	/**
	 * @dataProvider provideFindValue
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	public function testFindValue( $expect, $needle, $haystack ) {
		$this->assertEquals( $expect, Util::findValue( $needle, $haystack ) );
	}
}
