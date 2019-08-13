<?php
/**
 * Token table installation script
 *
 * @category    ContusRestapi
 * @package     ContusRestapi_Login
 */
/**
 *
 * @var $installer Mage_Core_Model_Resource_Setup
 */

$installer = $this;

$installer->run ( "DROP TABLE IF EXISTS " . $installer->getTable ( 'login/token' ) );

$token = $installer->getConnection ()->newTable ( $installer->getTable ( 'login/token' ) )->addColumn ( 'tokenid', Varien_Db_Ddl_Table::TYPE_INTEGER, NULL, array (
        'unsigned' => TRUE,
        'identity' => TRUE,
        'nullable' => FALSE,
        'primary' => TRUE 
), 'Token Auto Id' )->addColumn ( 'userid', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array (
        'unsigned' => TRUE,
        'nullable' => FALSE 
), 'Userid' )->addColumn ( 'token', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array (
        'nullable' => TRUE,
        'default' => NULL 
), 'Token' )->addColumn ( 'devicetoken', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array (
        'nullable' => TRUE,
        'default' => NULL 
), 'Devicetoken' )->addColumn ( 'devicetype', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array (
        'nullable' => TRUE,
        'default' => NULL 
), 'Devicetype' )->addColumn ( 'created', Varien_Db_Ddl_Table::TYPE_DATETIME, NULL, array (), 'Created Date' )->addColumn ( 'status', Varien_Db_Ddl_Table::TYPE_INTEGER, 2, array (
        'unsigned' => TRUE,
        'nullable' => FALSE 
), 'Status' );

$installer->getConnection ()->createTable ( $token );