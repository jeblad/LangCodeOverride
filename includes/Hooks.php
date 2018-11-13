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
		// empty
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
	 * @param OutputPage $output for the page the link belongs to
	 * @param Language $language for testing purposes
	 * @param LanguageCode $languageCode for testing purposes
	 */
	public static function overrideLanguageLink(
		&$languageLink,
		$overrideLangCode,
		$languageLinkTitle,
		$title,
		$output,
		$language = \Language::class,
		$languageCode = \LanguageCode::class
	) {
		$skin = $output->getContext()->getSkin();
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
	 * Get group for the given database id
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 *
	 * @param string $dbname the identifier for the database
	 * @param \MediaWiki\MediaWikiServices $services the provider
	 * @return string|null the group for the database
	 */
	public static function getGroup(
		$dbname,
		$services = \MediaWiki\MediaWikiServices::class
	) {
		$siteLookup = $services::getInstance()->getSiteLookup();
		if ( $siteLookup === null ) {
			return null;
		}

		$site = $siteLookup->getSite( $dbname );
		if ( $site === null ) {
			return null;
		}

		$group = $site->getGroup();

		return $group;
	}

	/**
	 * Find value give a needle and a heystack
	 *
	 * @param string|null $needle to find
	 * @param array|null $heystack to search
	 * @return any|null whats found
	 */
	public static function findValue( $needle, $heystack ) {

		if ( $needle === null ) {
			return null;
		}

		if ( $heystack === null ) {
			return null;
		}

		if ( !array_key_exists( $needle, $heystack ) ) {
			return null;
		}

		$value = $heystack[$needle];

		return $value;
	}

	/**
	 * Handler for SkinTemplateGetLanguageLink
	 *
	 * @param array &$languageLink containing data about the link
	 * @param Title $languageLinkTitle object for the external language link
	 * @param Title $title object for the page the link belongs to
	 * @param OutputPage $output for the page the link belongs to
	 * @return bool
	 */
	public static function onSkinTemplateGetLanguageLink(
		&$languageLink,
		$languageLinkTitle,
		$title,
		$output
	) {
		global $wgDBname;
		global $wgLCOverrideCodes;

		// This makes the assumption that $wgDBname is the sole identification
		// of a language specific database, that is no table prefix in use.
		// It also imply that $wgDBname can change during normal operation
		// as long as the interpretation of the previous name does not change.
		static $group = null;
		if ( $group === null ) {
			$group = self::getGroup( $wgDBname );
			if ( $group === null ) {
				wfDebugLog( 'LangCodeOverride',
					"Could not find a valid '$group' entry at services." );
			}
		}

		// With a fixed $group, then the $overrides will also be fixed. That is
		// the overrides for a given $wgDBname will never change.
		static $overrides = null;
		if ( $overrides === null ) {
			$overrides = self::findValue( $group, $wgLCOverrideCodes );
			if ( $overrides === null ) {
				wfDebugLog( 'LangCodeOverride',
					"Could not find a '$group' key among override structures." );
			}
		}

		// Attempt to find the language code…
		$langCode = self::findValue( 'lang', $languageLink );
		if ( $overrides === null ) {
			wfDebugLog( 'LangCodeOverride',
				"Could not find a value for 'lang' key in link structure." );
		}

		// … and then find a matching override code…
		$overrideCode = self::findValue( $langCode, $overrides );
		if ( $overrides === null ) {
			wfDebugLog( 'LangCodeOverride',
				"Could not find a value for '$langCode' key in override structure." );
		}

		// … and if so override the language link
		self::overrideLanguageLink(
			$languageLink,
			$overrideCode,
			$languageLinkTitle,
			$title,
			$output
		);

		return true;
	}

}
