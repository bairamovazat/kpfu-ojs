<?php

/**
 * @defgroup plugins_importexport_articulus
 */
 
/**
 * @file plugins/importexport/articulus/index.php
 *
 * Copyright (c) 2013-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_articulus
 * @brief Wrapper for articulus XML import/export plugin.
 *
 */

require_once('ArticulusExportPlugin.inc.php');

return new ArticulusExportPlugin();

?>
