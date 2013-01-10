<?php
interface iindex {
	public function storeDocuments($name,array $documents);
	public function getDocuments($name);
	public function clearIndex();
	public function validateDocument(array $document);
}
?>