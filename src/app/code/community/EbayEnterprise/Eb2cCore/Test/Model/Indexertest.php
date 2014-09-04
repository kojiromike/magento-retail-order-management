<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Eb2cCore_Test_Model_Indexertest extends EbayEnterprise_Eb2cCore_Test_Base
{
	private $_mockEnterpriseIndexer;
	private $_mockOneProcess;
	private $_mockOneIndexer;

	public function setUp()
	{
		// Indexer belongs to a Process
		$this->_mockOneIndexer = $this->getModelMock('enterprise_catalogsearch/indexer_fulltext', array('isVisible'));
		$this->_mockOneIndexer
			->expects($this->once())
			->method('isVisible')
			->will($this->returnValue(true));
		$this->replaceByMock('model', 'enterprise_catalogsearch/indexer_fulltext', $this->_mockOneIndexer);

		// A process is a single instance of index work to be done
		$this->_mockOneProcess = $this->getModelMockBuilder('enterprise_index/process')
			->setMethods(array('getIndexer', 'getIndexerCode', 'reindexEverything'))
			->getMock();
		$this->_mockOneProcess
			->expects($this->any())
			->method('getIndexer')
			->will($this->returnValue($this->_mockOneIndexer));
		$this->_mockOneProcess
			->expects($this->once())
			->method('getIndexerCode')
			->will($this->returnValue('catalogsearch_fulltext'));
		$this->_mockOneProcess
			->expects($this->once())
			->method('reindexEverything')
			->will($this->returnValue(true));
		$this->replaceByMock('model', 'enterprise_index/process', $this->_mockOneProcess);

		// getProcessesCollection returns an associative array of Process
		$this->_mockEnterpriseIndexer = $this->getModelMock('enterprise_index/indexer', array('getProcessesCollection'));
		$this->_mockEnterpriseIndexer
			->expects($this->any())
			->method('getProcessesCollection')
			->will($this->returnValue(array('catalogsearch_fulltext' => $this->_mockOneProcess)));
		$this->replaceByMock('model', 'enterprise_index/indexer', $this->_mockEnterpriseIndexer);
	}

	/**
	 * Test fire init, after, and finalize dispatchers given a single indexer collection
	 *
	 * @loadFixture testDispatchers.yaml
	 */
	public function testDispatchers()
	{
		$indexer = Mage::getModel('eb2ccore/indexer');
		$indexer->reindexAll();
		$this->assertEventDispatchedExactly('shell_reindex_init_process', 1);
		$this->assertEventDispatchedExactly('shell_reindex_finalize_process', 1);
		$this->assertEventDispatchedExactly('catalogsearch_fulltext_shell_reindex_after', 1);
	}

	/**
	 * Dispatched to this function while mocking a reindexAll, init phase
	 */
	public function mockShellReindexInitProcess()
	{
		return $this;
	}

	/**
	 * Dispatched to this function while mocking a reindexAll, finalize phase
	 */
	public function mockShellReindexFinalizeProcess()
	{
		return $this;
	}

	/**
	 * Dispatched to this function while mocking a reindexAll, after an individual index is built
	 */
	public function mockCatalogSearchFullTextAfterProcess()
	{
		return $this;
	}
}
