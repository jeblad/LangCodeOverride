<?php

namespace LangCodeOverride\Tests;

use \LangCodeOverride\Hooks;
use \MediaWiki\MediaWikiServices;

/**
 * @group LangCodeOverride
 *
 * @covers \LangCodeOverride\Hooks
 */
class HooksTest extends \MediaWikiTestCase {

	/**
	 * @see \MessageCacheTest::setup()
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	protected function setUp() {
		parent::setUp();
		$this->configureLanguages();
		\MessageCache::destroyInstance();
		\MessageCache::singleton()->enable();
	}

	/**
	 * @see MessageCacheTest::setup()
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	protected function configureLanguages() {
		$this->setUserLang( 'en' );
		$this->setContentLang( 'en' );
	}

	/**
	 * @see MessageCacheTest::addDBDataOnce()
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	function addDBDataOnce() {
		$this->configureLanguages();
		$this->makePage( 'interlanguage-link-no', 'en', 'Fancy Norwegian' );
		$this->makePage( 'interlanguage-link-nb', 'en', '-' );
		$this->makePage( 'interlanguage-link-sitename-no', 'en', 'Fancy Norwegian' );
		$this->makePage( 'interlanguage-link-sitename-nb', 'en', '-' );
	}

	/**
	 * @see MessageCacheTest:: makePage()
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	protected function makePage( $title, $lang, $content = null ) {
		if ( $content === null ) {
			$content = $lang;
		}

		if ( $lang !== \MediaWiki\MediaWikiServices::getInstance()->getContentLanguage()->getCode() ) {
			$title = "$title/$lang";
		}

		$title = \Title::newFromText( $title, NS_MEDIAWIKI );
		$wikiPage = new \WikiPage( $title );
		$contentHandler = \ContentHandler::makeContent( $content, $title );
		$wikiPage->doEditContent( $contentHandler, "This is \"$lang\" translation test case" );
	}

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
			[ 'nb', 'no:foo', 'Norsk bokmål', 'No:foo – norsk bokmål' ],
			[ 'nb', 'nb:foo', 'Norsk bokmål', 'Nb:foo – norsk bokmål' ],
			[ 'ping', 'no:foo', 'No:foo', ':No:foo' ],
			[ 'ping', 'nb:foo', 'Nb:foo', ':Nb:foo' ],
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
	public function testOverrideLanguageLink(
		$overrideCode,
		$link,
		$text,
		$title
	) {
		$linkedTitle = \Title::newFromText( $link );

		$output = $this->newOutputPage();
		$output->setTitle( \Title::newFromText( 'Lang code override' ) );
		$this->assertSame( 'Lang code override', $output->getTitle()->getPrefixedText() );

		$languageLink = [
			'href' => $linkedTitle->getFullURL(),
			'text' => 'none',
			'title' => 'none',
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
		$this->assertEquals( $text, $languageLink['text'] );

		// the text should be the language title for the override code
		$this->assertArrayHasKey( 'title', $languageLink );
		$this->assertEquals( $title, $languageLink['title'] );
	}

	/**
	 * @_dataProvider provideOverrideLanguageLink
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 *
	 * @param array &$languageLink containing data about the link
	 * @param string $overrideLangCode the language code to use
	 * @param Title $languageLinkTitle object for the external language link
	 * @param Title $title object for the page the link belongs to
	 * @param OutputPage $outputPage for the page the link belongs to
	 */
	public function noTestOnSkinTemplateGetLanguageLink(
		$overrideCode,
		$link,
		$text = null,
		$title = null
	) {
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

	/**
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	public function provideLinkText() {
		return [
			[ 'Foo', 'Foo', '', \Title::newFromText( 'no:Bar' ) ],
			[ 'Fancy Norwegian', '', 'no', \Title::newFromText( 'no:Bar' ) ],
			[ 'Bar', '', 'nb', \Title::newFromText( 'no:Bar' ) ],
			[ 'Bar', '', '', \Title::newFromText( 'no:Bar' ) ]
		];
	}

	/**
	 * @dataProvider provideLinkText
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	public function testLinkText( $expect, $langName, $langCode, $title ) {
		$this->assertEquals( $expect, Hooks::linkText( $langName, $langCode, $title ) );
	}

	/**
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	public function provideLinkTitle() {
		return [
			[ 'Bar – Foo', 'Foo', '', \Title::newFromText( 'no:Bar' ) ],
			[ 'Foo', 'Foo', '', \Title::newFromText( 'no:' ) ],
			[ 'Bar – Fancy Norwegian', '', 'no', \Title::newFromText( 'no:Bar' ) ],
			[ 'Fancy Norwegian', '', 'no', \Title::newFromText( 'no:' ) ],
			[ 'no:Bar', '', 'nb', \Title::newFromText( 'no:Bar' ) ],
			[ 'no:Bar', '', '', \Title::newFromText( 'no:Bar' ) ]
		];
	}

	/**
	 * @dataProvider provideLinkTitle
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	public function testLinkTitle( $expect, $langName, $langCode, $title ) {
		$this->assertEquals( $expect, Hooks::linkTitle( $langName, $langCode, $title ) );
	}

}
