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

	public static function linkText(
		$title,
		$langCode,
		$langName
	) {
		$linkText = $title->getText();
		if ( strval( $langName ) !== '' ) {
			// Use the language autonym as display text
			return $langName;
		}

		$displayTextMsg = wfMessage( "interlanguage-link-$langCode" );
		if ( !$displayTextMsg->isDisabled() ) {
			// Use custom MW message for the display text
			return $displayTextMsg->text();
		}

		// use the fallback
		return $title->getText();
	}

	public static function linkTitle(
		$title,
		$langCode,
		$langName
	) {
		$linkTitle = $title->getText();

		if ( $langName !== '' ) {
			$linkTitleMsg = ( ( $linkTitle === '' )
				? wfMessage( 'interlanguage-link-title-langonly', $langName )
				: wfMessage( 'interlanguage-link-title', $linkTitle, $langName )
			);
			return $linkTitleMsg->text();
		}

		$displayNameMsg = wfMessage( "interlanguage-link-sitename-$langCode" )->text();
		if ( !$displayNameMsg->isDisabled() ) {
			$displayName = $siteName->text();
			$linkTitleMsg = ( ( $linkTitle === '' )
				? wfMessage( 'interlanguage-link-title-nonlangonly', $displayName )
				: wfMessage( 'interlanguage-link-title-nonlang', $linkTitle, $displayName )
			);
			return $linkTitleMsg->text();
		}

		// we have nothing friendly to put in the title, so fall back to
		// displaying the interlanguage link itself in the title text
		// (similar to what is done in page content)
		return $title->getInterwiki() . ":$linkTitle";
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

		$langName = \Language::fetchLanguageName( $overrideLangCode );

		$linkText = self::linkText(
			$languageLinkTitle,
			$overrideLangCode,
			$skin->formatLanguageName( $langName )
		);

		// CLDR extension or similar is required to localize the language name;
		// otherwise we'll end up with the autonym again.
		$langLocalName = \Language::fetchLanguageName(
			$overrideLangCode,
			$userLang->getCode()
		);

		$linkTitle = self::linkTitle(
			$languageLinkTitle,
			$overrideLangCode,
			$langLocalName
		);

		$langCodeBCP47 = $languageCode::bcp47( $overrideLangCode );
		$class = "interlanguage-link interwiki-$overrideLangCode";

		$languageLink['href'] = $languageLinkTitle->getFullURL();
		$languageLink['text'] = $linkText;
		$languageLink['title'] = $linkTitle;
		$languageLink['class'] = $class;
		$languageLink['link-class'] = 'interlanguage-link-target';
		$languageLink['lang'] = $langCodeBCP47;
		$languageLink['hreflang'] = $langCodeBCP47;
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
