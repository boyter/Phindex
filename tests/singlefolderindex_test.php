<?php
error_reporting(E_ALL); 
ini_set('display_errors', '1');

require_once(dirname(__FILE__) . '/simpletest/autorun.php');
require_once('../classes/singlefolderindex.class.php');

class test_singlefolderindex extends UnitTestCase {
  function setUp() {
    $indexloc = dirname(__FILE__).'/../index/tempindex/';
	if(!file_exists($indexloc)) {
	  mkdir($indexloc);
	}
    if(!defined('INDEXLOCATION')) {
	  define('INDEXLOCATION', $indexloc);
	}
    $this->index = new singlefolderindex();
  }
  function tearDown() {
    $this->index->clearIndex();
	$indexloc = dirname(__FILE__).'/../index/tempindex/';
	if(file_exists($indexloc)) {
	  closedir(opendir($indexloc)); // windows only hack to fix permission issues
	  rmdir($indexloc);
	}
  }
  
  function testStoreDocumentsReturnsAnythingButNull() {
	$ret = $this->index->storeDocuments(null,null);
	$this->assertNotNull($ret);
  }
  function testStoreDocumentsReturnsFalseWithAnyNullArguements() {
	$ret = $this->index->storeDocuments(null,null);
	$this->assertFalse($ret);
	$ret = $this->index->storeDocuments('',null);
	$this->assertFalse($ret);
  }
  function testStoreDocumentsReturnsFalseFirstArgumentNotString() {
	$ret = $this->index->storeDocuments('',array());
	$this->assertFalse($ret);
  }
  function testStoreDocumentsReturnsTrueSecondArgumentsArray() {
	$ret = $this->index->storeDocuments('test',array());
	$this->assertTrue($ret);
  }
  function testStoreDocumentsReturnsTrueSecondArgumentsArrayNotContainArray() {
	$ret = $this->index->storeDocuments('',array(1));
	$this->assertFalse($ret);
  }
  function testStoreDocumentsReturnsTrueSecondArgumentsArrayContainArrayNotLength3() {
	$ret = $this->index->storeDocuments('',array(array(1,2)));
	$this->assertFalse($ret);
  }
  function testStoreDocumentsReturnsTrueSecondArgumentsArrayContainArrayLength3() {
	$ret = $this->index->storeDocuments('test',array(array(1,2,3)));
	$this->assertTrue($ret);
  }
  function testStoreDocumentsReturnsTrueSecondArgumentsArrayContainArrayLength3SecondLength2() {
	$ret = $this->index->storeDocuments('',array(array(1,2,3),array(1,2)));
	$this->assertFalse($ret);
  }
  function testvalidateDocumentNullReturnsFalse() {
    $ret = $this->index->validateDocument(null);
	$this->assertFalse($ret);
  }
  function testvalidateDocumentEmptyArrayReturnFalse() {
    $ret = $this->index->validateDocument(array());
	$this->assertFalse($ret);
  }
  function testvalidateDocumentArrayLenNot3ReturnFalse() {
	$this->assertFalse($this->index->validateDocument(array(1)));
	$this->assertFalse($this->index->validateDocument(array(1,2)));
	$this->assertFalse($this->index->validateDocument(array(1,2,3,4)));
	$this->assertFalse($this->index->validateDocument(array(1,2,3,4,5)));
  }
  function testvalidateDocumentArrayLen3IntegersReturnTrue() {
    $this->assertTrue($this->index->validateDocument(array(1,2,3)));
	$this->assertTrue($this->index->validateDocument(array(1,223,3)));
	$this->assertTrue($this->index->validateDocument(array(11232,2,3)));
	$this->assertTrue($this->index->validateDocument(array(1,2,33232)));
  }
  function testvalidateDocumentArrayLen3MixedTypeReturnFalse() {
    $this->assertFalse($this->index->validateDocument(array(1,'',3)));
	$this->assertFalse($this->index->validateDocument(array(1,array(),3)));
	$this->assertFalse($this->index->validateDocument(array(1,false,3)));
	$this->assertFalse($this->index->validateDocument(array(1,2,true)));
  }
  
  function testvalidateDocumentDocumentIntergerBoundsCheck() {
    $this->assertTrue($this->index->validateDocument(array(PHP_INT_MAX,2,3)));
	$this->assertTrue($this->index->validateDocument(array(PHP_INT_MAX,PHP_INT_MAX,3)));
	$this->assertTrue($this->index->validateDocument(array(PHP_INT_MAX,PHP_INT_MAX,PHP_INT_MAX)));
	$this->assertTrue($this->index->validateDocument(array(PHP_INT_MAX-1,2,3)));
	$this->assertTrue($this->index->validateDocument(array(PHP_INT_MAX-1,PHP_INT_MAX-1,3)));
	$this->assertTrue($this->index->validateDocument(array(PHP_INT_MAX-1,PHP_INT_MAX-1,PHP_INT_MAX-1)));
	$this->assertFalse($this->index->validateDocument(array(PHP_INT_MAX+1,2,3)));
	$this->assertFalse($this->index->validateDocument(array(PHP_INT_MAX+1,PHP_INT_MAX+1,3)));
	$this->assertFalse($this->index->validateDocument(array(PHP_INT_MAX+1,PHP_INT_MAX+1,PHP_INT_MAX+1)));
	$this->assertFalse($this->index->validateDocument(array(1,PHP_INT_MAX+1,PHP_INT_MAX+1)));
	$this->assertFalse($this->index->validateDocument(array(1,1,PHP_INT_MAX+1)));
	$this->assertFalse($this->index->validateDocument(array(PHP_INT_MAX+100,2,3)));
	$this->assertFalse($this->index->validateDocument(array(0-PHP_INT_MAX,2,3)));
	$this->assertFalse($this->index->validateDocument(array(0-PHP_INT_MAX-1,2,3)));
	$this->assertFalse($this->index->validateDocument(array(0-PHP_INT_MAX-100,2,3)));
	$this->assertFalse($this->index->validateDocument(array(-1,2,3)));
	$this->assertFalse($this->index->validateDocument(array(1,-1,1)));
	$this->assertFalse($this->index->validateDocument(array(1,1,-1)));
	$this->assertFalse($this->index->validateDocument(array(-1,-1,-1)));
	$this->assertFalse($this->index->validateDocument(array(1,-1,-1)));
	$this->assertFalse($this->index->validateDocument(array(-1,-1,1)));
  }
 
  /*
   * Full unit tests exist above. The below are all "integration" tests since they
   * actually write to disk and then check the results. Should still be fast but because
   * of this if you run the tests concurrently things will probably break.
   *
   * Was thinking about abstracting out the writes using a virtual filesystem but
   * that would hide any issues caused by permissions which would be nice to capture here.
   */
  
  function testgetDocumentsNullExpectEmptyArray() {
	$ret = $this->index->getDocuments(null);
	$this->assertIsA($ret,'array');
  }
  function testgetDocumentsEmptyExpectEmptyArray() {
	$ret = $this->index->getDocuments('');
	$this->assertIsA($ret,'array');
  }
  function testStoreDocumentsFollowedByGetDocumentsExpectSingleDocument() {
    $documents = array(array(intval(1),intval(0),intval(0)));
	$ret = $this->index->storeDocuments('test',$documents);
	$ret = $this->index->getDocuments('test');
	$this->assertEqual(1,count($ret));
  }
  function testStoreDocumentsFollowedByGetDocumentsExpectSingleDocumentCheckValues() {
    $documents = array(array(intval(1),intval(2),intval(3)));
	$ret = $this->index->storeDocuments('test',$documents);
	$ret = $this->index->getDocuments('test');
	$this->assertEqual(intval(1),$ret[0][0]);
	$this->assertEqual(intval(2),$ret[0][1]);
	$this->assertEqual(intval(3),$ret[0][2]);
  }
  function testSGetDocumentsFileNotExistExpectEmptyArray() {
	$ret = $this->index->getDocuments('hopefulltthisshouldneverexist');
	$this->assertIsA($ret,'array');
	$this->assertEqual(0,count($ret));
  }
  function testStoreTwoDocumentsFollowedByGetDocumentsExpectTwoDocuments() {
    $documents = array(array(intval(1),intval(1),intval(1)),
	                   array(intval(2),intval(2),intval(2)));
	$ret = $this->index->storeDocuments('test',$documents);
	$ret = $this->index->getDocuments('test');
	$this->assertEqual(2,count($ret));
  }
  function testStoreTwoDocumentsFollowedByGetDocumentsExpectCheckValues() {
    $documents = array(array(intval(1),intval(1),intval(1)),
	                   array(intval(2),intval(2),intval(2)));
	$ret = $this->index->storeDocuments('test',$documents);
	$ret = $this->index->getDocuments('test');
	$this->assertEqual($ret[0][0],1);
	$this->assertEqual($ret[0][1],1);
	$this->assertEqual($ret[0][2],1);
	$this->assertEqual($ret[1][0],2);
	$this->assertEqual($ret[1][1],2);
	$this->assertEqual($ret[1][2],2);
  }
  function testStoreThreeDocumentsFollowedByGetDocumentsExpectCheckValues() {
    $documents = array(array(intval(1),intval(1),intval(1)),
	                   array(intval(2),intval(2),intval(2)),
					   array(intval(3),intval(3),intval(3)));
	$ret = $this->index->storeDocuments('test',$documents);
	$ret = $this->index->getDocuments('test');
	$this->assertEqual($ret[0][0],1);
	$this->assertEqual($ret[0][1],1);
	$this->assertEqual($ret[0][2],1);
	$this->assertEqual($ret[1][0],2);
	$this->assertEqual($ret[1][1],2);
	$this->assertEqual($ret[1][2],2);
	$this->assertEqual($ret[2][0],3);
	$this->assertEqual($ret[2][1],3);
	$this->assertEqual($ret[2][2],3);
  }
  function testStoreDocumentsOneFollowedByGetDocumentsExpectSingleDocumentCheckValues100Times() {
    $documents = array(array(intval(1),intval(2),intval(3)));
	$ret = $this->index->storeDocuments('test',$documents);
    for($i=0;$i<100;$i++) {
	  $ret = $this->index->getDocuments('test');
	  $this->assertEqual(intval(1),$ret[0][0]);
	  $this->assertEqual(intval(2),$ret[0][1]);
	  $this->assertEqual(intval(3),$ret[0][2]);
	}
  }
  function testStoreDocuments100TimesFollowedByGetDocumentsExpectSingleDocumentCheckValues() {
    for($i=0;$i<100;$i++) {
      $documents = array(array(intval(1),intval(2),intval(3)));
	  $ret = $this->index->storeDocuments('test',$documents);
    }
    $ret = $this->index->getDocuments('test');
	$this->assertEqual(intval(1),$ret[0][0]);
	$this->assertEqual(intval(2),$ret[0][1]);
	$this->assertEqual(intval(3),$ret[0][2]);
  }
  function testStoreDocumentsFollowedByGetDocumentsExpectSingleDocumentCheckValues100Times() {
    for($i=0;$i<100;$i++) {
      $documents = array(array(intval(1),intval(2),intval(3)));
	  $ret = $this->index->storeDocuments('test',$documents);
	  $ret = $this->index->getDocuments('test');
	  $this->assertEqual(intval(1),$ret[0][0]);
	  $this->assertEqual(intval(2),$ret[0][1]);
	  $this->assertEqual(intval(3),$ret[0][2]);
    }
  }
  function testStore100DocumentsCheckValues() {
    $documents = array();
    for($i=0;$i<100;$i++) {
      $documents[] = array(intval(1),intval(2),intval(3));
    }
	$ret = $this->index->storeDocuments('test',$documents);
	$ret = $this->index->getDocuments('test');
	$this->assertEqual(100,count($ret));
	foreach($documents as $doc) {
	  $this->assertEqual(intval(1),$doc[0]);
	  $this->assertEqual(intval(2),$doc[1]);
	  $this->assertEqual(intval(3),$doc[2]);
	}
  }
  function testStore1000DocumentsCheckValues() {
    $documents = array();
    for($i=0;$i<1000;$i++) {
      $documents[] = array(intval(1),intval(2),intval(3));
    }
	$ret = $this->index->storeDocuments('test',$documents);
	$ret = $this->index->getDocuments('test');
	$this->assertEqual(1000,count($ret));
	foreach($documents as $doc) {
	  $this->assertEqual(intval(1),$doc[0]);
	  $this->assertEqual(intval(2),$doc[1]);
	  $this->assertEqual(intval(3),$doc[2]);
	}
  }
  function testClearIndexClearSingleFile() {
    $this->index->storeDocuments('test',array(array(intval(1),intval(2),intval(3))));
	$this->index->clearIndex();
	$this->assertFalse(file_exists($this->index->_getFilePathName('test')));
  }
  function testClearIndexClearTwoFiles() {
    $this->index->storeDocuments('test',array(array(intval(1),intval(2),intval(3))));
	$this->index->storeDocuments('test2',array(array(intval(1),intval(2),intval(3))));
	$this->index->clearIndex();
	$this->assertFalse(file_exists($this->index->_getFilePathName('test')));
	$this->assertFalse(file_exists($this->index->_getFilePathName('test2')));
  }
  function testClearIndexClearRandomFiles100Checks() {
    for($i=0;$i<100;$i++){
      $this->index->storeDocuments(md5($i),array(array(intval(1),intval(2),intval(3))));
	  $this->index->clearIndex();
	  $this->assertFalse(file_exists($this->index->_getFilePathName($i)));
	}
  }
  function testgetDocumentsCorruptSingle4BytesFileExpectException() {
	$this->expectException(new Exception('Filesize not correct index is corrupt!'));
	$fp = fopen($this->index->_getFilePathName('test'),'w');
	$bindata1 = pack('i',intval(1));
	fwrite($fp,$bindata1);
	fclose($fp);
	$ret = $this->index->getDocuments('test');
  }
  function testgetDocumentsCorrupt8BytesFileExpectException() {
	$this->expectException(new Exception('Filesize not correct index is corrupt!'));
	$fp = fopen($this->index->_getFilePathName('test'),'w');
	$bindata1 = pack('i',intval(1));
	$bindata2 = pack('i',intval(1));
	fwrite($fp,$bindata1);
	fwrite($fp,$bindata2);
	fclose($fp);
	$ret = $this->index->getDocuments('test');
  }
  function testgetDocuments12BytesExpectReturnVals() {
	$fp = fopen($this->index->_getFilePathName('test'),'w');
	$bindata1 = pack('i',intval(1));
	$bindata2 = pack('i',intval(2));
	$bindata3 = pack('i',intval(3));
	fwrite($fp,$bindata1);
	fwrite($fp,$bindata2);
	fwrite($fp,$bindata3);
	fclose($fp);
	$ret = $this->index->getDocuments('test');
	$this->assertEqual(1,$ret[0][0]);
	$this->assertEqual(2,$ret[0][1]);
	$this->assertEqual(3,$ret[0][2]);
  }
  function testgetDocumentsCorrupt16BytesFileExpectException() {
	$this->expectException(new Exception('Filesize not correct index is corrupt!'));
	$fp = fopen($this->index->_getFilePathName('test'),'w');
	$bindata1 = pack('i',intval(1));
	$bindata2 = pack('i',intval(1));
	fwrite($fp,$bindata1);
	fwrite($fp,$bindata2);
	fwrite($fp,$bindata1);
	fwrite($fp,$bindata2);
	fclose($fp);
	$ret = $this->index->getDocuments('test');
  }
}
?>