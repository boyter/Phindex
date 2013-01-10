<?php
include_once(dirname(__FILE__).'/../interfaces/iranker.php');

class naieveranker implements iranker {

	function __construct() {
	}
  
	public function rankDocuments($document,$document2) {
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
}
?>