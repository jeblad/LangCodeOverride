<?php

namespace LangCodeOverride\Tests;

use \LangCodeOverride\Hooks;

/**
 * @group LangCodeOverride
 *
 * @covers \LangCodeOverride\Hooks
 */
class HooksTest extends \MediaWikiTestCase {

	/**
	 * Create a new output page
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 *
	 * Not sure if all of this is needed. It is a copy of the private method from
	 * OutputPageTest::newInstance().\Composer\Autoload\ClassLoader
	 *
	 */
	private function newOutputPage( $config = [], WebRequest $request = null, $options = [] ) {
		$context = new \RequestContext();

		$context->setConfig( new \MultiConfig( [
			new \HashConfig( $config + [
				'AppleTouchIcon' => false,
				'DisableLangConversion' => true,
				'EnableCanonicalServerLink' => false,
				'Favicon' => false,
				'Feed' => false,
				'LanguageCode' => false,
				'ReferrerPolicy' => false,
				'RightsPage' => false,
				'RightsUrl' => false,
				'UniversalEditButton' => false,
			] ),
			$context->getConfig()
		] ) );

		if ( !in_array( 'notitle', (array)$options ) ) {
			$context->setTitle( \Title::newFromText( 'My test page' ) );
		}

		if ( $request ) {
			$context->setRequest( $request );
		}

		return new \OutputPage( $context );
	}

	public function provideOverrideLanguageLink() {
		return [
			[ 'nb', 'no:foo', null, null ],
			[ 'nb', 'nb:foo', null, null ],
			[ 'ping', 'nb:foo', false, false ],
			[ 'hbs', 'hs:foo', null, null ],
			[ 'nbs', 'hs:foo', null, null ],
			[ 'ping', 'hs:foo', false, false ],
			[ 'en-simple', 'simple:foo', null, null ],
			[ 'en-simple', 'simple:foo', null, null ],
			[ 'ping', 'simple:foo', false, false ]
		];
	}

	/**
	 * @dataProvider provideOverrideLanguageLink
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 *
	 * @param array &$languageLink containing data about the link
	 * @param string $overrideLangCode the language code to use
	 * @param Title $languageLinkTitle object for the external language link
	 * @param Title $title object for the page the link belongs to
	 * @param OutputPage $outputPage for the page the link belongs to
	 */
	public function testOverrideLanguageLink( $overrideCode, $link, $text = null, $title = null ) {
		$linkedTitle = \Title::newFromText( $link );

		$output = $this->newOutputPage();
		$output->setTitle( \Title::newFromText( 'Lang code override' ) );
		$this->assertSame( 'Lang code override', $output->getTitle()->getPrefixedText() );

		$languageLink = [
			'href' => $linkedTitle->getFullURL(),
			'text' => 'none',
			'title' => $linkedTitle,
			'class' => 'interlanguage-link interwiki-' . $linkedTitle->getInterwiki(),
			'link-class' => 'interlanguage-link-target',
			'lang' => $linkedTitle->getInterwiki(),
			'hreflang' => $linkedTitle->getInterwiki()
		];

		Hooks::overrideLanguageLink(
			$languageLink,
			$overrideCode,
			$linkedTitle,
			$output->getTitle(),
			$output
		);

		// this should still exist
		$this->assertNotNull( $languageLink );

		// the href should not be changed
		$this->assertArrayHasKey( 'href', $languageLink );
		$this->assertEquals( $linkedTitle->getFullURL(), $languageLink['href'] );

		// the link class should not be changed
		$this->assertArrayHasKey( 'link-class', $languageLink );
		$this->assertEquals( 'interlanguage-link-target', $languageLink['link-class'] );

		// the language code should be the override code
		$this->assertArrayHasKey( 'lang', $languageLink );
		$this->assertEquals( $overrideCode, $languageLink['lang'] );

		// the href language code should be the override code
		$this->assertArrayHasKey( 'hreflang', $languageLink );
		$this->assertEquals( $overrideCode, $languageLink['hreflang'] );

		// the class should use the override code
		$this->assertArrayHasKey( 'class', $languageLink );
		$this->assertEquals( "interlanguage-link interwiki-$overrideCode", $languageLink['class'] );

		// the text should be the language name for the override code
		$this->assertArrayHasKey( 'text', $languageLink );
		if ( $text === null ) {
			$skin = $output->getContext()->getSkin();
			$text = $skin->formatLanguageName( \Language::fetchLanguageName( $overrideCode ) );
			$text = '/' . preg_quote( $text ) . '/i';
		} elseif ( $text === true ) {
			// this should pick up a previously set message
			$text = '/' . '⧼' . 'interlanguage-link-' . $overrideCode . '\b' . '/i';
		} elseif ( $text === false ) {
			$text = '/' . preg_quote( $link ) . '/i';
		} elseif ( gettype( $text ) === 'string' ) {
			// nop
		} else {
			$this->fail( 'Should not be here…' );
		}
		$this->assertRegExp( $text, $languageLink['text'] );

		// the title should be a compostite with a site name
		$this->assertArrayHasKey( 'title', $languageLink );
		if ( $title === null ) {
			$skin = $output->getContext()->getSkin();
			$langName = $skin->formatLanguageName( \Language::fetchLanguageName( $overrideCode ) );
			$title = '/' . preg_quote( $langName ) . '/i';
		} elseif ( $title === true ) {
			// this should pick up a previously set message
			$title = '/' . '⧼' . 'interlanguage-link-' . $overrideCode . '\b' . '/i';
		} elseif ( $title === false ) {
			$title = '/:' . preg_quote( $link ) . '/i';
		} elseif ( gettype( $title ) === 'string' ) {
			$title = '/' . preg_quote( $title ) . '/i';
		} else {
			$this->fail( 'Should not be here…' );
		}
		$this->assertRegExp( $title, $languageLink['title'] );
	}

	/**
	 * @dataProvider provideOverrideLanguageLink
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 *
	 * @param array &$languageLink containing data about the link
	 * @param string $overrideLangCode the language code to use
	 * @param Title $languageLinkTitle object for the external language link
	 * @param Title $title object for the page the link belongs to
	 * @param OutputPage $outputPage for the page the link belongs to
	 */
	public function _testOnSkinTemplateGetLanguageLink( $overrideCode, $link, $text = null, $title = null ) {
		$linkedTitle = \Title::newFromText( $link );

		$output = $this->newOutputPage();
		$output->setTitle( \Title::newFromText( 'Lang code override' ) );
		$this->assertSame( 'Lang code override', $output->getTitle()->getPrefixedText() );

		$languageLink = [
			'href' => $linkedTitle->getFullURL(),
			'text' => 'none',
			'title' => $linkedTitle,
			'class' => 'interlanguage-link interwiki-' . $linkedTitle->getInterwiki(),
			'link-class' => 'interlanguage-link-target',
			'lang' => $linkedTitle->getInterwiki(),
			'hreflang' => $linkedTitle->getInterwiki()
		];

		Hooks::onSkinTemplateGetLanguageLink(
			$languageLink,
			$overrideCode,
			$linkedTitle,
			$output->getTitle(),
			$output
		);
	}

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
		$this->assertEquals( $expect, Hooks::findValue( $needle, $haystack ) );
	}

}
