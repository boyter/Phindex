<?php
interface iranker {
	public function rankDocuments($document,$document2);
	public function rankIndex($document,$document2);
	public function rankDocument($term, $document);
}
?>