<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */

$installer = $this;
$installer->startSetup();
try{
	/**
	 * Create table 'eb2cinventory/details'
	 */
	$table = $installer->getConnection()
		->newTable($installer->getTable('eb2cinventory/details'))
		->addColumn('detail_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
			'identity' => true,
			'unsigned' => true,
			'nullable' => false,
			'primary' => true,
		), 'Detail Id')
		->addColumn('item_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
			'unsigned' => true,
		), 'Quote Item Id')
		->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'Created At')
		->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'Updated At')
		->addColumn('display', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'eb2c display')
		->addColumn('creation_time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'eb2c creation time')
		->addColumn('delivery_window_from', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'eb2c delivery window from')
		->addColumn('delivery_window_to', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'eb2c delivery window to')
		->addColumn('shipping_window_from', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'eb2c shipping window from')
		->addColumn('shipping_window_to', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
		), 'eb2c shipping window to')
		->addColumn('ship_from_address_line1', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'eb2c ship from address line1')
		->addColumn('ship_from_address_city', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'eb2c ship from address city')
		->addColumn('ship_from_address_main_division', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'eb2c ship from address main division')
		->addColumn('ship_from_address_country_code', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'eb2c ship from address country code')
		->addColumn('ship_from_address_postal_code', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		), 'eb2c ship from address postal code')
		->addForeignKey($installer->getFkName('eb2cinventory/details', 'item_id', 'sales/quote_item', 'item_id'),
			'item_id', $installer->getTable('sales/quote_item'), 'item_id',
		Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
		->setComment('Eb2c Inventory Details');
	$installer->getConnection()->createTable($table);

	$installer->endSetup();
} catch (Exception $e) {
	Mage::logException($e);
}
