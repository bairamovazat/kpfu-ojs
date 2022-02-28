<?php

/**
 * @file pages/sectionEditor/RevRecParserHandler.inc.php
 *
 * Copyright (c) 2017-2019 Kazan Federal University
 * Copyright (c) 2017-2019 Shamil K.
 * @class RevRecParserHandler
 * @ingroup plugins_generic_reviewersRecommender
 *
 * @brief Handle requests for additional reviewers recommender functions.
 */

import('pages.sectionEditor.SubmissionEditHandler');

class RevRecParserHandler extends SubmissionEditHandler {

	/** Plugin associated with the request */
	var $plugin;
	
	/**
	 * Constructor
	 **/
	function RevRecParserHandler($parentPluginName) {
		parent::Handler();

		$plugin =& PluginRegistry::getPlugin('generic', $parentPluginName);
		$this->plugin =& $plugin;		
	}
	
	/**
	 * Ensure that we have a journal and the plugin is enabled.
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		if (!isset($journal)) return false;
		$browsePlugin =& PluginRegistry::getPlugin('generic', REVREC_PLUGIN_NAME);
		if (!isset($browsePlugin)) return false;
		if (!$browsePlugin->getEnabled()) return false;
		return parent::authorize($request, $args, $roleAssignments);
	}

	function index(&$request) {
		header("Content-Type: text/plain");
		// $plugin =& $this->plugin;
		// $templateMgr =& TemplateManager::getManager();
		// $templateMgr->display($plugin->getTemplatePath() . 'index.tpl');
				
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$journal =& Request::getJournal();
		$users =& $roleDao->getUsersByRoleId(ROLE_ID_REVIEWER, $journal->getId())->toArray();
		
		$interestDao =& DAORegistry::getDAO('RevInterestDAO');
		
		$n = count($users);
		// var_dump($users);
		// $idfs = array();
		foreach ($users as $user) {
			if ($msn = $user->getData('mathscinet')) {
				if (strpos($msn, 'MRAuthorID') !== false){
					$r_cur = '&r=1';
					$msn = preg_replace('/MRAuthorID\/(\d+)/', 'search/publications.html?pg1=INDI&s1=$1&sort=Newest&vfpref=html' .$r_cur. '&extend=1', $msn);
				}
				echo "[{$user->getId()}]:\n";
				$k = 0;
				$result = array();
				do {
					if ($k > 0) {
						$r = $r_cur;
						$r_cur = '&r=' . $k . '01';
						$msn = str_replace($r, $r_cur, $msn);
					}
					echo "$msn\n";
					$response = $this->_curlGet($msn);
					if ($response['status'] == 200){
						$body = $response['result'];
						$doc = new DOMDocument();
						$doc->encoding = 'utf-8';
						libxml_use_internal_errors(true);

						$doc->loadHTML($body);
						$xpath = new DOMXPath($doc);
						$xml = simplexml_import_dom($doc);
						$len = $xml->body->xpath("//div[@class='matches']");
						$len = (int) ($len[0] / 100);
						$codes = $xml->body->xpath("//a[contains(@href, 'mscdoc')]");
						foreach($codes as $code){
							preg_match_all('/\d{2}[A-Z-]\d{2}/', (string) $code, $matches);
							echo (string) $code .'; ';
							$msc_num = count($matches[0]); 
							if ($msc_num > 0){
								isset ($result[$matches[0][0]]) ? $result[$matches[0][0]] += 2 / 3 : $result[$matches[0][0]] = 2 / 3;
								// $idfs[$matches[0][0]] = 0;
								if ($msc_num > 1) {
									for($i = 1; $i < $msc_num; $i++){
										isset($result[$matches[0][$i]]) ? $result[$matches[0][$i]] += 1 / (3 * ($msc_num - 1)) : $result[$matches[0][$i]] = 1 / (3 * ($msc_num - 1)); //(3 * ($msc_num - 1));
										// $idfs[$matches[0][$i]] = 0;
									}
								}
							}
							unset($matches);
						}
						echo PHP_EOL;
						// arsort($result);
					}else{
						echo 'Status: ' . $response['status'] . PHP_EOL;
						echo $response['error'], PHP_EOL;
					}
					$k++;
				} while($k < $len + 1);
				print_r($result);	
				if (count($result) > 0) $interestDao->setUserInterests($result, $user->getId());
				unset($result);
			}
        }
		
		// var_dump($result);
		
		
		// idf
		// foreach ($idfs as $key => $value){
			// foreach ($result as $codes){
				// if (in_array($key, $codes['primary']) || in_array($key, $codes['secondary'])){
					// $idfs[$key]++;
				// }
			// }
		// }
		
		// print_r($idfs);
		//tf
		// foreach ($result as $user_id => $codes){
			// $primary = &array_count_values($codes['primary']);
			// $secondary = &array_count_values($codes['secondary']);
			// $dt = count($t = array_merge($primary, $secondary));
			// foreach($t as $key => $value){
				// $t[$key] = $primary[$key] * 2 / 3 + $secondary[$key] * 1 / 3;
				// $t[$key] = (($primary[$key] * 0.66 + $secondary[$key] * 0.34) / $dt) * log ($n / $idfs[$key]);
				// $t[$key] = $primary[$key] + $secondary[$key];
				
				
			// }
			// $result[$user_id] = $t;
				
		// }

		// print_r($result);
		
	
		// foreach ($result as $user_id => $codes){
			// $interestDao->setUserInterests($codes, $user_id);
		// }
		

        $users->Close();
        unset($users);
		
		
	}
	
	
	function _curlGet($url,$headers=array()) {
			
		$curl = curl_init(); 
		
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_URL => $url
		));
		if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
			curl_setopt($curl, CURLOPT_PROXY, $httpProxyHost);
			curl_setopt($curl, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
			if ($username = Config::getVar('proxy', 'username')) {
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
			}
		}
		
		$httpResult = curl_exec($curl);
		$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$httpError = curl_error($curl);
		curl_close ($curl);
				
		return array(
			'status' => $httpStatus,
			'result' => $httpResult,
			'error'  => $httpError
		);
	}
	
	//not used
	function updateInterests($args = array(), &$request) {
		
		$journal =& $request->getJournal();
		$userId = $request->getUserVar('userId');

		if (!empty($userId) && !Validation::canAdminister($journal->getId(), $userId)) {
			// We don't have administrative rights
			// over this user. Display an error.
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}
		
		
	}
	
	
	/**
	 * Select a reviewer with recommedation score.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function selectReviewer($args = array(), &$request) {
		$articleId = (int) array_shift($args);
		$reviewerId = (int) array_shift($args);
		
		$revrecPlugin =& PluginRegistry::getPlugin('generic', REVREC_PLUGIN_NAME);
		
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$journal =& $request->getJournal();
		$submission =& $this->submission;
		
		$msc = $submission->getLocalizedSubjectClass();

		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'recommendationScore';
		$sortDirection = $request->getUserVar('sortDirection');
		$sortDirection = (isset($sortDirection) && ($sortDirection == SORT_DIRECTION_ASC || $sortDirection == SORT_DIRECTION_DESC)) ? $sortDirection : SORT_DIRECTION_ASC;

		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

		if ($reviewerId) {
			// Assign reviewer to article
			SectionEditorAction::addReviewer($submission, $reviewerId, null, $request);
			$request->redirect(null, null, 'submissionReview', $articleId);

		} else {
			$this->setupTemplate(true, $articleId, 'review');

			$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = $request->getUserVar('search');
			$searchInitial = $request->getUserVar('searchInitial');
			if (!empty($search)) {
				$searchType = $request->getUserVar('searchField');
				$searchMatch = $request->getUserVar('searchMatch');

			} elseif (!empty($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$rangeInfo =& $this->getRangeInfo('reviewers');
			$reviewers = $sectionEditorSubmissionDao->getReviewersForArticle($journal->getId(), $articleId, $submission->getCurrentRound(), $searchType, $search, $searchMatch, $rangeInfo, ($sort === 'recommendationScore' ? 'reviewerName' : $sort), $sortDirection); /* @var $reviewers DAOResultFactory */

			$journal = $request->getJournal();
			$reviewAssignmentDao =& DAORegistry::getDAO('RevRecReviewAssignmentDAO');
			

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));
			$reviewerStatistics = $sectionEditorSubmissionDao->getReviewerStatistics($journal->getId());
			
			$score = $reviewAssignmentDao->getRecomendationScore($journal->getId(), $msc);
			$interestDao =& DAORegistry::getDAO('RevInterestDAO');
			
			foreach($reviewers = $reviewers->toAssociativeArray() as $userId => $reviewer){
				$reviewer->score = $score[$userId];
				$reviewer->interest = $interestDao->getInterestFromMsc($userId);
			}
			if ($sort === 'recommendationScore')
				uasort($reviewers, function (float $a, float $b) {
						if ($a->score == $b->score) {
							return 0;
						}
						return ($a->score > $b->score) ? -1 : 1;
				});
				
			$templateMgr->assign_by_ref('reviewers', $reviewers);
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewerStatistics', $reviewerStatistics);
			$templateMgr->assign('fieldOptions', array(
				USER_FIELD_INTERESTS => 'user.interests',
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('completedReviewCounts', $reviewAssignmentDao->getCompletedReviewCounts($journal->getId()));
			$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('averageQualityRatings', $reviewAssignmentDao->getAverageQualityRatings($journal->getId()));

			$templateMgr->assign('helpTopicId', 'journal.roles.reviewer');
			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
			$templateMgr->assign('reviewerDatabaseLinks', $journal->getSetting('reviewerDatabaseLinks'));
			$templateMgr->assign('sort', $sort);
			$templateMgr->assign('msc', $msc);
			$templateMgr->assign('sortDirection', $sortDirection);
			$templateMgr->display($revrecPlugin->getTemplatePath() . 'selectReviewer.tpl');
		}
	}

}

?>
