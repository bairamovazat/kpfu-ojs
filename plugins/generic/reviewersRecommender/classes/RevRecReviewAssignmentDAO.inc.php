<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignmentDAO.inc.php
 *
 * Copyright (c) 2017-2019 Kazan Federal University
 * Copyright (c) 2017-2019 Shamil K.
 *
 * @class ReviewAssignmentDAO
 * @ingroup plugins_generic_reviewersRecommender
 *
 * @brief reviewersRecommender plugin class for DAO relating reviewers to articles with recomendation score.
 */
import('classes.submission.reviewAssignment.ReviewAssignment');
import('classes.submission.reviewAssignment.ReviewAssignmentDAO');

class RevRecReviewAssignmentDAO extends ReviewAssignmentDAO {

    // /**
     // * Get reviewer recomendation score.
     // * @return array
     // * Shamil K.
     // */
    // // function getRecomendationScore_old($journalId, $msc) {
        // // $recomendationScore = Array();
        // // $result = & $this->retrieve('
			// // SELECT 
				// // DISTINCT u.user_id, 
				// // GROUP_CONCAT(
					// // DISTINCT cves.setting_value SEPARATOR ", "
				// // ) as interests,
				// // GROUP_CONCAT(
					// // ui.tf SEPARATOR ", "
				// // ) as tf, 
				// // CASE WHEN tt.incomplete IS NULL THEN 0 ELSE tt.incomplete END as incomplete
			// // FROM 
				// // users u 
				// // LEFT JOIN roles r ON (r.user_id = u.user_id) 
				// // LEFT JOIN user_interests ui ON (ui.user_id = u.user_id) 
				// // LEFT JOIN controlled_vocab_entry_settings cves ON (
					// // cves.controlled_vocab_entry_id = ui.controlled_vocab_entry_id
				// // )
				// // LEFT JOIN (SELECT r.reviewer_id, COUNT(*) AS incomplete
						// // FROM    review_assignments r,
							// // articles a
						// // WHERE   r.submission_id = a.article_id AND
							// // r.date_notified IS NOT NULL AND
							// // r.date_completed IS NULL AND
							// // r.cancelled = 0 AND
							// // r.declined = 0 AND
							// // r.date_completed IS NULL AND r.declined <> 1 AND (r.cancelled = 0 OR r.cancelled IS NULL) AND a.status = ' . STATUS_QUEUED . ' AND
							// // a.journal_id = ?
						// // GROUP BY r.reviewer_id) as tt ON tt.reviewer_id = u.user_id
			// // WHERE 
				// // u.user_id = r.user_id 
				// // AND r.journal_id = ? 
				// // AND r.role_id = ? 
			// // GROUP BY 
				// // u.user_id, 
				// // u.last_name',
                        // // array((int) $journalId, (int) $journalId, ROLE_ID_REVIEWER)
        // // );
        // // $editorSubmissionDao = & DAORegistry::getDAO('EditorSubmissionDAO');
        // // $submissionsCount = & $editorSubmissionDao->getEditorSubmissionsCount((int) $journalId);
        // // $reviewers = array();
        // // while (!$result->EOF) {
            // // $row = $result->GetRowAssoc(false);

            // // $workloadCoeff[$row['user_id']] = 1 - ($row['incomplete'] / $submissionsCount[1]);
			// // if ($row['user_id'] == 675)print_r($row['tf']);
            // // $reviewers[$row['user_id']] = array_combine(preg_split('/[\s;,]+/', $row['interests'], -1, PREG_SPLIT_NO_EMPTY), preg_split('/[\s;,]+/', $row['tf'], -1, PREG_SPLIT_NO_EMPTY)) ;
            // // $result->MoveNext();
        // // }

        // // $result->Close();
        // // unset($result);
		// // $result = &$this->getTfidfScore($msc, $reviewers);
		// // if($workloadCoeff)
			// // foreach($workloadCoeff as $key => $value){
				 // // $result[$key] *= $value;
				
			// // }
		// // return $result;

        // return $recomendationScore;
    // // }
	
	
    /**
     * Get reviewer recomendation score.
     * @return array
     * Shamil K.
     */
    function getRecomendationScore($journalId, $msc) {
        preg_match_all("/\d{2}[-A-Z]\d{2}/", $msc, $msc);
		$mscs = array_unique(array_map(function($value) { return substr($value, 0, -3); }, $msc[0]));
		$like = implode(' OR ', array_map(function($value) { return "cves.setting_value LIKE '$value%'"; }, $mscs));
		unset($mscs);
        $recomendationScore = Array();
        $result = & $this->retrieve('
			SELECT 
				u.user_id, 
				cves.setting_value as interests,
				ui.tf as tf, 
				CASE WHEN tt.incomplete IS NULL THEN 0 ELSE tt.incomplete END as incomplete
			FROM 
				users u 
				LEFT JOIN roles r ON (r.user_id = u.user_id) 
				LEFT JOIN user_interests ui ON (ui.user_id = u.user_id) 
				LEFT JOIN controlled_vocab_entry_settings cves ON (
					cves.controlled_vocab_entry_id = ui.controlled_vocab_entry_id
				)
				LEFT JOIN (SELECT r.reviewer_id, COUNT(*) AS incomplete
						FROM    review_assignments r,
							articles a
						WHERE   r.submission_id = a.article_id AND
							r.date_notified IS NOT NULL AND
							r.date_completed IS NULL AND
							r.cancelled = 0 AND
							r.declined = 0 AND
							r.date_completed IS NULL AND r.declined <> 1 AND (r.cancelled = 0 OR r.cancelled IS NULL) AND a.status = ' . STATUS_QUEUED . ' AND
							a.journal_id = ?
						GROUP BY r.reviewer_id) as tt ON tt.reviewer_id = u.user_id
			WHERE 
				u.user_id = r.user_id 
				AND r.journal_id = ? 
				AND r.role_id = ?
				AND ('.$like.')',
                        array((int) $journalId, (int) $journalId, ROLE_ID_REVIEWER)
        );
        $editorSubmissionDao = & DAORegistry::getDAO('EditorSubmissionDAO');
        $submissionsCount = & $editorSubmissionDao->getEditorSubmissionsCount((int) $journalId);
        $reviewers = array();
        while (!$result->EOF) {
            $row = $result->GetRowAssoc(false);

            $workloadCoeff[$row['user_id']] = 1 - ($row['incomplete'] / $submissionsCount[1]);
			// if ($row['user_id'] == 675) {echo "{$row['interests']} => {$row['tf']}\n";}
            // $reviewers[$row['user_id']] = array_combine(preg_split('/[\s;,]+/', $row['interests'], -1, PREG_SPLIT_NO_EMPTY), preg_split('/[\s;,]+/', $row['tf'], -1, PREG_SPLIT_NO_EMPTY)) ;
            $reviewers[$row['user_id']][$row['interests']] = $row['tf'] ;
            $result->MoveNext();
        }
        $result->Close();
        unset($result);
		$result = &$this->getTfidfScore($msc[0], $reviewers);
		if($workloadCoeff)
			foreach($workloadCoeff as $key => $value){
				 $result[$key] *= $value;
				
			}
		return $result;

        // return $recomendationScore;
    }
	
    function &getTfidfScore($mscs, &$reviewers) {
        $normalization = false;
        $preffer_primary = true;
		$three_group = true;
		$echo = false;
		// $echo = true;
		// if($echo) print_r($reviewers);
        $n = count($reviewers);
        if($echo) echo "$n\n";
        // preg_match_all("/\d{2}[-A-Z]\d{2}/", $mscs, $mscs);
        $groups = array(null, null, null);
        $idfs = array();
        $result = array();
        $k_1 = 2;
        $b = 0.75;
        $avgdl = 0;
		$max_ndt = 0;
        foreach ($reviewers as $user_id => $interests) {
            $avgdl += count($interests);
            if ($max_ndt < array_sum($interests))
                $max_ndt = array_sum($interests);
        }
        $avgdl /= count($reviewers);
		if($echo) echo "max_ndt = $max_ndt\n";
        //idf
        foreach ($mscs as $k => $msc) {
            foreach ($groups as $i => $group) {
				// if ($i == 1 && $three_group) continue;
                $df = 0;
                $group = substr($msc, 0, $i == 0 ? 10 : -$i - 1);
                if($echo) echo "msc_$k = $msc; group = $group;\nf_$i = ";
				$f_q_array = preg_grep("/" . $group . ($i == 0 ? "" : '\w{' . $i . '}') . "/", $mscs);
				$f_q = count($f_q_array);
				if ($max_f_q[$group] < $f_q) $max_f_q[$group] = $f_q;
                foreach ($reviewers as $user_id => $interests) {
                    $df_array = preg_grep("/" . $group . ($i == 0 ? "" : '\w{' . $i . '}') . "/", array_keys($interests));
                    $f = count($df_array);


                    if ($f > 0) {
                        $df++;
                    }
                    if ($df)
                        if($echo) echo "$f ";
                }
                $idf = $df > 0 ? log($n / $df) : 0;
                // $idf = $df > 0 ? log(($n - $df) / $df) : 0;
                // $idf = $df > 0 ? log(($n - $df + 0.5)/ ($df + 0.5)) : 0;
                // $idf = log($n / (1 + $df));
                if ($df) {
                    if($echo) echo "(df = $df)\n";
                    if($echo) echo "idf_$i = log (\$n/\$df) = log ($n/$df) = $idf\n";
                }
                // $idfs[$k]['gr' . $i] = $idf;
                $idfs[$i][$group] = $idf;
            }
        }
        if($echo) echo "\n";
        if($echo) print_r($idfs);
        if($echo) print_r($max_f_q);
        if($echo) echo "\n\ntf block\n\n";

        //tf and score
        foreach ($reviewers as $user_id => $interests) {
            if (!$interests) continue;
            if($echo) echo "id = $user_id\n";
            foreach ($mscs as $k => $msc) {
				if($echo) echo "\tmsc = $msc;\n";
				$exclude = array();
				$max_f = 0;
                foreach ($groups as $i => $group) {
				// if ($i == 1 && $three_group) continue;
					$f = $tf = 0;
                $group = substr($msc, 0, $i == 0 ? 10 : -$i - 1);
                    if($echo) echo "\t\tgr = $group;\n";
					$ndt = array_sum($interests);
                    $df_array = preg_grep("/" . $group . ($i == 0 ? "" : '\w{' . $i . '}') . "/", array_keys($interests));
                    $f_q_array = preg_grep("/" . $group . ($i == 0 ? "" : '\w{' . $i . '}') . "/", $mscs);
					// print_r($df_array);
					
					//считаем коэф. для тф, а также удаляем считанные коды
					foreach($df_array as $key => $val){
						if(in_array($val, $exclude)){
							unset($df_array[$key]);
						}else{
							$f += $interests[$val];
							$exclude[] = $val;
						}
					}
					// print_r($df_array);
					$f_q = count($f_q_array);
                    // if ($f_q > 0){
						// // $tf = $f;
						// // $tf = $f == 0 ? 0 : log($f) + 1;
                        // // echo "(tf = $tf); \n";
                        // // echo "tf_idf = ".$tf * $idfs[$group]."\n";
						// // $tf = $f / $ndt;
                        // // echo "(tf = f / ndt = $f / $ndt = $tf); \n";
						// // $tf = 0.5 + (0.5 * $f / $max_ndt);
                        // // echo "(tf = 0.5 + (0.5 * f / max_ndt) = 0.5 + (0.5 * $f / $max_ndt) = $tf); \n";
						// // $result[$user_id][$msc]['gr' . $i]['f'] = $f;
					// }
                        if($echo) echo "\t\t\t[$user_id] => f = $f; f_q = $f_q;\n";
						
					// reviewers tf
					// $tf = $f / $ndt;
					// $tf = $f == 0 ? 0 : (1 + log($f));
					$tf = $f > 0 ? 0.5 + (0.5 * $f / $max_ndt) : 0;
					if($echo) echo "\t\t\t(tf = 0.5 + (0.5 * f / max_ndt) = 0.5 + (0.5 * $f / $max_ndt) = $tf); \n";
					if($echo) echo "\t\t\tidf = ".$idfs[$i][$group]."\n";
					// $result[$user_id][$group]['tf'] = $tf;
					// $result[$user_id][$group]['tf_idf'] = $tf * $idfs[$group];
					
					// reviewers tf-df
					$tf_idfs[$user_id][$i][$group] = $tf * $idfs[$i][$group];
					if($echo) echo "\t\t\ttf-idf = ".$tf_idfs[$user_id][$i][$group]."\n";
					
					//tf-idf of query
					$tf_idfs_q[$i][$group] = (0.5 + (0.5 * $f_q / $max_f_q[$group])) * $idfs[$i][$group] * ($k == 0 ? 2 / 3 : 1 / 3);
					// $tf_idfs_q[$i][$group] = $f_q * $idfs[$i][$group] * ($k == 0 ? 1 : 0.5);
					if($echo) echo "\t\t\tquery tf-idf = (0.5 + (0.5 * $f_q / {$max_f_q[$group]})) * {$idfs[$i][$group]} * ($k == 0 ? 2 / 3 : 1 / 3) = ".$tf_idfs_q[$i][$group]."\n";
                }
				
                // foreach ($groups as $i => $group) {
					// $result2[$user_id] += $this->cosine($result[$user_id][$group], $idfs[$group]);
					// $result[$user_id][$msc][$group] = $reviewers[$user_id][$k]['gr' . $i]['tf'] * $idfs[$k]['gr' . $i]) * (1 - $i * 0.25);
                    // $result[$user_id] += ($reviewers[$user_id][$k]['gr' . $i]['tf_idf'] = $reviewers[$user_id][$k]['gr' . $i]['tf'] * $idfs[$k]['gr' . $i]) * (1 - $i * 0.25) * ($preffer_primary ? ($k == 0 ? 1 : 0.5) : 1) / ($normalization ? (count($mscs[0]) * log($n)) : 1);
                    // if ($df)echo " + {$reviewers[$user_id]['gr'.$i]['tf']} * $idf * (1 - $i*0.2)";
                    // $bm25 += ($idf * ($reviewers[$user_id]['gr'.$i]['f'] * ($k_1 + 1)) / ($reviewers[$user_id]['gr'.$i]['f'] + $k_1 * (1 - $b + $b * (count($interests)/$avgdl)))) * (1 - $i*0.2);
                // }
            }
            if($echo) echo "\n-----\n\n";
        }

        if($echo) print_r($tf_idfs);
        if($echo) echo "tf_idfs_q\n";
		if($echo) print_r($tf_idfs_q);
		$max_score = 0;
		foreach ($tf_idfs as $user_id => $groups) {
			if($echo) echo "[$user_id]:\n";
			foreach ($groups as $i => $group) {
				// if($echo) print_r($group);
				// if($echo) print_r($tf_idfs_q[$i]);
				$cos = $this->cosine($group, $tf_idfs_q[$i]);
				if($echo) echo "cos_$i = $cos * ".(3 - $i) / 6 ." = ";
				$result[$user_id] += $cos * ((3 - $i) / 6);
				// isset($result[$user_id]) ? $result[$user_id] += $group * ((3 - $i) / 6) : $result[$user_id] = $group * ((3 - $i) / 6);
				if($echo) echo $result[$user_id]."\n";
			}
			// if ($result[$user_id] > $max_score) $max_score = $result[$user_id];
		}
		
		//normalization by max score if max > 1
		// if($max_score > 1)
			// foreach ($result as $user_id => $score) {
				// $result[$user_id] = $score / $max_score;
			// }
			
        if($echo) print_r($result);
        // print_r($reviewers);

        return $result;
    }
	
	function dot_product($a, $b) {
		$products = array_map(function($a, $b) {
			return $a * $b;
		}, $a, $b);
		return array_reduce($products, function($a, $b) {
			return $a + $b;
		});
	}
	function magnitude($point) {
		$squares = array_map(function($x) {
			return pow($x, 2);
		}, $point);
		return sqrt(array_reduce($squares, function($a, $b) {
			return $a + $b;
		}));
	}
	function cosine($a, $b) {
		return $this->dot_product($a, $b) / ($this->magnitude($a) * $this->magnitude($b)); 
	}
}

?>
