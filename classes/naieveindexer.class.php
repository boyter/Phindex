<?php
include_once(dirname(__FILE__).'/../interfaces/iindexer.php');
include_once(dirname(__FILE__).'/../classes/singlefolderindex.class.php');

class naieveindexer implements iindexer {
	public $index = null;
	public $documentstore = null;

	function __construct(iindex $index,idocumentstore $documentstore) {
		$this->index = $index;
		$this->documentstore = $documentstore;
	}
  
	public function index(array $documents) {
		if(!is_array($documents)) {
			return false;
		}
		foreach($documents as $document) {
			$id = $this->documentstore->storeDocument(array($document));
			$con = $this->_concordance($this->_cleanDocument($document));
			foreach($con as $word => $count) {
				$ind = $this->index->getDocuments($word);
				if(count($ind) == 0) {
					$this->index->storeDocuments($word,array(array($id,$count,0)));
				}
				else {
					$ind[] = array($id,0,0);
					$this->index->storeDocuments($word,$ind);
				}
			}
		}
		return true;
	}

	public function _concordance(array $document) {
		if(!is_array($document)) {
			return array();
		}
		$con = array();
		foreach($document as $word) {
			if(array_key_exists($word,$con)) {
				$con[$word] = $con[$word] + 1;
			}
			else {
				$con[$word] = 1;
			}
		}
		return $con;
	}
  
	public function _cleanDocument($document) {
		if(!is_string($document)) {
			return array();
		}
		$cleandocument = strip_tags(strtolower($document));
		$cleandocument = preg_replace('/\W/i',' ',$cleandocument);
		$cleandocument = preg_replace('/\s\s+/', ' ', $cleandocument);
		if($cleandocument != ''){
			return explode(' ',trim($cleandocument));
		}
		return array();
	}
}
?>