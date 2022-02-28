<?php

/**
 * @file plugins/importexport/articulus/ArticulusExportDom.inc.php
 *
 * Copyright (c) 2017 Kazan Federal University
 * Copyright (c) 2017 Shamil K.
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticulusExportDom
 * @ingroup plugins_importexport_articulus
 *
 * @brief Articulus import/export plugin DOM functions for export
 */

import('lib.pkp.classes.xml.XMLCustomWriter');

class ArticulusExportDom {
	function &generateIssueDom(&$doc, &$journal, &$issue, &$galleys) {
		$root =& XMLCustomWriter::createElement($doc, 'journal');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance", false);
		XMLCustomWriter::setAttribute($root, 'xsi:noNamespaceSchemaLocation', "JournalArticulus.xsd", false);
		
		/* --- OperCard --- */
		$operCardNode =& XMLCustomWriter::createElement($doc, 'operCard');
		XMLCustomWriter::createChildWithText($doc, $operCardNode, 'operator', "Articulus_8421", false);
		XMLCustomWriter::createChildWithText($doc, $operCardNode, 'date', date("Y-m-d H:i:s"), false);
		
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		foreach ($sectionDao->getSectionsForIssue($issue->getId()) as $section) {
			//$cntArticle+=count($publishedArticleDao->getPublishedArticlesBySectionId($section->getId(), $issue->getId()));
			$startPages=null;
			foreach ($publishedArticleDao->getPublishedArticlesBySectionId($section->getId(), $issue->getId()) as $article) {
				if(!$startPages) $startPages=$article->getPages();
				$endPages=$article->getPages();
				$cntArticle++;
			}

		}
		XMLCustomWriter::createChildWithText($doc, $operCardNode, 'cntArticle', $cntArticle, false);
		XMLCustomWriter::appendChild($root, $operCardNode);
		unset($operCardNode);
		
		/* --- titleid & ISSN--- */
		XMLCustomWriter::createChildWithText($doc, $root, 'titleid', "1829", false);
		// check various ISSN fields to create the ISSN tag
		if ($journal->getSetting('printIssn') != '') $ISSN = $journal->getSetting('printIssn');
		elseif ($journal->getSetting('issn') != '') $ISSN = $journal->getSetting('issn');
		elseif ($journal->getSetting('onlineIssn') != '') $ISSN = $journal->getSetting('onlineIssn');
		else $ISSN = '';

		if ($ISSN != '') XMLCustomWriter::createChildWithText($doc, $root, 'issn', $ISSN, false);

		/* --- journalInfo --- */
		
		foreach(array_keys($journal->getSupportedLocaleNames()) as $locale){
			$journalInfoNode =& XMLCustomWriter::createElement($doc, 'journalInfo');
			XMLCustomWriter::setAttribute($journalInfoNode, 'lang', ArticulusExportDom::formatLocale($locale), false);
			XMLCustomWriter::createChildWithText($doc, $journalInfoNode, 'title', $journal->getLocalizedTitle($locale), false);
			XMLCustomWriter::appendChild($root, $journalInfoNode);
			unset($journalInfoNode);
		}
		
		
		/* --- Issues Node --- */
		$issueNode =& XMLCustomWriter::createElement($doc, 'issue');

		switch (
			(int) $issue->getShowVolume() .
			(int) $issue->getShowNumber() .
			(int) $issue->getShowYear() .
			(int) $issue->getShowTitle()
		) {
			case '1111': $idType = 'num_vol_year_title'; break;
			case '1110': $idType = 'num_vol_year'; break;
			case '1010': $idType = 'vol_year'; break;
			case '0111': $idType = 'num_year_title'; break;
			case '0010': $idType = 'year'; break;
			case '1000': $idType = 'vol'; break;
			case '0001': $idType = 'title'; break;
			default: $idType = null;
		}
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'volume', $issue->getVolume(), false);
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'number', $issue->getNumber(), false);
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'dateUni', $issue->getYear(), false);
		XMLCustomWriter::createChildWithText($doc, $issueNode, 'pages', preg_replace("/(?:\-\d+)*\|\d+/", "", "$startPages|$endPages"), false);

		foreach ($sectionDao->getSectionsForIssue($issue->getId()) as $section) {
			$sectionNode =& ArticulusExportDom::generateSectionDom($doc, $journal, $issue, $section, $galleys);
			XMLCustomWriter::appendChild($issueNode, $sectionNode);
			unset($sectionNode);
		}
		XMLCustomWriter::appendChild($root, $issueNode);
		unset($issueNode);
		
		return $root;
	}

	function &generateSectionDom(&$doc, &$journal, &$issue, &$section, &$galleys) {
		$root =& XMLCustomWriter::createElement($doc, 'articles');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		foreach ($publishedArticleDao->getPublishedArticlesBySectionId($section->getId(), $issue->getId()) as $article) {
			$articleNode =& ArticulusExportDom::generateArticleDom($doc, $journal, $issue, $section, $article, $galleys);
			XMLCustomWriter::appendChild($root, $articleNode);
			unset($articleNode);
		}

		return $root;
	}

	function &generateArticleDom(&$doc, &$journal, &$issue, &$section, &$article, &$galleys) {
		$root =& XMLCustomWriter::createElement($doc, 'article');

		XMLCustomWriter::createChildWithText($doc, $root, 'pages', $article->getPages(), false);
		
		XMLCustomWriter::createChildWithText($doc, $root, 'artType', 'RAR', false);
		
		
		/* --- Authors --- */

		$authors =& XMLCustomWriter::createElement($doc, 'authors');
		foreach ($article->getAuthors() as $author) {
			$num++;
			$authorNode =& ArticulusExportDom::generateAuthorDom($doc, $journal, $issue, $article, $author, $num);
			XMLCustomWriter::appendChild($authors, $authorNode);
			unset($authorNode);
		}
		XMLCustomWriter::appendChild($root, $authors);
		unset($authors);
		
		/* --- Titles and Abstracts --- */
		$artTitles =& XMLCustomWriter::createElement($doc, 'artTitles');
		if (is_array($article->getTitle(null))) foreach ($article->getTitle(null) as $locale => $title) {
			$titleNode =& XMLCustomWriter::createChildWithText($doc, $artTitles, 'artTitle', $title, false);
			if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'lang', ArticulusExportDom::formatLocale($locale));
			unset($titleNode);
		}
		XMLCustomWriter::appendChild($root, $artTitles);
		unset($artTitles);

		$abstracts =& XMLCustomWriter::createElement($doc, 'abstracts');
		if (is_array($article->getAbstract(null))) foreach ($article->getAbstract(null) as $locale => $abstract) {
			$abstractNode =& XMLCustomWriter::createChildWithText($doc, $abstracts, 'abstract', strip_tags($abstract), false);
			if ($abstractNode) XMLCustomWriter::setAttribute($abstractNode, 'lang', ArticulusExportDom::formatLocale($locale));
			unset($abstractNode);
		}
		XMLCustomWriter::appendChild($root, $abstracts);
		unset($abstracts);
				
		/* --- Text  --- */
		foreach ($article->getGalleys() as $galley) {
			$articleFileName="";
			$galleyNode = ArticulusExportDom::generateGalleyDom($doc, $journal, $issue, $article, $galley, $articleFileName, $galleys);
			if ($galleyNode != ''){
				$galleyNode = preg_replace("/\n/","",$galleyNode);
				// $galleyNode = preg_replace('/^.+Ключевые слова:.+?\.\s/su','', $galleyNode);
				// $galleyNode = preg_replace('/список литературы.++/iuU','', $galleyNode);
				$textNode =& XMLCustomWriter::createChildWithText($doc, $root, "text",htmlspecialchars($galleyNode), false);
				XMLCustomWriter::setAttribute($textNode, 'lang', ArticulusExportDom::formatLocale($galley->getLocale()));
				unset($galleyNode);
				
			}
		}
		
		/* --- UDK  --- */
		$subjectClass=$article->getSubjectClass(null);
				
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $issue->getJournalId());
		
		if (is_array($subjectClass) || is_array($pubIdPlugins)){
			$codes =& XMLCustomWriter::createElement($doc, 'codes');
			$subjectClassNode =& XMLCustomWriter::createChildWithText($doc, $codes, 'udk', preg_replace("/УДК\s*/m", '', $subjectClass['ru_RU']), false);
			if ($subjectClassNode) {
				$isIndexingNecessary = true;
			}			
			
			ArticulusExportDom::generatePubId($doc, $codes, $article, $issue);
			XMLCustomWriter::appendChild($root, $codes);
			unset($codes);
		}
		
		/* --- Indexing --- */
		$indexingNode =& XMLCustomWriter::createElement($doc, 'keywords');
		$isIndexingNecessary = false;
		if (is_array($article->getSubject(null))) foreach ($article->getSubject(null) as $locale => $subject) {
			$kwdGroup =& XMLCustomWriter::createElement($doc, 'kwdGroup');
			foreach(explode(";",$subject) as $keyword){
				$subjectNode =& XMLCustomWriter::createChildWithText($doc, $kwdGroup, 'keyword', $keyword, false);
			}
			if ($subjectNode) {
				XMLCustomWriter::setAttribute($kwdGroup, 'lang', ArticulusExportDom::formatLocale($locale));
				$isIndexingNecessary = true;
			}
			unset($subjectNode);
			XMLCustomWriter::appendChild($indexingNode, $kwdGroup);
			unset($kwdGroup);
		}
		if ($isIndexingNecessary) XMLCustomWriter::appendChild($root, $indexingNode);

		/* --- References --- */
		$references =& XMLCustomWriter::createElement($doc, 'references');
		foreach(explode("\n",$article->getCitations(null)) as $reference){
			XMLCustomWriter::createChildWithText($doc, $references, 'reference', preg_replace("/^\d+.\s+/u",'',$reference), false);
		}
		XMLCustomWriter::appendChild($root, $references);
		unset($references);
		
		/* --- files --- */
		$files =& XMLCustomWriter::createElement($doc, 'files');
		$fullTextUrl =& XMLCustomWriter::createChildWithText($doc, $files, 'furl', Request::url(null, 'article', 'view', $article->getId())); //furl
		$fullTextUrl =& XMLCustomWriter::createChildWithText($doc, $files, 'file', $articleFileName); //filename
		XMLCustomWriter::appendChild($root, $files);
		unset($files);
		
		/* --- dates --- */
		$dates =& XMLCustomWriter::createElement($doc, 'dates'); 
		XMLCustomWriter::createChildWithText($doc, $dates, 'dateReceived', date('d.m.Y', strtotime($article->getDateSubmitted())), false);
		XMLCustomWriter::appendChild($root, $dates);
		unset($dates);
	
		return $root;
	}
	

	function &generateAuthorDom(&$doc, &$journal, &$issue, &$article, &$author, &$num) {
		$root =& XMLCustomWriter::createElement($doc, 'author');
		//if ($author->getPrimaryContact()) XMLCustomWriter::setAttribute($root, 'primary_contact', 'true');
		
		$locales = array_unique(array_merge(array_keys($author->getAffiliation(null)), array_keys($author->getBiography(null))));
		
		// foreach($locales as $locale){		
		foreach(array_keys($journal->getSupportedLocaleNames()) as $locale){
			$individInfo =& XMLCustomWriter::createElement($doc, 'individInfo');
			XMLCustomWriter::setAttribute($individInfo, 'lang', ArticulusExportDom::formatLocale($locale));
			
			XMLCustomWriter::setAttribute($root, 'num', str_pad($num, 3, "0", STR_PAD_LEFT));
			if ($locale == 'ru_RU'){
				XMLCustomWriter::createChildWithText($doc, $individInfo, 'surname', $author->getLastName());
				XMLCustomWriter::createChildWithText($doc, $individInfo, 'initials', $author->getFirstName()." ".$author->getMiddleName());
			}
			$temp = $author->getAffiliation($locale);
			if (!is_null($temp)) {
				$affiliationNode =& XMLCustomWriter::createChildWithText($doc, $individInfo, 'orgName', $temp, false);
				//if ($affiliationNode) XMLCustomWriter::setAttribute($affiliationNode, 'locale', $locale);
				unset($affiliationNode);
			}
			XMLCustomWriter::createChildWithText($doc, $individInfo, 'email', $author->getEmail());
			
			$temp = $author->getBiography($locale);
			if (!is_null(is_array($temp))) {
				$biographyNode =& XMLCustomWriter::createChildWithText($doc, $individInfo, 'otherInfo', strip_tags($temp), false);
				unset($biographyNode);
			}
			XMLCustomWriter::appendChild($root, $individInfo);
			unset($temp);
			unset($individInfo);
		}
		return $root;
	}

	function &generateGalleyDom(&$doc, &$journal, &$issue, &$article, &$galley, &$articleFileName, &$galleys) {
		// $isHtml = $galley->isHTMLGalley();

		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($article->getId());

		/* --- Galley file --- *//*
		if (!$galley->getRemoteURL()) {
			import('classes.custom.PdfToText');
			$articleFile =& $articleFileManager->getFile($galley->getFileId());
			$filePath = $articleFileManager->filesDir .  $articleFileManager->fileStageToPath($articleFile->getFileStage()) . '/' . $articleFile->getFileName();
			$pdf = new PdfToText($filePath);
			$articleFileName=$articleFile->getFileName();
			//$articleFile =& $articleFileDao->getArticleFile($galley->getFileId());
			if (!$articleFile) return $articleFile; // Stupidity check
		}

		return $pdf -> Text ;
		*/
		if (!$galley->getRemoteURL()) {
			$articleFile =& $articleFileManager->getFile($galley->getFileId());
			$articleFileName=$articleFile->getFileName();
			$filePath = $articleFileManager->filesDir .  $articleFileManager->fileStageToPath($articleFile->getFileStage()) . '/' . $articleFileName;
			$galleys[] = $articleFileName;
			$articleFileManager->copyFile($filePath, Config::getVar('files', 'files_dir') . '/temp/' . $articleFileName);
			if ($articleFileManager->parseFileExtension($articleFileName) == 'pdf'){
				$this->import('pdf2text');
				$pdf2text = new PDF2Text();
				$pdf2text->setFilename($filePath);
				$pdf2text->setUnicode(true);
				$pdf2text->decodePDF();
				// $galleyNode = iconv(mb_detect_encoding($text, mb_detect_order(), true), "UTF-8//IGNORE", $pdf2text->decodedtext);
				// $galleyNode = iconv('utf-8', 'utf-8//IGNORE', $pdf2text->decodedtext);
				$galleyNode = mb_convert_encoding($pdf2text->decodedtext, "UTF-8");
				$galleyNode = html_entity_decode($galleyNode, ENT_COMPAT | ENT_HTML401, 'UTF-8');
				// file_put_contents(Config::getVar('files', 'files_dir') . '/temp/source.txt', $galleyNode."\r\n", FILE_APPEND);
				// $galleys[] = 'source.txt';
			}
			if (!$articleFile)  return $articleFile; // Stupidity check
		}
		
		return (isset($pdf2text) ? $galleyNode : '');
	}

	function formatDate($date) {
		if ($date == '') return null;
		return date('Y-m-d', strtotime($date));
	}

	/**
	 * Add ID-nodes to the given node.
	 * @param $doc DOMDocument
	 * @param $node DOMNode
	 * @param $pubObject object
	 * @param $issue Issue
	 */
	function generatePubId(&$doc, &$node, &$pubObject, &$issue) {
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $issue->getJournalId());
		if (is_array($pubIdPlugins)) foreach ($pubIdPlugins as $pubIdPlugin) {
			if ($issue->getPublished()) {
				$pubId = $pubIdPlugin->getPubId($pubObject);
			} else {
				$pubId = $pubIdPlugin->getPubId($pubObject, true);
			}
			if ($pubId) {
				$pubIdType = $pubIdPlugin->getPubIdType();
				$idNode =& XMLCustomWriter::createChildWithText($doc, $node, ($pubIdType == 'doi' ? 'doi' : 'otherCode'), $pubId);
				if ($pubIdType != 'doi') XMLCustomWriter::setAttribute($idNode, 'type', $pubIdType);
			}
		}
	}
	
	function formatLocale(&$locale){
		switch($locale){
			case "en_US":return "ENG";
			case "ru_RU":return "RUS";
			default: return "ANY";
		}
	}
	
	function StripBadUTF8($str) { // (C) SiMM, based on ru.wikipedia.org/wiki/Unicode 
		$ret = ''; 
		for ($i = 0;$i < strlen($str);) { 
			$tmp = $str{$i++}; 
			$ch = ord($tmp); 
			if ($ch > 0x7F) { 
				if ($ch < 0xC0) continue; 
				elseif ($ch < 0xE0) $di = 1; 
				elseif ($ch < 0xF0) $di = 2; 
				elseif ($ch < 0xF8) $di = 3; 
				elseif ($ch < 0xFC) $di = 4; 
				elseif ($ch < 0xFE) $di = 5; 
				else continue; 

				for ($j = 0;$j < $di;$j++) { 
					$tmp .= $ch = $str{$i + $j}; 
					$ch = ord($ch); 
					if ($ch < 0x80 || $ch > 0xBF) continue 2; 
				} 
				$i += $di; 
			} 
			$ret .= $tmp; 
		} 
		return $ret; 
		} 
}

?>
