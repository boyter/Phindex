<?php
$file_handle = fopen("Quantcast-Top-Million.txt", "r");

while (!feof($file_handle)) {
	$line = fgets($file_handle);
	if(preg_match('/^\d+/',$line)) { # if it starts with some amount of digits
		$tmp = explode("\t",$line);
		$rank = trim($tmp[0]);
		$url = trim($tmp[1]);
		if($url != 'Hidden profile') { # Hidden profile appears sometimes just ignore then
			echo $rank.' http://'.$url."/\n";
		}
	}
}
fclose($file_handle);
?>