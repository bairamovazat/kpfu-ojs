<?php

/**
 * @file LJMThemePlugin.inc.php
 *
 * Copyright (c) 2016 Lobochecskii Journal of Mathematics Kazan Federal University
 * Copyright (c) 2016 Shamil K.
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LJMThemePlugin
 * @ingroup plugins_themes_ljmTheme
 *
 * @brief "LJM" theme plugin
 */

import('classes.plugins.ThemePlugin');

class LJMThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'LJMThemePlugin';
	}

	function getDisplayName() {
		return 'LJM Theme';
	}

	function getDescription() {
		return 'Stylesheet with blue header bar and embossed text';
	}

	function getStylesheetFilename() {
		return 'ljm.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
