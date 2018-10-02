<?php
error_reporting(E_ALL);
//error_reporting(0);
ini_set("display_errors", 1);
set_time_limit(0);
define('INDEXLOCATION',dirname(__FILE__).'/index/');
define('DOCUMENTLOCATION',dirname(__FILE__).'/documents/');

include_once('./classes/ranker.class.php');
include_once('./classes/indexer.class.php');
include_once('./classes/multifolderindex.class.php');
include_once('./classes/multifolderdocumentstore.class.php');

$ranker = new ranker();
$index = new multifolderindex();
$docstore = new multifolderdocumentstore();
$indexer = new indexer($index,$docstore,$ranker);


function html2txt($document){ 
	$search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript 
					'@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags 
					'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly 
					'@<![\s\S]*?--[ \t\n\r]*>@',        // Strip multi-line comments including CDATA 
					'@<style[^>]*?>.*?</style>@si',        // Strip CSS 
					'@\W+@si',        // Strip Whitespace
	); 
	$text = preg_replace($search, ' ', $document); 
	return $text; 
} 


$toindex = array();

$count = 0;

foreach(new RecursiveIteratorIterator (new RecursiveDirectoryIterator ('./crawler/documents/')) as $x) {
	$filename = $x->getPathname();
	if(is_file($filename)) {
		$handle = fopen($filename, 'r');
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		$unserialized = unserialize($contents);
		
		$url = $unserialized[0];
		$content = $unserialized[1];
		$rank = $unserialized[2];
		
		// Try to extract out the title. Using a regex because its easy
		// however not correct, see http://stackoverflow.com/questions/1732348/regex-match-open-tags-except-xhtml-self-contained-tags/1732454#1732454 for more details
		preg_match_all('/<title.*?>.*?<\/title>/i',$content, $matches);
		if(count($matches[0]) != 0) {
			$title = preg_replace('/[^(\x20-\x7F)]*/','',trim(strip_tags($matches[0][0])));
		}
		else {
			$title = '';
		}
		
		// Turns out PHP has a function for extracting meta tags for us, the only
		// catch is that it works on files, so we fake a file by creating one using
		// base64 encode and string concaternation
		$tmp = get_meta_tags("data://text/plain;base64,".base64_encode($content));
		if(isset($tmp['description'])) {
			$desc = preg_replace('/[^(\x20-\x7F)]*/','',trim($tmp['description']));
		}
		else {
			$desc = '';
		}
		
		// This is the rest of the content. We try to clean it somewhat using
		// the custom function html2text which works 90% of the tiem
		$content = preg_replace('/[^(\x20-\x7F)]*/','',trim(strip_tags(html2txt($content))));
		
		// If values arent set lets try to set them here. Start with desc
		// using content and then try the title using desc
		if($desc == '' && $content != '') {
			$desc = substr($content,0,200).'...';
		}
		if($title == '' && $desc != '') {
			$title = substr($desc,0,50).'...';
		}
		
		$count++;
		// If we dont have a title, then we dont have desc or content
		// so lets not add it to the index
		if($title != '') {
			$toindex[] = array($url, $title, $desc, $rank);
			echo 'INDEXING '.$count."\r\n";
		}
		else {
			echo 'SKIP '.$count."\r\n";
		}
		
	}
}
echo "Starting Index\r\n";
$indexer->index($toindex);

?>
