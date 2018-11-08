<?php

namespace LangCodeOverride\Tests;

use MediaWikiTestCase;
use \LangCodeOverride\Hooks;

/**
 * @group LangCodeOverride
 *
 * @covers \LangCodeOverride\Hooks
 */
class HooksTest extends \MediaWikiTestCase {

	public function provideOverrideLanguageLink() {
		return [
			[
				[
					'href' => 'https://no.wikipedia.org/wiki/Foo',
					'text' => 'norsk bokmål',
					'title' => 'Foo – norsk bokmål',
					'class' => 'interlanguage-link interwiki-nb',
					'link-class' => 'interlanguage-link-target',
					'lang' => 'nb',
					'hreflang' => 'nb'
				],
				[
					'override' => 'no',
					'sourceText' => 'nb:Foo',
					'targetText' => 'no:Foo'
				]
			],
			[
				[
					'href' => 'https://no.wikipedia.org/wiki/Bar',
					'text' => 'norsk bokmål',
					'title' => 'Bar – norsk bokmål',
					'class' => 'interlanguage-link interwiki-nb',
					'link-class' => 'interlanguage-link-target',
					'lang' => 'nb',
					'hreflang' => 'nb'
				],
				[
					'override' => 'no',
					'sourceText' => 'no:Foo',
					'targetText' => 'nb:Bar'
				]
			]
		];
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
	public function testOverrideLanguageLink( $expect, $actual ) {
		$sourceTitle = \Title::newFromText( $actual['sourceText'] );
		$targetTitle = \Title::newFromText( $actual['targetText'] );

		$languageLink = [
			'href' => $targetTitle->getFullURL(),
			'text' => 'none',
			'title' => $targetTitle,
			'class' => 'interlanguage-link interwiki-' . $targetTitle->getInterwiki(),
			'link-class' => 'interlanguage-link-target',
			'lang' => $targetTitle->getInterwiki(),
			'hreflang' => $targetTitle->getInterwiki()
		]

		\LangCodeOverride\Hooks::overrideLanguageLink(
			$languageLink,
			$actual['override'],
			$targetTitle,
			$sourceTitle,
			//$stub // TODO: implement this
		);

		$this->assertNotNull( $languageLink );
		$this->assertEquals( $expect['href'], $languageLink['href'] );
		$this->assertEquals( $expect['text'], $languageLink['text'] );
		$this->assertEquals( $expect['class'], $languageLink['class'] );
		$this->assertEquals( $expect['link-class'], $languageLink['link-class'] );
		$this->assertEquals( $expect['lang'], $languageLink['lang'] );
		$this->assertEquals( $expect['hreflang'], $languageLink['hreflang'] );
	}
}
