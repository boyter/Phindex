<?php
include_once(dirname(__FILE__).'/../interfaces/isearch.php');
include_once(dirname(__FILE__).'/../interfaces/iranker.php');
include_once(dirname(__FILE__).'/../classes/singlefolderindex.class.php');

class naievesearch implements isearch {
	public $index = null;
	public $documentstore = null;
	public $ranker = null;

	function __construct(iindex $index, idocumentstore $documentstore, iranker $ranker) {
		$this->index = $index;
		$this->documentstore = $documentstore;
		$this->ranker = $ranker;
	}
  
	public function dosearch($searchterms) {
		$doc = array();
		foreach($this->_cleanSearchTerms($searchterms) as $term) {
			$ind = $this->index->getDocuments($term);

			if($ind != null) {
				foreach($ind as $i) {
					$doc[] = $this->documentstore->getDocument($i[0]);
					usort($ind, array($this->ranker, 'rankDocuments'));
				}
			}
		}
		return $doc;
	}
  
	public function _cleanSearchTerms($searchterms) {
		$cleansearchterms = strtolower($searchterms);
		$cleansearchterms = preg_replace('/\W/i',' ',$cleansearchterms);
		$cleansearchterms = preg_replace('/\s\s+/', ' ', $cleansearchterms);
		return explode(' ',trim($cleansearchterms));
	}
}
?>