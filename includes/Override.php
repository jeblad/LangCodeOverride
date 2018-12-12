<?php

namespace LangCodeOverride;

/**
 * Correct the language link in the langlink box.
 *
 * @ingroup Extensions
 */

class Override {

	public $mOverrides = [];

	function __construct() {}

	/**
	 * Make a new override instance and add the language codes
	 * 
	 * @param array $langCodes an array of language pairs
	 * @return the instance
	 */
	public static function makeFromLangCodes( array $langCodes ) {
		$instance = new Override();

		foreach ( $langCodes as $sourceLang => $targetLang ) {
			$instance->addOverride( $sourceLang, $targetLang );
		}

		return $instance;
	}

	/**
	 * Add a single language pair to be overridden
	 *
	 * @param string $sourceLang the language to be replaced
	 * @param string $targetLang the language to be the replacement
	 */
	public function addOverride( $sourceLang, $targetLang ) {
		$this->mOverrides[$sourceLang] = $targetLang;
		wfDebugLog( 'LangCodeOverride',
			"Setting up pair ($sourceLang – $targetLang)." );
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
		string $langName,
		string $langCode,
		\Title $title
	) {
		// Use the given language autonym for the link text?
		if ( $langName !== '' ) {
			return $langName;
		}

		// Use the language code to look up the message for the link text?
		if ( $langCode !== '' ) {
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
		string $langName,
		string $langCode,
		\Title $title
	) {
		$linkTitle = $title->getText();

		// Use the given language autonym for the link title?
		if ( $langName !== '' ) {
			$linkTitleMsg = ( ( $linkTitle === '' )
				? wfMessage( 'interlanguage-link-title-langonly', $langName )
				: wfMessage( 'interlanguage-link-title', $linkTitle, $langName )
			);
			return $linkTitleMsg->text();
		}

		// Use the language code to look up the message for the link title?
		if ( $langCode !== '' ) {
			$displayNameMsg = wfMessage( "interlanguage-link-sitename-$langCode" );
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
		return $title->getInterwiki() . ":" . $linkTitle;
	}

	/**
	 * Change a language link structure
	 * This tries to mimic the inner actions of SkinTemplate::getLanguages()
	 *
	 * An formal parameter is passed on, which is never used.
	 * @SuppressWarnings(PHPMD.UnusedFormalParameters)
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 *
	 * @param array &$languageLink containing data about the link
	 * @param Title $languageLinkTitle object for the external language link
	 * @param Title $title object for the page the link belongs to
	 * @param OutputPage $output for the page the link belongs to
	 * @param Language $language for testing purposes
	 * @param LanguageCode $languageCode for testing purposes
	 */
	public function changeLanguageLink(
		&$languageLink,
		$languageLinkTitle,
		$title,
		$output,
		 // prepare for testing
		$language = \Language::class,
		$languageCode = \LanguageCode::class
	) {
		$sourceLang = Util::findValue( 'lang', $languageLink );
		if ( $sourceLang === null ) {
			wfDebugLog( 'LangCodeOverride',
				"Could not find a value for 'lang' key in link structure." );
			return false;
		}

		$targetLang = Util::findValue( $sourceLang, $this->mOverrides );
		if ( $targetLang === null ) {
			wfDebugLog( 'LangCodeOverride',
				"Could not find a value for '$targetLang' key in override structure." );
			return false;
		}

		$skin = $output->getContext()->getSkin();
		$userLang = $skin->getLanguage();

		$langName = $language::fetchLanguageName( $targetLang );

		$linkText = self::linkText(
			$skin->formatLanguageName( $langName ),
			$targetLang,
			$languageLinkTitle
		);
		echo "linkText: $linkText.";

		// CLDR extension or similar is required to localize the language name;
		// otherwise we'll end up with the autonym again.
		$langLocalName = $language::fetchLanguageName(
			$targetLang,
			$userLang->getCode()
		);

		$linkTitle = self::linkTitle(
			$langLocalName,
			$targetLang,
			$languageLinkTitle
		);

		$langCodeBCP47 = $languageCode::bcp47( $targetLang );
		$class = "interlanguage-link interwiki-$targetLang";

		$languageLink['href'] = $languageLinkTitle->getFullURL();
		$languageLink['text'] = $linkText;
		$languageLink['title'] = $linkTitle;
		$languageLink['class'] = $class;
		$languageLink['link-class'] = 'interlanguage-link-target';
		$languageLink['lang'] = $langCodeBCP47;
		$languageLink['hreflang'] = $langCodeBCP47;

		wfDebugLog( 'LangCodeOverride',
			"Done converting link structure for ($sourceLang – $targetLang) pair." );
		return true;
	}

}
