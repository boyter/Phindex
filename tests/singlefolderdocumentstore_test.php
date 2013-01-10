<?php
error_reporting(E_ALL); 
ini_set('display_errors', '1');

require_once(dirname(__FILE__) . '/simpletest/autorun.php');
require_once('../classes/singlefolderdocumentstore.class.php');

class test_singlefolderDocumentstore extends UnitTestCase {
  function setUp() {
    $docloc = dirname(__FILE__).'/../documents/tempDocument/';
	if(!file_exists($docloc)) {
	  mkdir($docloc);
	}
    if(!defined('DOCUMENTLOCATION')) {
	  define('DOCUMENTLOCATION', $docloc);
	}
    $this->docstore = new singlefolderdocumentstore();
  }
  function tearDown() {
    $this->docstore->clearDocuments();
	$docloc = dirname(__FILE__).'/../documents/tempDocument/';
	if(file_exists($docloc)) {
	  closedir(opendir($docloc)); // windows only hack to fix permission issues
	  rmdir($docloc);
	}
  }
  
  function testStoreDocumentReturnsAnythingButNull() {
	$ret = $this->docstore->storeDocument(null);
	$this->assertNotNull($ret);
  }
  function testStoreDocumentSuppliedNullReturnsFalse() {
	$ret = $this->docstore->storeDocument(null);
	$this->assertFalse($ret);
  }
  function testStoreDocumentSuppliedEmptyArrayReturnsFalse() {
	$ret = $this->docstore->storeDocument(array());
	$this->assertFalse($ret);
  }
  function testStoreDocumentSuppliedArrayWithElementReturnsTrue() {
	$ret = $this->docstore->storeDocument(array(1));
	$this->assertEqual(0,$ret);
  }
  function testStoreDocumentSuppliedArrayWithElementsReturnsTrue() {
	$ret = $this->docstore->storeDocument(array(1,1));
	$this->assertEqual(0,$ret);
  }
  function testStoreDocumentSuppliedArrayWithMixedElementsReturnsTrue() {
	$ret = $this->docstore->storeDocument(array(1,'test',true,false,array()));
	$this->assertEqual(0,$ret);
  }
  function testGetDocumentSuppliedNullReturnsNull() {
	$ret = $this->docstore->getDocument(null);
	$this->assertNull($ret);
  }
  function testGetDocumentSuppliedAnythingButIntegerReturnsNull() {
	$this->assertNull($this->docstore->getDocument(null));
	$this->assertNull($this->docstore->getDocument(''));
	$this->assertNull($this->docstore->getDocument('test'));
	$this->assertNull($this->docstore->getDocument(array()));
	$this->assertNull($this->docstore->getDocument(false));
  }
  function testGetDocumentSuppliedNegativeIntegerReturnsNull() {
	$this->assertNull($this->docstore->getDocument(-1));
  }
  function testGetDocumentBoundsChecks() {
    // TODO expand on these to really test the bounds.... 
	// only for completeness though
	$this->assertNull($this->docstore->getDocument(PHP_INT_MAX+1));
	$this->assertNull($this->docstore->getDocument(PHP_INT_MAX+100));
  }
  
  /*
   * Full unit tests exist above. The below are all "integration" tests since they
   * actually write to disk and then check the results. Should still be fast but because
   * of this if you run the tests concurrently things will probably break.
   *
   * Was thinking about abstracting out the writes using a virtual filesystem but
   * that would hide any issues caused by permissions which would be nice to capture here.
   */
   
  function testGetNextDocumentIdNeverReturnsSame1000Checks() {
    for($i=0;$i<1000;$i++) {
      $this->assertNotEqual($this->docstore->_getNextDocumentId(),
	                        $this->docstore->_getNextDocumentId());
	}
  }
  function testStoreDocumentFollowedByGetDocumentReturnsCorrectInts() {
    $value = array(1,2,3,4);
    $docid = $this->docstore->storeDocument($value);
  	$ret = $this->docstore->getDocument($docid);
	$this->assertIsA($ret,'array');
	$this->assertTrue(count($ret) > 0);	
	$this->assertEqual($value,$ret);
  }
  function testStoreDocumentFollowedByGetDocumentReturnsCorrectMixedValues() {
    $value = array(1,'string',null,false,2.43);
    $docid = $this->docstore->storeDocument($value);
  	$ret = $this->docstore->getDocument($docid);
	$this->assertIsA($ret,'array');
	$this->assertTrue(count($ret) > 0);	
	$this->assertEqual($value,$ret);
  }
  function testStoreDocumentFollowedByGetDocumentReturnsCorrectMixedValues1000Checks() {
    $value = array(1,'string',null,false,2.43);
	for($i=0;$i<1000;$i++) {
      $docid = $this->docstore->storeDocument($value);
  	  $ret = $this->docstore->getDocument($docid);
	  $this->assertIsA($ret,'array');
	  $this->assertTrue(count($ret) > 0);	
	  $this->assertEqual($value,$ret);
	}
  }
  function testStoreDocumentFollowedByClearGetDocumentReturnsNull() {
    $value = array(1,'string',null,false,2.43);
    $docid = $this->docstore->storeDocument($value);
	$this->docstore->clearDocuments();
	$ret = $this->docstore->getDocument($docid);
	$this->assertNull($ret);
  }
}
?>