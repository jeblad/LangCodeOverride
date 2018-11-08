<?php

namespace LangCodeOverride;

/**
 * Hook handlers for the LangCodeOverride extension
 *
 * @ingroup Extensions
 */
class Hooks {

	/**
	 * Setup for the extension
	 */
	public static function onExtensionSetup() {
		global $wgDebugComments;

		// turn on comments while in development
		$wgDebugComments = true;
	}

	/**
	 * @param string[] &$files to be tested
	 */
	public static function onUnitTestsList( array &$files ) {
		$files[] = __DIR__ . '/../tests/phpunit/';
	}


	/**
	 * Override the language link
	 * This tries to mimic the inner actions of SkinTemplate::getLanguages()
	 * 
	 * Some consequences of mimicing existing code
	 * 
	 * An formal parameter is passed on, which is never used.
	 * @SuppressWarnings(PHPMD.UnusedFormalParameters)
	 * 
	 * A very long variable name is used, it could be dropped, chose to keep it.
	 * @SuppressWarnings(PHPMD.LongVariable)
	 * 
	 * The original code uses else clauses, kept to make code similar.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * 
	 * @param array &$languageLink containing data about the link
	 * @param string $overrideLangCode the language code to use
	 * @param Title $languageLinkTitle object for the external language link
	 * @param Title $title object for the page the link belongs to
	 * @param OutputPage $outputPage for the page the link belongs to
	 * @param Language $language for testing purposes
	 * @param LanguageCode $languageCode for testing purposes
	 */
	public static function overrideLanguageLink(
		&$languageLink,
		$overrideLangCode,
		$languageLinkTitle,
		$title,
		$outputPage,
		$language = \Language::class,
		$languageCode = \LanguageCode::class
	) {
		$skin = $outputPage->getContext()->getSkin();
		$userLang = $skin->getLanguage();

		$languageLinkText = $languageLinkTitle->getText();
		$ilLangName = $language::fetchLanguageName( $overrideLangCode );
		if ( strval( $ilLangName ) === '' ) {
			$ilDisplayTextMsg = wfMessage( "interlanguage-link-$overrideLangCode" );
			if ( !$ilDisplayTextMsg->isDisabled() ) {
				// Use custom MW message for the display text
				$ilLangName = $ilDisplayTextMsg->text();
			} else {
				// Last resort: fallback to the language link target
				$ilLangName = $languageLinkText;
			}
		} else {
			// Use the language autonym as display text
			$ilLangName = $skin->formatLanguageName( $ilLangName );
		}

		// CLDR extension or similar is required to localize the language name;
		// otherwise we'll end up with the autonym again.
		$ilLangLocalName = $language::fetchLanguageName(
			$overrideLangCode,
			$userLang->getCode()
		);

		$languageLinkTitleText = $languageLinkTitle->getText();
		if ( $ilLangLocalName === '' ) {
			$ilFriendlySiteName = wfMessage( "interlanguage-link-sitename-$overrideLangCode" );
			if ( !$ilFriendlySiteName->isDisabled() ) {
				if ( $languageLinkTitleText === '' ) {
					$ilTitle = wfMessage(
						'interlanguage-link-title-nonlangonly',
						$ilFriendlySiteName->text()
					)->text();
				} else {
					$ilTitle = wfMessage(
						'interlanguage-link-title-nonlang',
						$languageLinkTitleText,
						$ilFriendlySiteName->text()
					)->text();
				}
			} else {
				// we have nothing friendly to put in the title, so fall back to
				// displaying the interlanguage link itself in the title text
				// (similar to what is done in page content)
				$ilTitle = $languageLinkTitle->getInterwiki() .
					":$languageLinkTitleText";
			}
		} elseif ( $languageLinkTitleText === '' ) {
			$ilTitle = wfMessage(
				'interlanguage-link-title-langonly',
				$ilLangLocalName
			)->text();
		} else {
			$ilTitle = wfMessage(
				'interlanguage-link-title',
				$languageLinkTitleText,
				$ilLangLocalName
			)->text();
		}

		$ilInterwikiCodeBCP47 = $languageCode::bcp47( $overrideLangCode );
		$class = "interlanguage-link interwiki-$overrideLangCode";

		$languageLink['href'] = $languageLinkTitle->getFullURL();
		$languageLink['text'] = $ilLangName;
		$languageLink['title'] = $ilTitle;
		$languageLink['class'] = $class;
		$languageLink['link-class'] = 'interlanguage-link-target';
		$languageLink['lang'] = $ilInterwikiCodeBCP47;
		$languageLink['hreflang'] = $ilInterwikiCodeBCP47;
	}

	/**
	 * Handler for SkinTemplateGetLanguageLink
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 *
	 * @param array &$languageLink containing data about the link
	 * @param Title $languageLinkTitle object for the external language link
	 * @param Title $title object for the page the link belongs to
	 * @param OutputPage $outputPage for the page the link belongs to
	 * @return bool
	 */
	public static function onSkinTemplateGetLanguageLink(
		&$languageLink,
		$languageLinkTitle,
		$title,
		$output
	) {
		global $wgLCOverrideGroup;
		global $wgLCOverrideCodes;

		$group = null;

		$dbname = $languageLinkTitle->getTransWikiID();
		if ( $dbname !== false ) {
			$services = \MediaWiki\MediaWikiServices::getInstance();
			if ( $services !== null ) {
				$siteLookup = $services->getSiteLookup();
				if ( $siteLookup !== null ) {
					$site = $siteLookup->getSite( $dbname );
					if ( $site !== null ) {
						$group = $site->getGroup();
						if ( $group === null ) {
							wfDebugLog( 'LangCodeOverride', "Could not find a valid group name, using default." );
							$group = $wgLCOverrideGroup;
						}
					} else {
						wfDebugLog( 'LangCodeOverride', "Could not find Site for $dbname." );
					}
				} else {
					wfDebugLog( 'LangCodeOverride', "Could not find SiteLookup for $dbname." );
				}
			} else {
				wfDebugLog( 'LangCodeOverride', "Could not find Services for $dbname." );
			}
		} else {
			wfDebugLog( 'LangCodeOverride', "Could not find DBname." );
		}


		if ( $group === null ) {
			wfDebugLog( 'LangCodeOverride', "Could not find a group name, using default." );
			$group = $wgLCOverrideGroup;
		}

		if ( !array_key_exists( $group, $wgLCOverrideCodes ) ) {
			wfDebugLog( 'LangCodeOverride', "Could not find a valid group name." );
			return true;
		}

		$overrideCodesForSite = $wgLCOverrideCodes[ $group ];
		if ( $overrideCodesForSite === null
			or !array_key_exists( 'lang', $languageLink )
			or !array_key_exists( $languageLink['lang'], $overrideCodesForSite )
		) {
			wfDebugLog( 'LangCodeOverride', "Could not find a override group." );
			return true;
		}

		$overrideCode = $overrideCodesForSite[ $languageLink['lang'] ];
		if ( $overrideCode === null ) {
			wfDebugLog( 'LangCodeOverride', "Could not find a override code." );
			return true;
		}

		wfDebugLog( 'LangCodeOverride', "Overrides language code: $languageLink[lang] → $overrideCode" );

		self::overrideLanguageLink(
			$languageLink,
			$overrideCode,
			$languageLinkTitle,
			$title,
			$output );

		return true;
	}

}