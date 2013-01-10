<?php
include_once(dirname(__FILE__).'/../interfaces/isearch.php');
include_once(dirname(__FILE__).'/../interfaces/iranker.php');
include_once(dirname(__FILE__).'/../classes/singlefolderindex.class.php');
include_once(dirname(__FILE__).'/../classes/porterstemmer.class.php');

define('SEARCH_DOCUMENTRETURN', 10);
define('SEARCH_DISPLAYPORN', FALSE);
define('SEARCH_PORNFILTER','/porn|naked|teens|pussy|sex|nasty|mature|crossdresser|couples|girlfriend|wives|pornstar|cock|fuck|shit|cunt|nude|lesbian|sexy|ass|ladyboy|granny|cum|boob|breast|exposing|milf|erotic|bdsm|live|penis|horny|slut|nudist|upskirt|boobs|tits|amateur|hottest|adult|teen|babe|1yo|2yo|3yo|4yo|5yo|6yo|7yo|8yo|9yo|10yo|11yo|12yo|13yo|14yo|15yo|16yo|17yo|incest|jailbait|kdv|kiddie|kiddy|kinder|Lolita|lsm|mbla|molested|ninfeta|pedo|phat|pjk|pthc|ptsc|premature|preteen|pthc|qsh|qwerty|r@ygold|raped|teensex|yovo|Pr0nStarS|tranny|transvest|XXX|Anal|Asshole|Bangbros|Barely|Blow|Blowjob|Bondage|brazzers|Camera_Phone|Centerfold|Clitoris|Cock|Cum|Cunt|Deepthroat|Diaper|Drilled|EROTRIX|Facial|Femjoy|Fetish|Fisting|fotos|FTV|Fuck|Gangbang|Gay|Handjob|Hardcore|Headjob|hidden_cam|Hustler|Jenna|Lesbo|Masturbat|MILF|nackte|naken|Naturals|Nipple|Nubile|Onlytease|Orgasm|Orgy|Penis|Penthouse|Playboy|Porn|Profileasian|Profileblond|Pussy|Scroops|selfpic|spunky_teens|strapon|strappon|Suck|TeenTraps|tittie|titty|tranny|transvest|twat|vagina|webcam|Whore|XPUSS|Amateur|Blonde|Brunette|Naked|Naughty|Private|Redhead|Sex|Slut|Strips|Teen|Young|wet|girl|video|taboo|nastiest/i');

class search implements isearch {
	public $index = null;
	public $documentstore = null;
	public $ranker = null;

	function __construct(iindex $index, idocumentstore $documentstore, iranker $ranker) {
		$this->index = $index;
		$this->documentstore = $documentstore;
		$this->ranker = $ranker;
	}


	function dosearch($searchterms, $seeporn=SEARCH_DISPLAYPORN) {
		$indresult = array(); // AND results 
		$indorresult = array(); // OR results IE everything
		
		$interlists = array();
		
		$cleansearchterms = $this->_cleanSearchTerms($searchterms);
		
		foreach($cleansearchterms as $term) {
			$ind = $this->index->getDocuments($term);
			if($ind != null) {
				$tmp = array();
				foreach($ind as $i) {
					$indorresult[$i[0]] = $i[0];
					$tmp[] = $i[0];
				}
				$interlists[] = $tmp;
			}
		}
		
		// Get the intersection of the lists
		$indresult = $interlists[0];
		foreach($interlists as $lst) {
			$indresult = array_intersect($indresult, $lst);
		}
		
		
		$doc = array();
		$count = 0;
		foreach($indresult as $i) {
			
			$document = $this->documentstore->getDocument($i);
			$document = $document[0];

			// Rank the documents based on all the terms
			$rank = 0;
			foreach($cleansearchterms as $term) {
				$rank = $rank + $this->ranker->rankDocument($term, $document);
			}
			$document[3] = $rank;
			
			preg_match_all(SEARCH_PORNFILTER, $document[1].$document[2], $matches);

			// if they want to see porn, or its not porn
			if($seeporn || count($matches[0]) <= 1) {
				$doc[] = $document;
				$count++;
				if($count == SEARCH_DOCUMENTRETURN) {
					break;
				}
			}
		}
		
		usort($doc, array($this->ranker, 'rankDocuments'));
		
		
		if($count != SEARCH_DOCUMENTRETURN) { // If we dont have enough results to AND default to OR
			foreach($indorresult as $i) {
				$document = $this->documentstore->getDocument($i);
				$document = $document[0];
				
				// Rank the documents based on all the terms
				$rank = 0;
				foreach($cleansearchterms as $term) {
					$rank = $rank + $this->ranker->rankDocument($term, $document);
				}
				$document[3] = $rank;
				
				if(!in_array($document, $doc)) { # not already in there
				
					preg_match_all(SEARCH_PORNFILTER, $document[1].$document[2], $matches);
					
					if($seeporn || count($matches[0]) <= 1) {
						$doc[] = $document;
						$count++;
						if($count == SEARCH_DOCUMENTRETURN) {
							break;
						}
					}
				}
			}
		}

		return $doc;
	}
	
  
	function _cleanSearchTerms($searchterms) {
		$cleansearchterms = strtolower($searchterms);
		$cleansearchterms = preg_replace('/\W/i',' ',$cleansearchterms);
		$cleansearchterms = preg_replace('/\s\s+/', ' ', $cleansearchterms);
		$terms = explode(' ',trim($cleansearchterms));
		
		$toreturn = array();
		foreach($terms as $term) {
			$term = PorterStemmer::Stem($term);
			$toreturn[] = $term;
		}
		return $toreturn;
	}
}
?>