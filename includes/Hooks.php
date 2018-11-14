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
	 * Figure out which one of several possible texts to use
	 *
	 * @param string $langName the name to be used if exist
	 * @param string $langCode the code to be used if exist
	 * @param \Title $title the object to use as fallback
	 * @return string the link text to use
	 */
	public static function linkText(
		$langName,
		$langCode,
		\Title $title
	) {
		// Use the given language autonym for the link text?
		if ( strval( $langName ) !== '' ) {
			return $langName;
		}

		// Use the language code to look up the message for the link text?
		if ( strval( $langCode ) !== '' ) {
			$displayTextMsg = wfMessage( "interlanguage-link-$langCode" );
			if ( !$displayTextMsg->isDisabled() ) {
				return $displayTextMsg->text();
			}
		}

		// We have nothing friendly to put in the title, so fall back to
		// displaying the interlanguage link itself in the link text
		return $title->getText();
	}

	/**
	 * Figure out which one of several possible texts to use
	 *
	 * @param string $langName the name to be used if exist
	 * @param string $langCode the code to be used if exist
	 * @param \Title $title the object to use as fallback
	 * @return string the link title to use
	 */
	public static function linkTitle(
		$langName,
		$langCode,
		$title
	) {
		// Use the given language autonym for the link title?
		if ( strval( $langName ) !== '' ) {
			$linkTitle = $title->getText();
			$linkTitleMsg = ( ( $linkTitle === '' )
				? wfMessage( 'interlanguage-link-title-langonly', $langName )
				: wfMessage( 'interlanguage-link-title', $linkTitle, $langName )
			);
			return $linkTitleMsg->text();
		}

		// Use the language code to look up the message for the link title?
		if ( strval( $langCode ) !== '' ) {
			$displayNameMsg = wfMessage( "interlanguage-link-sitename-$langCode" )->text();
			if ( !$displayNameMsg->isDisabled() ) {
				$displayName = $displayNameMsg->text();
				$linkTitleMsg = ( ( $linkTitle === '' )
					? wfMessage( 'interlanguage-link-title-nonlangonly', $displayName )
					: wfMessage( 'interlanguage-link-title-nonlang', $linkTitle, $displayName )
				);
				return $linkTitleMsg->text();
			}
		}

		// we have nothing friendly to put in the title, so fall back to
		// displaying the interlanguage link itself in the link title
		return $title->getInterwiki() . ":$linkTitle";
	}

	/**
	 * Override the language link
	 * This tries to mimic the inner actions of SkinTemplate::getLanguages()
	 *
	 * An formal parameter is passed on, which is never used.
	 * @SuppressWarnings(PHPMD.UnusedFormalParameters)
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
		 // for testing purposes
		$language = \Language::class,
		$languageCode = \LanguageCode::class
	) {
		$skin = $output->getContext()->getSkin();
		$userLang = $skin->getLanguage();

		$langName = $language::fetchLanguageName( $overrideLangCode );

		$linkText = self::linkText(
			$skin->formatLanguageName( $langName ),
			$overrideLangCode,
			$languageLinkTitle
		);

		// CLDR extension or similar is required to localize the language name;
		// otherwise we'll end up with the autonym again.
		$langLocalName = $language::fetchLanguageName(
			$overrideLangCode,
			$userLang->getCode()
		);

		$linkTitle = self::linkTitle(
			$langLocalName,
			$overrideLangCode,
			$languageLinkTitle
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
