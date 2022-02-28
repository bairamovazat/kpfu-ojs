<?php

/**
 * @file plugins/importexport/articulus/ArticulusExportPlugin.inc.php
 *
 * Copyright (c) 2013-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticulusExportPlugin
 * @ingroup plugins_importexport_articulus
 *
 * @brief Articulus import/export plugin
 */

import('classes.plugins.ImportExportPlugin');

import('lib.pkp.classes.xml.XMLCustomWriter');
import('lib.pkp.classes.file.FileArchive');

define('ARTICULUS_DTD_ID', '-//PKP//OJS Articles and Issues XML//EN');

class ArticulusExportPlugin extends ImportExportPlugin {
	/**
	 * Constructor
	 */
	function ArticulusExportPlugin() {
		parent::ImportExportPlugin();
	}

	/**
	 * Get the DTD URL for the export XML.
	 * @return string
	 */
	function getDTDUrl() {
		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$currentVersion =& $versionDao->getCurrentVersion();
		return 'http://pkp.sfu.ca/ojs/dtds/' . urlencode($currentVersion->getMajor() . '.' . $currentVersion->getMinor() . '.' . $currentVersion->getRevision()) . '/articulus.dtd';
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ArticulusExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.articulus.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.articulus.description');
	}

	function display(&$args, $request) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args, $request);

		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$journal =& $request->getJournal();
		switch (array_shift($args)) {
			case 'exportIssues':
				$issueIds = $request->getUserVar('issueId');
				if (!isset($issueIds)) $issueIds = array();
				$issues = array();
				foreach ($issueIds as $issueId) {
					$issue =& $issueDao->getIssueById($issueId, $journal->getId());
					if (!$issue) $request->redirect();
					$issues[] =& $issue;
					unset($issue);
				}
				$this->exportIssues($journal, $issues);
				break;
			case 'exportIssue':
				$issueId = array_shift($args);
				$issue =& $issueDao->getIssueById($issueId, $journal->getId());
				if (!$issue) $request->redirect();
				$this->exportIssue($journal, $issue);
				break;
			case 'issues':
				// Display a list of issues for export
				$this->setBreadcrumbs(array(), true);
				AppLocale::requireComponents(LOCALE_COMPONENT_OJS_EDITOR);
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issues =& $issueDao->getIssues($journal->getId(), Handler::getRangeInfo('issues'));

				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
				break;
			default:
				// Display a list of issues for export
				$this->setBreadcrumbs(array(), true);
				AppLocale::requireComponents(LOCALE_COMPONENT_OJS_EDITOR);
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issues =& $issueDao->getIssues($journal->getId(), Handler::getRangeInfo('issues'));

				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
				break;
		}
	}
	
	function stripUtf16Be($string) {
		return preg_replace('/^\xfe\xff/', '', $string);
	}

	function exportIssue(&$journal, &$issue, $outputFile = null) {
		$this->import('ArticulusExportDom');
		$doc =& XMLCustomWriter::createDocument(null, ARTICULUS_DTD_ID, $this->getDTDUrl());
		$files = array();
		$issueNode =& ArticulusExportDom::generateIssueDom($doc, $journal, $issue, $files);
		XMLCustomWriter::appendChild($doc, $issueNode);
		//get ISSN for outputFile name
		if ($journal->getSetting('printIssn') != '') $ISSN = $journal->getSetting('printIssn');
		elseif ($journal->getSetting('issn') != '') $ISSN = $journal->getSetting('issn');
		elseif ($journal->getSetting('onlineIssn') != '') $ISSN = $journal->getSetting('onlineIssn');
		else $ISSN = '';
		$newFileName = ($ISSN != '' ? str_replace('-', '', $ISSN) : '') . '_' . $issue->getYear() ."_".$issue->getVolume() ."_".$issue->getNumber();
		
		if (empty($outputFile)) {
			$outputFile = Config::getVar('files', 'files_dir') . '/temp/' . $newFileName . "_unicode.xml";
		}
		if (($h = fopen($outputFile, 'wba+'))===false) return false;
		fwrite($h, XMLCustomWriter::getXML($doc));
		fclose($h);
		
		$files[] = basename($outputFile);
		$str = file_get_contents($outputFile);
		$str = preg_replace('/\s+encoding\=\"UTF-8\"/', '', $str);
		$str = "\xFF\xFE".mb_convert_encoding($str, "UCS-2LE", "auto");
		file_put_contents($outputFile, $str);
		
		$filesDir = Config::getVar('files', 'files_dir') . '/temp/';
		$fileArchive = new FileArchive();
		$archivePath = $fileArchive->create($files, $filesDir);
		if (file_exists($archivePath)) {
			$fileManager = new FileManager();
			if ($fileArchive->zipFunctional()) {
				$fileManager->downloadFile($archivePath, 'application/x-zip', false, $newFileName . '.zip');
			} else {
				$fileManager->downloadFile($archivePath, 'application/x-gtar', false, $newFileName . '.tar.gz');
			}
			$fileManager->deleteFile($archivePath);
			foreach ($files as $file){
				$fileManager->deleteFile($filesDir.$file);
			}
		} else {
			fatalError('Creating archive with submission files failed!');
		}
		
		return true;
	}

	function exportIssues(&$journal, &$issues, $outputFile = null) {
		$this->import('ArticulusExportDom');
		$zips = array();
		$filesDir = Config::getVar('files', 'files_dir') . '/temp/';
		foreach ($issues as $issue) {
			$files = array();
			$doc =& XMLCustomWriter::createDocument(null, ARTICULUS_DTD_ID, $this->getDTDUrl());
			$issueNode =& ArticulusExportDom::generateIssueDom($doc, $journal, $issue, $files);
			XMLCustomWriter::appendChild($doc, $issueNode);
			//get ISSN for outputFile name
			if ($journal->getSetting('printIssn') != '') $ISSN = $journal->getSetting('printIssn');
			elseif ($journal->getSetting('issn') != '') $ISSN = $journal->getSetting('issn');
			elseif ($journal->getSetting('onlineIssn') != '') $ISSN = $journal->getSetting('onlineIssn');
			else $ISSN = '';
			$newFileName = ($ISSN != '' ? str_replace('-', '', $ISSN) : '') . '_' . $issue->getYear() ."_".$issue->getVolume() ."_".$issue->getNumber();
			
			$outputFile = Config::getVar('files', 'files_dir') . '/temp/' . $newFileName . "_unicode.xml";
			if (($h = fopen($outputFile, 'wba+'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
			
			$files[] = $newFileName . "_unicode.xml";
			$fileArchive = new FileArchive();
			$archivePath = $fileArchive->create($files, $filesDir);
			if (file_exists($archivePath)) {
				$fileManager = new FileManager();
				if ($fileArchive->zipFunctional()) {
					$fileManager->copyFile($archivePath, Config::getVar('files', 'files_dir') . '/temp/' . $newFileName . '.zip');
					$zips[] = $newFileName . '.zip';
				} else {
					$fileManager->copyFile($archivePath, Config::getVar('files', 'files_dir') . '/temp/' . $newFileName . '.tar.gz');
					$zips[] = $newFileName . '.tar.gz';
				}
				$fileManager->deleteFile($archivePath);
				foreach ($files as $file){
					$fileManager->deleteFile($filesDir.$file);
				}
			} else {
				fatalError('Creating archive with submission files failed!');
			}
		}

		$fileArchive = new FileArchive();
		$archivePath = $fileArchive->create($zips, $filesDir);
		if (file_exists($archivePath)) {
			$fileManager = new FileManager();
			if ($fileArchive->zipFunctional()) {
				$fileManager->downloadFile($archivePath, 'application/x-zip', false, 'issues.zip');
			} else {
				$fileManager->downloadFile($archivePath, 'application/x-gtar', false, 'issues.tar.gz');
			}
			$fileManager->deleteFile($archivePath);
			foreach ($zips as $file){
				$fileManager->deleteFile($filesDir.$file);
			}
		} else {
			fatalError('Creating archive with submission files failed!');
		}
		return true;
	}

	function &getDocument($fileName) {
		$parser = new XMLParser();
		$returner =& $parser->parse($fileName);
		return $returner;
	}

	function getRootNodeName(&$doc) {
		return $doc->name;
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */
	function executeCLI($scriptName, &$args) {
		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);

		AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');

		$journal =& $journalDao->getJournalByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.articulus.cliError') . "\n";
				echo __('plugins.importexport.articulus.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		$this->import('ArticulusImportDom');
		if ($xmlFile && ArticulusImportDom::isRelativePath($xmlFile)) {
			$xmlFile = PWD . '/' . $xmlFile;
		}

		switch ($command) {
			case 'import':
				$userName = array_shift($args);
				$user =& $userDao->getByUsername($userName);

				if (!$user) {
					if ($userName != '') {
						echo __('plugins.importexport.articulus.cliError') . "\n";
						echo __('plugins.importexport.articulus.error.unknownUser', array('userName' => $userName)) . "\n\n";
					}
					$this->usage($scriptName);
					return;
				}

				$doc =& $this->getDocument($xmlFile);

				$context = array(
					'user' => $user,
					'journal' => $journal
				);

				switch ($this->getRootNodeName($doc)) {
					case 'article':
					case 'articles':
						// Determine the extra context information required
						// for importing articles.
						if (array_shift($args) !== 'issue_id') return $this->usage($scriptName);
						$issue =& $issueDao->getIssueByBestIssueId(($issueId = array_shift($args)), $journal->getId());
						if (!$issue) {
							echo __('plugins.importexport.articulus.cliError') . "\n";
							echo __('plugins.importexport.articulus.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
							return;
						}

						$context['issue'] =& $issue;

						switch (array_shift($args)) {
							case 'section_id':
								$section =& $sectionDao->getSection(($sectionIdentifier = array_shift($args)));
								break;
							case 'section_name':
								$section =& $sectionDao->getSectionByTitle(($sectionIdentifier = array_shift($args)), $journal->getId());
								break;
							case 'section_abbrev':
								$section =& $sectionDao->getSectionByAbbrev(($sectionIdentifier = array_shift($args)), $journal->getId());
								break;
							default:
								return $this->usage($scriptName);
						}

						if (!$section) {
							echo __('plugins.importexport.articulus.cliError') . "\n";
							echo __('plugins.importexport.articulus.export.error.sectionNotFound', array('sectionIdentifier' => $sectionIdentifier)) . "\n\n";
							return;
						}
						$context['section'] =& $section;
				}

				$result = $this->handleImport($context, $doc, $errors, $issues, $articles, true);
				if ($result) {
					echo __('plugins.importexport.articulus.import.success.description') . "\n\n";
					if (!empty($issues)) echo __('issue.issues') . ":\n";
					foreach ($issues as $issue) {
						echo "\t" . $issue->getIssueIdentification() . "\n";
					}

					if (!empty($articles)) echo __('article.articles') . ":\n";
					foreach ($articles as $article) {
						echo "\t" . $article->getLocalizedTitle() . "\n";
					}
				} else {
					$errorsTranslated = array();
					foreach ($errors as $error) {
						$errorsTranslated[] = __($error[0], $error[1]);
					}
					echo __('plugins.importexport.articulus.cliError') . "\n";
					foreach ($errorsTranslated as $errorTranslated) {
						echo "\t" . $errorTranslated . "\n";
					}
				}
				return;
				break;
			case 'export':
				if ($xmlFile != '') switch (array_shift($args)) {
					case 'article':
						$articleId = array_shift($args);
						$publishedArticle =& $publishedArticleDao->getPublishedArticleByBestArticleId($journal->getId(), $articleId);
						if ($publishedArticle == null) {
							echo __('plugins.importexport.articulus.cliError') . "\n";
							echo __('plugins.importexport.articulus.export.error.articleNotFound', array('articleId' => $articleId)) . "\n\n";
							return;
						}
						$issue =& $issueDao->getIssueById($publishedArticle->getIssueId(), $journal->getId());

						$sectionDao =& DAORegistry::getDAO('SectionDAO');
						$section =& $sectionDao->getSection($publishedArticle->getSectionId());

						if (!$this->exportArticle($journal, $issue, $section, $publishedArticle, $xmlFile)) {
							echo __('plugins.importexport.articulus.cliError') . "\n";
							echo __('plugins.importexport.articulus.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'articles':
						$results =& ArticleSearch::formatResults($args);
						if (!$this->exportArticles($results, $xmlFile)) {
							echo __('plugins.importexport.articulus.cliError') . "\n";
							echo __('plugins.importexport.articulus.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'issue':
						$issueId = array_shift($args);
						$issue =& $issueDao->getIssueByBestIssueId($issueId, $journal->getId());
						if ($issue == null) {
							echo __('plugins.importexport.articulus.cliError') . "\n";
							echo __('plugins.importexport.articulus.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
							return;
						}
						if (!$this->exportIssue($journal, $issue, $xmlFile)) {
							echo __('plugins.importexport.articulus.cliError') . "\n";
							echo __('plugins.importexport.articulus.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'issues':
						$issues = array();
						while (($issueId = array_shift($args))!==null) {
							$issue =& $issueDao->getIssueByBestIssueId($issueId, $journal->getId());
							if ($issue == null) {
								echo __('plugins.importexport.articulus.cliError') . "\n";
								echo __('plugins.importexport.articulus.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
								return;
							}
							$issues[] =& $issue;
						}
						if (!$this->exportIssues($journal, $issues, $xmlFile)) {
							echo __('plugins.importexport.articulus.cliError') . "\n";
							echo __('plugins.importexport.articulus.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
				}
				break;
		}
		$this->usage($scriptName);
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.articulus.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
