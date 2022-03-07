<?php

/**
 * @file KazGikThemePlugin.inc.php
 *
 * Copyright (c) 2016 Kazan Federal University
 * Copyright (c) 2016 Shamil K.
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class KazGikThemePlugin
 * @ingroup plugins_themes_KazGik
 *
 * @brief "KazGik" theme plugin
 */

import('lib.pkp.classes.plugins.ThemePlugin');

class KazGikThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'KazGikThemePlugin';
	}

	function getDisplayName() {
		return 'KazGik Vestnik Theme';
	}

	function getDescription() {
		return 'Stylesheet with blue header bar and embossed text';
	}

	function getStylesheetFilename() {
		return 'kazgik.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}

	public function init()
	{
		// TODO: Implement init() method.
	}
}

?>
