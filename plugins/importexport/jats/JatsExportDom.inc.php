<?php

/**
 * @file plugins/importexport/jats/JatsExportDom.inc.php
 *
 * Copyright (c) 2017 Kazan Federal University
 * Copyright (c) 2017 Shamil K.
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JatsExportDom
 * @ingroup plugins_importexport_jats
 *
 * @brief Articulus import/export plugin DOM functions for export
 */

import('lib.pkp.classes.xml.XMLCustomWriter');

class JatsExportDom {
	function &generateIssueDom(&$doc, &$journal, &$issue) {
		$root =& XMLCustomWriter::createElement($doc, 'journal');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance", false);
		XMLCustomWriter::setAttribute($root, 'xsi:noNamespaceSchemaLocation', "JournalArticulus.xsd", false);
		
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
		XMLCustomWriter::createChildWithText($doc, $root, 'issn', $journal->getSetting('onlineIssn'), false);
		
		/* --- journalInfo --- */
		$journalInfoNode =& XMLCustomWriter::createElement($doc, 'journalInfo');
		XMLCustomWriter::setAttribute($journalInfoNode, 'lang', "RUS", false);
		XMLCustomWriter::createChildWithText($doc, $journalInfoNode, 'title', $journal->getLocalizedTitle("ru_RU"), false);
		XMLCustomWriter::appendChild($root, $journalInfoNode);
		unset($journalInfoNode);
		
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
			$sectionNode =& JatsExportDom::generateSectionDom($doc, $journal, $issue, $section);
			XMLCustomWriter::appendChild($issueNode, $sectionNode);
			unset($sectionNode);
		}

		XMLCustomWriter::appendChild($root, $issueNode);
		unset($issueNode);
		
		return $root;
	}

	function &generateSectionDom(&$doc, &$journal, &$issue, &$section) {
		$root =& XMLCustomWriter::createElement($doc, 'articles');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		foreach ($publishedArticleDao->getPublishedArticlesBySectionId($section->getId(), $issue->getId()) as $article) {
			$articleNode =& JatsExportDom::generateArticleDom($doc, $journal, $issue, $section, $article);
			XMLCustomWriter::appendChild($root, $articleNode);
			unset($articleNode);
		}

		return $root;
	}

	function &generateArticleDom(&$doc, &$journal, &$issue, &$section, &$article) {
		$root =& XMLCustomWriter::createElement($doc, 'article');
		if ($root) {
			XMLCustomWriter::setAttribute($root, 'article-type', 'research-article');
			XMLCustomWriter::setAttribute($root, 'dtd-version', '1.0');
			XMLCustomWriter::setAttribute($root, 'xml:lang', 'en');
			XMLCustomWriter::setAttribute($root, 'xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
			XMLCustomWriter::setAttribute($root, 'xmlns:xlink', 'http://www.w3.org/1999/xlink');
			XMLCustomWriter::setAttribute($root, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		}

		JatsExportDom::generatePubId($doc, $root, $article, $issue);
		/* --- pages --- */
		XMLCustomWriter::createChildWithText($doc, $root, 'pages', $article->getPages(), false);
		
		/* --- Authors --- */

		$authors =& XMLCustomWriter::createElement($doc, 'authors');
		foreach ($article->getAuthors() as $author) {
			$num++;
			$authorNode =& JatsExportDom::generateAuthorDom($doc, $journal, $issue, $article, $author, $num);
			XMLCustomWriter::appendChild($authors, $authorNode);
			unset($authorNode);
		}
		XMLCustomWriter::appendChild($root, $authors);
		unset($authors);
		
		/* --- Titles and Abstracts --- */
		$artTitles =& XMLCustomWriter::createElement($doc, 'artTitles');
		if (is_array($article->getTitle(null))) foreach ($article->getTitle(null) as $locale => $title) {
			$titleNode =& XMLCustomWriter::createChildWithText($doc, $artTitles, 'artTitle', $title, false);
			if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'lang', JatsExportDom::formatLocale($locale));
			unset($titleNode);
		}
		XMLCustomWriter::appendChild($root, $artTitles);
		unset($artTitles);

		$abstracts =& XMLCustomWriter::createElement($doc, 'abstracts');
		if (is_array($article->getAbstract(null))) foreach ($article->getAbstract(null) as $locale => $abstract) {
			$abstractNode =& XMLCustomWriter::createChildWithText($doc, $abstracts, 'abstract', strip_tags($abstract), false);
			if ($abstractNode) XMLCustomWriter::setAttribute($abstractNode, 'lang', JatsExportDom::formatLocale($locale));
			unset($abstractNode);
		}
		XMLCustomWriter::appendChild($root, $abstracts);
		unset($abstracts);
				
		/* --- Text  --- */
		foreach ($article->getGalleys() as $galley) {
			$articleFileName="";
			$galleyNode = JatsExportDom::generateGalleyDom($doc, $journal, $issue, $article, $galley, $articleFileName);
			$galleyNode = iconv('utf-8', 'utf-8//IGNORE', $galleyNode);
			$galleyNode = html_entity_decode($galleyNode, ENT_QUOTES , 'UTF-8');
			$textNode =& XMLCustomWriter::createChildWithText($doc, $root, "text",htmlspecialchars($galleyNode), false);
			XMLCustomWriter::setAttribute($textNode, 'lang', JatsExportDom::formatLocale($galley->getLocale()));
			unset($galleyNode);
		}
		
		/* --- UDK  --- */
		$subjectClass=$article->getSubjectClass(null);
		if (is_array($subjectClass)){
			$codes =& XMLCustomWriter::createElement($doc, 'codes');
			$subjectClassNode =& XMLCustomWriter::createChildWithText($doc, $codes, 'udk', preg_replace("/УДК\s*/m", '', $subjectClass['ru_RU']), false);
			if ($subjectClassNode) {
				$isIndexingNecessary = true;
			}			
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
				XMLCustomWriter::setAttribute($kwdGroup, 'lang', JatsExportDom::formatLocale($locale));
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
	
		return $root;
	}
	

	function &generateAuthorDom(&$doc, &$journal, &$issue, &$article, &$author, &$num) {
		$root =& XMLCustomWriter::createElement($doc, 'author');
		//if ($author->getPrimaryContact()) XMLCustomWriter::setAttribute($root, 'primary_contact', 'true');
		
		$individInfo =& XMLCustomWriter::createElement($doc, 'individInfo');
		XMLCustomWriter::setAttribute($individInfo, 'lang', 'RUS');
		
		XMLCustomWriter::setAttribute($root, 'num', str_pad($num, 3, "0", STR_PAD_LEFT));
		
		XMLCustomWriter::createChildWithText($doc, $individInfo, 'surname', $author->getLastName());
		XMLCustomWriter::createChildWithText($doc, $individInfo, 'initials', $author->getFirstName()." ".$author->getMiddleName());

		$affiliations = $author->getAffiliation(null);
		if (is_array($affiliations)) {
			$affiliationNode =& XMLCustomWriter::createChildWithText($doc, $individInfo, 'orgName', $affiliations['ru_RU'], false);
			//if ($affiliationNode) XMLCustomWriter::setAttribute($affiliationNode, 'locale', $locale);
			unset($affiliationNode);
		}
		XMLCustomWriter::createChildWithText($doc, $individInfo, 'email', $author->getEmail());
		
		$affiliations = $author->getBiography(null);
		if (is_array($affiliations)) {
			$biographyNode =& XMLCustomWriter::createChildWithText($doc, $individInfo, 'otherInfo', strip_tags($affiliations['ru_RU']), false);
			unset($biographyNode);
		}
		XMLCustomWriter::appendChild($root, $individInfo);
		unset($individInfo);

		return $root;
	}

	function &generateGalleyDom(&$doc, &$journal, &$issue, &$article, &$galley, &$articleFileName) {
		$isHtml = $galley->isHTMLGalley();

		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($article->getId());
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		/* --- Galley file --- */
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
		
		/*if (!$galley->getRemoteURL()) {
			import('classes.custom.pdf2text');
			$pdf2text = new PDF2Text();
			$articleFile =& $articleFileManager->getFile($galley->getFileId());
			$filePath = $articleFileManager->filesDir .  $articleFileManager->fileStageToPath($articleFile->getFileStage()) . '/' . $articleFile->getFileName();
			$pdf2text->setFilename($filePath);
			$pdf2text->decodePDF();
			$articleFileName=$articleFile->getFileName();
			//$articleFile =& $articleFileDao->getArticleFile($galley->getFileId());
			if (!$articleFile) return $articleFile; // Stupidity check
		}

		return $pdf2text->decodedtext;*/
	}

	function &generateSuppFileDom(&$doc, &$journal, &$issue, &$article, &$suppFile) {
		$root =& XMLCustomWriter::createElement($doc, 'supplemental_file');

		JatsExportDom::generatePubId($doc, $root, $suppFile, $issue);

		// FIXME: These should be constants!
		switch ($suppFile->getType()) {
			case __('author.submit.suppFile.researchInstrument'):
				$suppFileType = 'research_instrument';
				break;
			case __('author.submit.suppFile.researchMaterials'):
				$suppFileType = 'research_materials';
				break;
			case __('author.submit.suppFile.researchResults'):
				$suppFileType = 'research_results';
				break;
			case __('author.submit.suppFile.transcripts'):
				$suppFileType = 'transcripts';
				break;
			case __('author.submit.suppFile.dataAnalysis'):
				$suppFileType = 'data_analysis';
				break;
			case __('author.submit.suppFile.dataSet'):
				$suppFileType = 'data_set';
				break;
			case __('author.submit.suppFile.sourceText'):
				$suppFileType = 'source_text';
				break;
			default:
				$suppFileType = 'other';
				break;
		}

		XMLCustomWriter::setAttribute($root, 'type', $suppFileType);
		XMLCustomWriter::setAttribute($root, 'public_id', $suppFile->getPubId('publisher-id'), false);
		XMLCustomWriter::setAttribute($root, 'language', $suppFile->getLanguage(), false);
		XMLCustomWriter::setAttribute($root, 'show_reviewers', $suppFile->getShowReviewers()?'true':'false');

		if (is_array($suppFile->getTitle(null))) foreach ($suppFile->getTitle(null) as $locale => $title) {
			$titleNode =& XMLCustomWriter::createChildWithText($doc, $root, 'title', $title, false);
			if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'locale', $locale);
			unset($titleNode);
		}
		if (is_array($suppFile->getCreator(null))) foreach ($suppFile->getCreator(null) as $locale => $creator) {
			$creatorNode =& XMLCustomWriter::createChildWithText($doc, $root, 'creator', $creator, false);
			if ($creatorNode) XMLCustomWriter::setAttribute($creatorNode, 'locale', $locale);
			unset($creatorNode);
		}
		if (is_array($suppFile->getSubject(null))) foreach ($suppFile->getSubject(null) as $locale => $subject) {
			$subjectNode =& XMLCustomWriter::createChildWithText($doc, $root, 'subject', $subject, false);
			if ($subjectNode) XMLCustomWriter::setAttribute($subjectNode, 'locale', $locale);
			unset($subjectNode);
		}
		if ($suppFileType == 'other') {
			if (is_array($suppFile->getTypeOther(null))) foreach ($suppFile->getTypeOther(null) as $locale => $typeOther) {
				$typeOtherNode =& XMLCustomWriter::createChildWithText($doc, $root, 'type_other', $typeOther, false);
				if ($typeOtherNode) XMLCustomWriter::setAttribute($typeOtherNode, 'locale', $locale);
				unset($typeOtherNode);
			}
		}
		if (is_array($suppFile->getDescription(null))) foreach ($suppFile->getDescription(null) as $locale => $description) {
			$descriptionNode =& XMLCustomWriter::createChildWithText($doc, $root, 'description', $description, false);
			if ($descriptionNode) XMLCustomWriter::setAttribute($descriptionNode, 'locale', $locale);
			unset($descriptionNode);
		}
		if (is_array($suppFile->getPublisher(null))) foreach ($suppFile->getPublisher(null) as $locale => $publisher) {
			$publisherNode =& XMLCustomWriter::createChildWithText($doc, $root, 'publisher', $publisher, false);
			if ($publisherNode) XMLCustomWriter::setAttribute($publisherNode, 'locale', $locale);
			unset($publisherNode);
		}
		if (is_array($suppFile->getSponsor(null))) foreach ($suppFile->getSponsor(null) as $locale => $sponsor) {
			$sponsorNode =& XMLCustomWriter::createChildWithText($doc, $root, 'sponsor', $sponsor, false);
			if ($sponsorNode) XMLCustomWriter::setAttribute($sponsorNode, 'locale', $locale);
			unset($sponsorNode);
		}
		XMLCustomWriter::createChildWithText($doc, $root, 'date_created', JatsExportDom::formatDate($suppFile->getDateCreated()), false);
		if (is_array($suppFile->getSource(null))) foreach ($suppFile->getSource(null) as $locale => $source) {
			$sourceNode =& XMLCustomWriter::createChildWithText($doc, $root, 'source', $source, false);
			if ($sourceNode) XMLCustomWriter::setAttribute($sourceNode, 'locale', $locale);
			unset($sourceNode);
		}

		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($article->getId());
		$fileNode =& XMLCustomWriter::createElement($doc, 'file');
		XMLCustomWriter::appendChild($root, $fileNode);
		if ($suppFile->getRemoteURL()) {
			$remoteNode =& XMLCustomWriter::createElement($doc, 'remote');
			XMLCustomWriter::appendChild($fileNode, $remoteNode);
			XMLCustomWriter::setAttribute($remoteNode, 'src', $suppFile->getRemoteURL());
		} else {
			$embedNode =& XMLCustomWriter::createChildWithText($doc, $fileNode, 'embed', base64_encode($articleFileManager->readFile($suppFile->getFileId())));
			XMLCustomWriter::setAttribute($embedNode, 'filename', $suppFile->getOriginalFileName());
			XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
			XMLCustomWriter::setAttribute($embedNode, 'mime_type', $suppFile->getFileType());
		}
		return $root;
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
				$idNode =& XMLCustomWriter::createChildWithText($doc, $node, 'id', $pubId);
				XMLCustomWriter::setAttribute($idNode, 'type', $pubIdType);
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
