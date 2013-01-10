<?php
error_reporting(E_ALL); 
ini_set('display_errors', '1');

require_once(dirname(__FILE__) . '/simpletest/autorun.php');
require_once('../classes/naievesearch.class.php');
require_once('../interfaces/iindex.php');
require_once('../interfaces/idocumentstore.php');
Mock::generate('iindex');
Mock::generate('idocumentstore');

class test_naievesearch extends UnitTestCase {
  function setUp() {
    $index = new Mockiindex();
	$documentstore = new Mockidocumentstore();
	$this->search = new naievesearch($index,$documentstore);
  }
  function tearDown() {
  }
  
  function testCleanSearchTerms() {
    $this->assertEqual(array('test'),$this->search->_cleanSearchTerms('test'));
	$this->assertEqual(array('test'),$this->search->_cleanSearchTerms('test!@#@$#%(#@*&%$(#&%)(*#%&)(#*@&%*'));
	$this->assertEqual(array('test','too'),$this->search->_cleanSearchTerms('test too'));
	$this->assertEqual(array('another','test'),$this->search->_cleanSearchTerms('another( test'));
	$this->assertEqual(array('another','test'),$this->search->_cleanSearchTerms('another       test'));
	$this->assertEqual(array('another','test'),$this->search->_cleanSearchTerms('      another       test    '));
	$this->assertEqual(array('another'),$this->search->_cleanSearchTerms('another       '));
	$this->assertEqual(array('another'),$this->search->_cleanSearchTerms('        another       '));
	$this->assertEqual(array('another'),$this->search->_cleanSearchTerms('        another'));
	$this->assertEqual(array('a','a','a','aa','a'),$this->search->_cleanSearchTerms('a a a aa a'));
  }
  function testDoSearchReturnsAnythingButNull() {
	$this->assertNotNull($this->search->dosearch(null));
	$this->assertNotNull($this->search->dosearch(''));
	$this->assertNotNull($this->search->dosearch('test'));
	$this->assertNotNull($this->search->dosearch(1));
	$this->assertNotNull($this->search->dosearch(true));
  }
  function testDoSearchReturnsArrayForAnything() {
  	$this->assertIsA($this->search->dosearch(null),'array');
	$this->assertIsA($this->search->dosearch(''),'array');
	$this->assertIsA($this->search->dosearch('test'),'array');
	$this->assertIsA($this->search->dosearch(1),'array');
	$this->assertIsA($this->search->dosearch(true),'array');
  }
  function testDoSearchReturnsEmptyArrayForAnythingNotString() {
    $this->assertEqual(0,count($this->search->dosearch(null)));
	$this->assertEqual(0,count($this->search->dosearch(1)));
	$this->assertEqual(0,count($this->search->dosearch(true)));
  }
  function testDoSearchIndexDocumentSingleDocumentThatExistsReturnsDocument() {
  	$index = new Mockiindex();
	$documentstore = new Mockidocumentstore();
	$index->expectOnce('getDocuments', array('test'));
	$index->returns('getDocuments',array(array(1,0,0)));
	$documentstore->expectOnce('getDocument', array(1));
	$documentstore->returns('getDocument', array('this is the document with id 1'));
	$this->search = new naievesearch($index,$documentstore);
	$ret = $this->search->dosearch('test');
	$this->assertEqual(array(array('this is the document with id 1')),$ret);
  }
  function testDoSearchIndexDocumentTwoDocumentThatExistsReturnsTwoDocuments() {
  	$index = new Mockiindex();
	$documentstore = new Mockidocumentstore();
	$index->expectOnce('getDocuments', array('test'));
	$index->returns('getDocuments',array(array(1,0,0),array(2,0,0)));
	$documentstore->expectAt(0,'getDocument', array(1));
	$documentstore->expectAt(1,'getDocument', array(2));
	$documentstore->returnsAt(0,'getDocument', array('this is the document with id 1'));
	$documentstore->returnsAt(1,'getDocument', array('this is the document with id 2'));
	$this->search = new naievesearch($index,$documentstore);
	$ret = $this->search->dosearch('test');
	$this->assertEqual(array(array('this is the document with id 1'), array('this is the document with id 2')),$ret);
  }
  function testDoSearchIndexDocumentThreeDocumentThatExistsReturnsThreeDocuments() {
  	$index = new Mockiindex();
	$documentstore = new Mockidocumentstore();
	$index->expectOnce('getDocuments', array('test'));
	$index->returns('getDocuments',array(array(1,0,0),array(2,0,0),array(3,0,0)));
	$documentstore->expectAt(0,'getDocument', array(1));
	$documentstore->expectAt(1,'getDocument', array(2));
	$documentstore->expectAt(2,'getDocument', array(3));
	$documentstore->returnsAt(0,'getDocument', array('this is the document with id 1'));
	$documentstore->returnsAt(1,'getDocument', array('this is the document with id 2'));
	$documentstore->returnsAt(2,'getDocument', array('this is the document with id 3'));
	$this->search = new naievesearch($index,$documentstore);
	$ret = $this->search->dosearch('test');
	$this->assertEqual(array(array('this is the document with id 1'), 
	                         array('this is the document with id 2'),
							 array('this is the document with id 3')),$ret);
  }
  function testDoSearchIndexDocument1000DocumentsExistExpect1000() {
  	$index = new Mockiindex();
	$documentstore = new Mockidocumentstore();
	
	$doclist = array();
	$expected = array();
	for($i=0;$i<1000;$i++) {
	  $doclist[] = array($i,0,0);
	  $documentstore->expectAt($i,'getDocument', array($i));
	  $documentstore->returnsAt($i,'getDocument', array('this is the document with id '.$i));
	  $expected[] = array('this is the document with id '.$i);
	}
	$index->expectOnce('getDocuments', array('test'));
	$index->returns('getDocuments',$doclist);
	
	$this->search = new naievesearch($index,$documentstore);
	$ret = $this->search->dosearch('test');
	$this->assertEqual($expected,$ret);
  }
  function testDoSearchTwoTermsIndexDocumentSingleDocumentThatExistsReturnsDocument() {
  	$index = new Mockiindex();
	$documentstore = new Mockidocumentstore();
	$index->expectAt(0,'getDocuments', array('test'));
	$index->expectAt(1,'getDocuments', array('test2'));
	$index->returnsAt(0,'getDocuments',array(array(1,0,0)));
	$index->returnsAt(1,'getDocuments',array());
	$documentstore->expectOnce('getDocument', array(1));
	$documentstore->returns('getDocument', array('this is the document with id 1'));
	$this->search = new naievesearch($index,$documentstore);
	$ret = $this->search->dosearch('test test2');
	$this->assertEqual(array(array('this is the document with id 1')),$ret);
  }
  function testDoSearchTwoTermsIndexDocumentTwoDocumentThatExistsReturnsTwoDocument() {
  	$index = new Mockiindex();
	$documentstore = new Mockidocumentstore();
	$index->expectAt(0,'getDocuments', array('test'));
	$index->expectAt(1,'getDocuments', array('test2'));
	$index->returnsAt(0,'getDocuments',array(array(1,0,0)));
	$index->returnsAt(1,'getDocuments',array(array(2,0,0)));
	$documentstore->expectAt(0,'getDocument', array(1));
	$documentstore->expectAt(1,'getDocument', array(2));
	$documentstore->returnsAt(0,'getDocument', array('this is the document with id 1'));
	$documentstore->returnsAt(1,'getDocument', array('this is the document with id 2'));
	$this->search = new naievesearch($index,$documentstore);
	$ret = $this->search->dosearch('test test2');
	$this->assertEqual(array(array('this is the document with id 1'),
                             array('this is the document with id 2')),$ret);
  }
  function testDoSearchThreeTermsIndexDocumentThreeDocumentThatExistsReturnsThreeDocument() {
  	$index = new Mockiindex();
	$documentstore = new Mockidocumentstore();
	$index->expectAt(0,'getDocuments', array('test'));
	$index->expectAt(1,'getDocuments', array('test2'));
	$index->expectAt(2,'getDocuments', array('test3'));
	$index->returnsAt(0,'getDocuments',array(array(1,0,0)));
	$index->returnsAt(1,'getDocuments',array(array(2,0,0)));
	$index->returnsAt(2,'getDocuments',array(array(3,0,0)));
	$documentstore->expectAt(0,'getDocument', array(1));
	$documentstore->expectAt(1,'getDocument', array(2));
	$documentstore->expectAt(2,'getDocument', array(3));
	$documentstore->returnsAt(0,'getDocument', array('this is the document with id 1'));
	$documentstore->returnsAt(1,'getDocument', array('this is the document with id 2'));
	$documentstore->returnsAt(2,'getDocument', array('this is the document with id 3'));
	$this->search = new naievesearch($index,$documentstore);
	$ret = $this->search->dosearch('test test2 test3');
	$this->assertEqual(array(array('this is the document with id 1'),
                             array('this is the document with id 2'),
							 array('this is the document with id 3')),$ret);
  }
}
?>