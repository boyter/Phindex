<?php
error_reporting(E_ALL); 
ini_set('display_errors', '1');

require_once(dirname(__FILE__) . '/simpletest/autorun.php');
require_once('../classes/naieveindexer.class.php');
require_once('../interfaces/iindex.php');
require_once('../interfaces/idocumentstore.php');
Mock::generate('iindex');
Mock::generate('idocumentstore');

class test_naieveindexer extends UnitTestCase {
  function setUp() {
    $index = new Mockiindex();
	$documentstore = new Mockidocumentstore();
	$this->indexer = new naieveindexer($index,$documentstore);
  }
  function tearDown() {
  }
  
  function testCleanDocumentAlwaysReturnsArray() {
    $this->assertIsA($this->indexer->_cleanDocument(''),'array');
	$this->assertIsA($this->indexer->_cleanDocument(1),'array');
	$this->assertIsA($this->indexer->_cleanDocument(null),'array');
	$this->assertIsA($this->indexer->_cleanDocument(array()),'array');
	$this->assertIsA($this->indexer->_cleanDocument(true),'array');
  }
  function testCleanDocumentCheckReturnValuesSimpleString() {
    $this->assertEqual(array('test'),$this->indexer->_cleanDocument('test'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('test   '));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('    test   '));
	$this->assertEqual(array('test','test'),$this->indexer->_cleanDocument('test test'));
	$this->assertEqual(array('test','test'),$this->indexer->_cleanDocument('test     test'));
	$this->assertEqual(array('test','test'),$this->indexer->_cleanDocument('   test     test   '));
  }
  function testCleanDocumentCheckReturnValuesLowersString() {
    $this->assertEqual(array('test'),$this->indexer->_cleanDocument('Test'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('tEst'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('TEst'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('tesT'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('TEST'));
  }
  function testCleanDocumentCheckReturnValuesHtml() {
    $this->assertEqual(array('test'),$this->indexer->_cleanDocument('<b>test</b>'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('<i>test</i>'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('<b>test</b'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('<b>test'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('test</b>'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('<title>test</title>'));
	$this->assertEqual(array('test'),$this->indexer->_cleanDocument('<meta description="test">test</meta>'));
  }
  function testCleanDocumentCheckReturnNeverEmptyString() {
    $this->assertEqual(array(),$this->indexer->_cleanDocument('<b></b>'));
  }
  function testConcordanceAlwaysReturnsArray() {
	$this->assertIsA($this->indexer->_concordance(array()),'array');
  }
  function testConcordanceBuildsCorrectOneWords() {
    $test = array('test');
	$ret = $this->indexer->_concordance($test);
	$this->assertEqual(array('test'=>1),$ret);
  }
  function testConcordanceBuildsCorrectTwoWordsSame() {
    $test = array('test','test');
	$ret = $this->indexer->_concordance($test);
	$this->assertEqual(array('test'=>2),$ret);
  }
  function testConcordanceBuildsCorrectThreeWordsSame() {
    $test = array('test','test','test');
	$ret = $this->indexer->_concordance($test);
	$this->assertEqual(array('test'=>3),$ret);
  }
  function testConcordanceBuildsCorrect1000WordsSame() {
    $test = array();
    for($i=0;$i<1000;$i++) {
      $test[] = 'test';
	}
	$ret = $this->indexer->_concordance($test);
	$this->assertEqual(array('test'=>1000),$ret);
  }
  function testConcordanceBuildsCorrectTwoWordsDifferent() {
    $test = array('test','test2');
	$ret = $this->indexer->_concordance($test);
	$this->assertEqual(array('test'=>1,'test2'=>1),$ret);
  }
  function testConcordanceBuildsCorrect4WordsTwoSimilar() {
    $test = array('test','test2','test','test2');
	$ret = $this->indexer->_concordance($test);
	$this->assertEqual(array('test'=>2,'test2'=>2),$ret);
  }
  function testIndexSingleWordDocumentCheckMockAsserts() {
    $index = new Mockiindex();
	$documentstore = new Mockidocumentstore();

	$documentstore->expectOnce('storeDocument', array(array('test')));
	$documentstore->returns('storeDocument',1);
	$index->expectOnce('storeDocuments',array('test',array(array(1,1,0))));
	
	$this->indexer = new naieveindexer($index,$documentstore);
	$ret = $this->indexer->index(array('test'));
	$this->assertTrue($ret);
  }
  function testIndexTwoSameWordDocumentCheckMockAsserts() {
    $index = new Mockiindex();
	$documentstore = new Mockidocumentstore();

	$documentstore->expectOnce('storeDocument', array(array('test test')));
	$documentstore->returns('storeDocument',1);
	$index->expectOnce('storeDocuments',array('test',array(array(1,2,0))));
	
	$this->indexer = new naieveindexer($index,$documentstore);
	$ret = $this->indexer->index(array('test test'));
	$this->assertTrue($ret);
  }
  function testIndexThreeSameWordDocumentCheckMockAsserts() {
    $index = new Mockiindex();
	$documentstore = new Mockidocumentstore();

	$documentstore->expectOnce('storeDocument', array(array('test test test')));
	$documentstore->returns('storeDocument',1);
	$index->expectOnce('storeDocuments',array('test',array(array(1,3,0))));
	
	$this->indexer = new naieveindexer($index,$documentstore);
	$ret = $this->indexer->index(array('test test test'));
	$this->assertTrue($ret);
  }
  function testIndexTwoWordsTwiceCheckAssets() {
    $index = new Mockiindex();
	$documentstore = new Mockidocumentstore();

	$documentstore->expectOnce('storeDocument', array(array('test test test2 test2')));
	$documentstore->returns('storeDocument',1);
	$index->expectAt(0,'storeDocuments',array('test',array(array(1,2,0))));
	$index->expectAt(1,'storeDocuments',array('test2',array(array(1,2,0))));
	
	$this->indexer = new naieveindexer($index,$documentstore);
	$ret = $this->indexer->index(array('test test test2 test2'));
	$this->assertTrue($ret);
  }
  function testIndexThreeWordsTwoSameCheckAssets() {
    $index = new Mockiindex();
	$documentstore = new Mockidocumentstore();

	$documentstore->expectOnce('storeDocument', array(array('test test2 test2')));
	$documentstore->returns('storeDocument',1);
	$index->expectAt(0,'storeDocuments',array('test',array(array(1,1,0))));
	$index->expectAt(1,'storeDocuments',array('test2',array(array(1,2,0))));
	
	$this->indexer = new naieveindexer($index,$documentstore);
	$ret = $this->indexer->index(array('test test2 test2'));
	$this->assertTrue($ret);
  }
}
?>