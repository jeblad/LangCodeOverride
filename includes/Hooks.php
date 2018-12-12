<?php

namespace LangCodeOverride;

/**
 * Hook handlers for the LangCodeOverride extension
 *
 * @ingroup Extensions
 */
class Hooks {

	/**
	 * Static store for the wiki specific overrides
	 */
	private static $override = null;

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
	 * Resolve the DBname into language codes to override
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 *
	 * @param string $dbName is the database name to use
	 * @return array of language codes to override
	 */
	protected static function findOverrideCodes( $dbName ) {
		global $wgLCOverrideCodes;

		// Start out empty
		$overrideCodes = [];

		// find the site lookup
		$siteLookup = \MediaWiki\MediaWikiServices::getInstance()->getSiteLookup();
		if ( $siteLookup === null ) {
			wfErrorLog( 'LangCodeOverride',
				"Could not locate a valid 'siteLookup' service." );
			return $overrideCodes;
		}
		
		// This makes the assumption that $dbName is the sole identification
		// of a language specific database, that is no table prefix in use.
		$site = $siteLookup->getSite( $dbName );
		if ( $site === null ) {
			wfErrorLog( 'LangCodeOverride',
				"Could not locate a valid '$dbName' site instance." );
			return $overrideCodes;
		}

		// Get a group entry from the config.
		$group = $site->getGroup();
		if ( $group === null or !is_string( $group ) ) {
			wfWarningLog( 'LangCodeOverride',
				"Could not get a valid 'group' entry from the site instance." );
			return $overrideCodes;
		}

		// With a fixed $group, then the $override will also be fixed. That is
		// the overrides for a given $wgDBname will never change.
		$overrideCodes = Util::findValue( $group, $wgLCOverrideCodes );
		if ( $overrideCodes === null or !is_array( $overrideCodes ) ) {
			wfWarningLog( 'LangCodeOverride',
				"Could not find a valid '$group' entry among override structures." );
			return $overrideCodes;
		}

		return $overrideCodes;
	}

	/**
	 * Handler for SkinTemplateGetLanguageLink
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
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

		if ( self::$override === null ) {
			$langCodes = self::findOverrideCodes( $wgDBname );
			self::$override = Override::makeFromLangCodes( $langCodes );
		}

		self::$override->changeLanguageLink(
			$languageLink,
			$languageLinkTitle,
			$title,
			$output
		);

		return true;
	}

}
