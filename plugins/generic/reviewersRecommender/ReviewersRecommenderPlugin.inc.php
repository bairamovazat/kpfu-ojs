<?php

/**
 * @file plugins/generic/reviewersRecommender/ReviewersRecommenderPlugin.inc.php
 *
 * Copyright (c) 2017-2019 Kazan Federal University
 * Copyright (c) 2017-2019 Shamil K.
 *
 * @class ReviewersRecommenderPlugin
 * @ingroup plugins_generic_reviewersRecommender
 *
 * @brief reviewersRecommender plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ReviewersRecommenderPlugin extends GenericPlugin {

	function register($category, $path) { 
		$success = parent::register($category, $path);
        if ($success && $this->getEnabled()) { 
			$this->import('classes.RevRecReviewAssignmentDAO');
			// PHP4 Requires explicit instantiation-by-reference
			if (checkPhpVersion('5.0.0')) {
				$revrecDao = new RevRecReviewAssignmentDAO($this->getName());
			} else {
				$revrecDao =& new RevRecReviewAssignmentDAO($this->getName());
			}
			$returner =& DAORegistry::registerDAO('RevRecReviewAssignmentDAO', $revrecDao);
			
			$this->import('classes.RevInterestDAO');
			$revInterestDAO = new RevInterestDAO($this->getName());
			DAORegistry::registerDAO('RevInterestDAO', $revInterestDAO);
			
            HookRegistry::register('LoadHandler', array($this, 'callback')); 
			HookRegistry::register('userdao::getAdditionalFieldNames', array($this, 'callbackUserDaoAdditionalFieldNames'));
        }
		return $success; 
    } 
	
	function callbackUserDaoAdditionalFieldNames($hookName, $params) {
		$fields =& $params[1];
		$fields[] = 'mathscinet';
		$fields[] = 'zbmath';
		return false;
	}

	function getDisplayName() {
		return __('plugins.generic.revrec.displayName');
	}

	function getDescription() {
		return __('plugins.generic.revrec.description');
	}
	
	
	/**
	 * Get the template path for this plugin.
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	/**
	 * Get the handler path for this plugin.
	 */
	function getHandlerPath() {
		return $this->getPluginPath() . '/pages/';
	}
	
	/**
	 * @see GenericPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		$this->import('pages.RevRecParserHandler');
		$revrec = new RevRecParserHandler($this->getName());
		switch ($verb) {
			default:
				$revrec->index();
				return true;
		}
	}
	
	//
	// Implement template methods from GenericPlugin.
	//
	/**
	 * @see GenericPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('index', __('plugins.generic.revrec.index'));
		}
		return parent::getManagementVerbs($verbs);
	}
	
	function callback($hookName, $params) {
		$page =& $params[0];
		
		if ($page == 'editor') {
			$op =& $params[1];

			if ($op) {
				$editorPages = array(
					'selectReviewer',
				);

				if (in_array($op, $editorPages)) {
					define('HANDLER_CLASS', 'RevRecParserHandler');
					define('REVREC_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'RevRecParserHandler.inc.php';
				}
			}
		}
		if ($page == 'manager') {
			$op =& $params[1];

			if ($op) {
				$editorPages = array(
					'editUser',
					'updateUser',
					'userProfile',
				);

				if (in_array($op, $editorPages)) {
					define('HANDLER_CLASS', 'RevPeopleHandler');
					define('REVREC_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'RevPeopleHandler.inc.php';
				}
			}
		}
	}
	

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}
	
	
	function getInstallDataFile() {
		return $this->getPluginPath() . '/' . 'data.xml';
	}
	
}
?>
