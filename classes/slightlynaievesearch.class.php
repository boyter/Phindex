<?php
include_once(dirname(__FILE__).'/../interfaces/isearch.php');
include_once(dirname(__FILE__).'/../interfaces/iranker.php');
include_once(dirname(__FILE__).'/../classes/singlefolderindex.class.php');

class slightlynaievesearch implements isearch {
	public $index = null;
	public $documentstore = null;
	public $ranker = null;

	function __construct(iindex $index, idocumentstore $documentstore, iranker $ranker) {
		$this->index = $index;
		$this->documentstore = $documentstore;
		$this->ranker = $ranker;
	}


	function dosearch($searchterms) {
		$indresult = array();
		foreach($this->_cleanSearchTerms($searchterms) as $term) {
			
			$ind = $this->index->getDocuments($term);
			
			if($ind != null) {
				usort($ind, array($this->ranker, 'rankDocuments'));
				foreach($ind as $i) {
					$indresult[$i[0]] = $i[0];
				}
			}
		}

		$doc = array();
		foreach($indresult as $i) {
			$doc[] = $this->documentstore->getDocument($i);
		}

		return $doc;
	}
	
  
	function _cleanSearchTerms($searchterms) {
		$cleansearchterms = strtolower($searchterms);
		$cleansearchterms = preg_replace('/\W/i',' ',$cleansearchterms);
		$cleansearchterms = preg_replace('/\s\s+/', ' ', $cleansearchterms);
		return explode(' ',trim($cleansearchterms));
	}
}
?>