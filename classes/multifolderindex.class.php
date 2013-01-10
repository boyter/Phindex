<?php
include_once(dirname(__FILE__).'/../interfaces/iindex.php');

define('MULTIINDEX_DOCUMENTCOUNT', 3);
define('MULTIINDEX_DOCUMENTBYTESIZE', 12);
define('MULTIINDEX_DOCUMENTINTEGERBYTESIZE', 4);
define('MULTIINDEX_DOCUMENTFILEEXTENTION', '.bin');
define('MULTIINDEX_DOCUMENTRETURN', 1500);

class multifolderindex implements iindex {
	function __construct() {
		$this->_checkDefinitions();
	}

	public function storeDocuments($name, array $documents = null) {
		if($name === null || $documents === null || trim($name) == '') {
			return false;
		}
		if(!is_string($name) || !is_array($documents)) {
			return false;
		}
		foreach($documents as $doc) {
			if(!$this->validateDocument($doc)){
				return false;
			}
		}
		$fp = fopen($this->_getFilePathName($name),'w');
		foreach($documents as $doc) {
			$bindata1 = pack('i',intval($doc[0]));
			$bindata2 = pack('i',intval($doc[1]));
			$bindata3 = pack('i',intval($doc[2]));
			fwrite($fp,$bindata1);
			fwrite($fp,$bindata2);
			fwrite($fp,$bindata3);
		}
		fclose($fp);
		return true;
	}
  
	public function getDocuments($name) {
		if(!file_exists($this->_getFilePathName($name))) {
			return array();
		}
		$fp = fopen($this->_getFilePathName($name),'r');
		$filesize = filesize($this->_getFilePathName($name));
		
		if($filesize%MULTIINDEX_DOCUMENTBYTESIZE != 0) {
			throw new Exception('Filesize not correct index is corrupt!');
		}
		$ret = array();
		$count = 0;
		for($i=0;$i<$filesize/MULTIINDEX_DOCUMENTBYTESIZE;$i++) {
			$bindata1 = fread($fp,MULTIINDEX_DOCUMENTINTEGERBYTESIZE);
			$bindata2 = fread($fp,MULTIINDEX_DOCUMENTINTEGERBYTESIZE);
			$bindata3 = fread($fp,MULTIINDEX_DOCUMENTINTEGERBYTESIZE);
			$data1 = unpack('i',$bindata1);
			$data2 = unpack('i',$bindata2);
			$data3 = unpack('i',$bindata3);
			$ret[] = array($data1[1],
							$data2[1],
							$data3[1]);
			$count++;
			if($count == MULTIINDEX_DOCUMENTRETURN) {
				break;
			}
		}
		fclose($fp);
		return $ret;
	}
  
	/**
	* Clears the index of all index documents, but does not
	* remove the index location itself.
	*/
	public function clearIndex() {
		$fp = opendir(INDEXLOCATION);
		while(false !== ($file = readdir($fp))) {
			if(is_file(INDEXLOCATION.$file)){
				unlink(INDEXLOCATION.$file);
			}
		}
	}
  
	/**
	* Validates a document against what is required to store
	* in the index.
	*/
	public function validateDocument(array $document=null) {
		if(!is_array($document)) {
			return false;
		}
		if(count($document) != MULTIINDEX_DOCUMENTCOUNT) {
			return false;
		}
		for($i=0;$i<MULTIINDEX_DOCUMENTCOUNT;$i++) {
			if(!is_int($document[$i]) || $document[$i] < 0) {
				return false;
			}
		}
		return true;
	}
   
	public function _getFilePathName($name) {
		$md5 = md5($name);
		$one = substr($md5,0,2);
		if(!file_exists(INDEXLOCATION.$one.'/')) {
			mkdir(INDEXLOCATION.$one.'/');
		}
		return INDEXLOCATION.$one.'/'.$name.MULTIINDEX_DOCUMENTFILEEXTENTION;
	}
  
	public function _checkDefinitions() {
		if(!defined('INDEXLOCATION')) {
			throw new Exception('Expects INDEXLOCATION to be defined!');
		}
	}
}
?>