<?php
include_once(dirname(__FILE__).'/../interfaces/iindexer.php');
include_once(dirname(__FILE__).'/../interfaces/iranker.php');
include_once(dirname(__FILE__).'/../classes/porterstemmer.class.php');

define('INDEXER_DOCUMENTFILEEXTENTION', '.bin');

class indexer implements iindexer {
	public $index = null;
	public $documentstore = null;
	public $ranker = null;
	public $stemmercache = array();

	function __construct(iindex $index,idocumentstore $documentstore, iranker $ranker) {
		$this->_checkDefinitions();
		$this->index = $index;
		$this->documentstore = $documentstore;
		$this->ranker = $ranker;
	}
  
	public function index(array $documents) {
		if(!is_array($documents)) {
			return false;
		}
		
		$indexcache = array(); // So we know if the flush file exists
		
		foreach($documents as $document) {
			// Clean up the document and create stemmed text for ranking down the line
			$con = $this->_concordance($this->_cleanDocument($document));
			
			// Save the document and get its ID
			$id = $this->documentstore->storeDocument(array($document));

			foreach($con as $word => $count) {
			
				if(!$this->_inStopList($word) && strlen($word) >= 2) {
					if(!array_key_exists($word, $indexcache)) {
						$ind = $this->index->getDocuments($word);
						$this->_createFlushFile($word, $ind);
						$indexcache[$word] = $word;
					}
					
					// Rank the Document
					$rank = $this->ranker->rankDocument($word,$document);
					
					$this->_addToFlushFile($word,array($id,$rank,0));
				}
			}
		}
		
		foreach($indexcache as $word) {
			$ind = $this->_readFlushFile($word);
			unlink(INDEXLOCATION.$word.INDEXER_DOCUMENTFILEEXTENTION);
			
			usort($ind, array($this->ranker, 'rankIndex'));
			$this->index->storeDocuments($word,$ind);
		}
		
		return true;
	}
	
	
	public function _createFlushFile($word, $ind) {
		$fp = fopen(INDEXLOCATION.$word.INDEXER_DOCUMENTFILEEXTENTION,'w');
		fclose($fp);
		$this->indexfp[$word] = $word;
		foreach($ind as $doc) {
			$this->_addToFlushFile($word, $ind);
		}
	}
	
	public function _addToFlushFile($word, $doc) {
		$fp = fopen(INDEXLOCATION.$word.INDEXER_DOCUMENTFILEEXTENTION,'a');
		
		if($fp !== false) {
			$bindata1 = pack('i',intval($doc[0]));
			$bindata2 = pack('i',intval($doc[1]));
			$bindata3 = pack('i',intval($doc[2]));
			fwrite($fp,$bindata1);
			fwrite($fp,$bindata2);
			fwrite($fp,$bindata3);
			fclose($fp);
		}
	}
	
	public function _readFlushFile($name) {
		$fp = fopen(INDEXLOCATION.$name.INDEXER_DOCUMENTFILEEXTENTION,'r');
		$filesize = filesize(INDEXLOCATION.$name.INDEXER_DOCUMENTFILEEXTENTION);
		
		$ret = array();
		
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
		}
		fclose($fp);
		return $ret;
	}
	

	public function _concordance(array $document) {
		if(!is_array($document)) {
			return array();
		}
		$con = array();
		foreach($document as $word) {
			if(array_key_exists($word, $this->stemmercache)) {
				$word = $stemmercache[$word];
			}
			else {
				$stem = PorterStemmer::Stem($word);
				$stemmercache[$word] = $stem;
				$word = $stem;
			}
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
		$contents = $document[0].' '.$document[1].' '.$document[2];
		
		$cleandocument = strip_tags(strtolower($contents));
		$cleandocument = preg_replace('/\W/i',' ',$cleandocument);
		$cleandocument = preg_replace('/\s\s+/', ' ', $cleandocument);
		if($cleandocument != ''){
			return explode(' ',trim($cleandocument));
		}
		return array();
	}
	
	public function _inStopList($term) {
		$stopwords = array("a", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also","although","always","am","among", "amongst", "amoungst", "amount",  "an", "and", "another", "any","anyhow","anyone","anything","anyway", "anywhere", "are", "around", "as",  "at", "back","be","became", "because","become","becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom","but", "by", "call", "can", "cannot", "cant", "co", "con", "could", "couldnt", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven","else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least", "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own","part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereupon", "these", "they", "thickv", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "very", "via", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself", "yourselves", "the", "www", "com", "http", "net", "org", "gov");
		
		return in_array($term, $stopwords);
	}
	
	public function _checkDefinitions() {
		if(!defined('INDEXLOCATION')) {
			throw new Exception('Expects INDEXLOCATION to be defined!');
		}
	}
	
}
?>