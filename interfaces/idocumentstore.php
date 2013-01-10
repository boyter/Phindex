<?php
interface idocumentstore {
	public function storeDocument(array $document);
	public function getDocument($documentid);
	public function clearDocuments();
}
?>