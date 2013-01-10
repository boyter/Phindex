<?php
include_once(dirname(__FILE__).'/../interfaces/iranker.php');
include_once(dirname(__FILE__).'/../classes/porterstemmer.class.php');

define('RANKER_TITLEWEIGHT', 1000);
define('RANKER_URLWEIGHT', 6000);
define('RANKER_URLWEIGHTLOOSE', 2000);
define('RANKER_TERMWEIGHT', 100);

class ranker implements iranker {

	public $stemmed = array();

	function __construct() {
	}
  
	public function rankDocuments($document,$document2) {
		if(!is_array($document) || !is_array($document2)) {
			throw new Exception('Document(s) not array!');
		}
		if(count($document) != 4 || count($document2) != 4) {
			throw new Exception('Document not correct format!');
		}
		
		if(	$document[3] == $document2[3] ) {
			return 0;
		}
		if(	$document[3] <= $document2[3] ) {
			return 1;
		}
		return -1;
	}
	
	public function rankIndex($document,$document2) {
		if(!is_array($document) || !is_array($document2)) {
			throw new Exception('Document(s) not array!');
		}
		if(count($document) != 3 || count($document2) != 3) {
			throw new Exception('Document not correct format!');
		}
		
		if(	$document[1] == $document2[1] ) {
			return 0;
		}
		if(	$document[1] <= $document2[1] ) {
			return 1;
		}
		return -1;
	}
	
	public function rankDocument($term, $document) {
		$cleanurl = $this->_cleanString($document[0]);
		$cleantitle = $this->_cleanString($document[1]);
		$cleanmeta = $this->_cleanString($document[2]);
		$rank = $document[3];
		
		preg_match_all('/ '.$term.' /i', $cleanurl, $urlcount);
		preg_match_all('/'.$term.'/i', $cleanurl, $urlcountloose);
		preg_match_all('/ '.$term.' /i', $cleantitle, $titlecount);
		preg_match_all('/ '.$term.' /i', $cleanmeta, $pagecount);
		
		$words_in_url 			= count($urlcount[0]);
		$words_in_url_loose 	= count($urlcountloose[0]);
		$words_in_title 		= count($titlecount[0]);
		$words_in_meta 			= count($pagecount[0]);
		
		$weight = ( $words_in_meta * RANKER_TERMWEIGHT
					+ $words_in_title * RANKER_TITLEWEIGHT
					+ $words_in_url * RANKER_URLWEIGHT
					+ $words_in_url_loose * RANKER_URLWEIGHTLOOSE
				);
		
		// Normalise between 1 and 10 and then invert so
		// top 100 sites are 9.9 something and bottom 100 are 0.1
		$normailise = 10-(1 + ($rank-1)*(10-1)) / (1000000 - 1);
		$newweight = intval($weight * $normailise);
		
		return $newweight;
	}
	
	
	public function _cleanString($contents) {
		$cleandocument = strip_tags(strtolower($contents));
		$cleandocument = preg_replace('/\W/i',' ',$cleandocument);
		$cleandocument = preg_replace('/\s\s+/', ' ', $cleandocument);
		
		$return = '';
		foreach(explode(' ',$cleandocument) as $term) {
			if(array_key_exists($term,$this->stemmed)) {
				$return .= ' '.$this->stemmed[$term];
			}
			else {
				$stem = PorterStemmer::Stem($term);
				$this->stemmed[$term] = $stem;
				$return .= ' '.$stem;
			}
		}
		
		return $return;
	}
	
}
?>