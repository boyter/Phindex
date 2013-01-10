<?php
include_once(dirname(__FILE__).'/../interfaces/idocumentstore.php');

define('DOCUMENTSTORE_DOCUMENTFILEEXTENTION', '.txt');

class singlefolderdocumentstore implements idocumentstore {
	function __construct() {
		$this->_checkDefinitions();
	}

	public function storeDocument(array $document=null) {
		if(!is_array($document) || count($document) == 0) {
			return false;
		}
		$docid = $this->_getNextDocumentId();
		$serialized = serialize($document);
		$fp = fopen($this->_getFilePathName($docid), 'a');
		fwrite($fp, $serialized);
		fclose($fp);
		return $docid;
	}
  
	public function getDocument($documentid) {
		if(!is_integer($documentid) || $documentid < 0) {
			return null;
		}
		$filename = $this->_getFilePathName($documentid);
		if (!file_exists($filename)) {
		  return null;
		}
		$handle = fopen($filename, 'r');
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		$unserialized = unserialize($contents);
		return $unserialized;
	}
  
	public function clearDocuments() {
		$fp = opendir(DOCUMENTLOCATION);
		while(false !== ($file = readdir($fp))) {
			if(is_file(DOCUMENTLOCATION.$file)){
				unlink(DOCUMENTLOCATION.$file);
			}
		}
	}
  
	public function _checkDefinitions() {
		if(!defined('DOCUMENTLOCATION')) {
			throw new Exception('Expects DOCUMENTLOCATION to be defined!');
		}
	}
  
	public function _getFilePathName($name) {
		return DOCUMENTLOCATION.$name.DOCUMENTSTORE_DOCUMENTFILEEXTENTION;
	}
  
	public function _getNextDocumentId() {
		$countFile = $this->_getFilePathName('__count__');
		$count = 0;
		if(file_exists($countFile)) {
			$fh = fopen($countFile, 'r');
			$count = (int)fgets($fh);
		}
		$fh = fopen($countFile, 'w');
		fputs($fh,$count+1);
		fclose($fh);
		return $count;
	}
}
?>