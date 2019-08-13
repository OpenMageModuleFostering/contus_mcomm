<?php
/**
 * Contus
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category   Contus
 * @package    Contus_PushnoteProducts
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class Contus_PushnoteProducts_Block_Adminhtml_PushnoteProducts_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    /**
     * Initializing the default sort entity_id for the grid
     */
    public function __construct() {
        parent::__construct ();
        $this->setId ( 'pushnoteproductsGrid' );
        $this->setDefaultSort ( 'pushnoteproducts_id' );
        $this->setDefaultDir ( 'ASC' );
        $this->setSaveParametersInSession ( true );
    }
    protected function _prepareCollection() {
        $collection = Mage::getModel ( 'catalog/product' )->getCollection ()->addAttributeToSelect ( 'name' )->addAttributeToSelect ( 'sku' )->addAttributeToSelect ( 'price' )->addAttributeToFilter ( 'visibility', 4 )->addAttributeToSelect ( 'status' )->addStoreFilter ( $this->getRequest ()->getParam ( 'store' ) );
        $this->setCollection ( $collection );
        
        return parent::_prepareCollection ();
    }
    
    /**
     * Defines Form field to be edited
     *
     * @return object Form field to be edited
     */
    protected function _prepareColumns() {
        $this->addColumn ( 'entity_id', array (
                'header' => Mage::helper ( 'catalog' )->__ ( 'ID' ),
                'sortable' => true,
                'width' => '60',
                'index' => 'entity_id' 
        ) );
        
        $this->addColumn ( 'name', array (
                'header' => Mage::helper ( 'catalog' )->__ ( 'Name' ),
                'index' => 'name' 
        ) );
        $this->addColumn ( 'sku', array (
                'header' => Mage::helper ( 'catalog' )->__ ( 'SKU' ),
                'width' => '80',
                'index' => 'sku' 
        ) );
        $this->addColumn ( 'price', array (
                'header' => Mage::helper ( 'catalog' )->__ ( 'Price' ),
                'type' => 'currency',
                'width' => '1',
                'currency_code' => ( string ) Mage::getStoreConfig ( Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE ),
                'index' => 'price' 
        ) );
        $this->addColumn ( 'visibility', array (
                'header' => Mage::helper ( 'pushnoteproducts' )->__ ( 'Visibility' ),
                'width' => '70px',
                'index' => 'visibility',
                'type' => 'options',
                'options' => Mage::getModel ( 'catalog/product_visibility' )->getOptionArray () 
        ) );
        
        $this->addColumn ( 'status', array (
                'header' => Mage::helper ( 'pushnoteproducts' )->__ ( 'Status' ),
                'width' => '70px',
                'index' => 'status',
                'type' => 'options',
                'options' => Mage::getSingleton ( 'catalog/product_status' )->getOptionArray () 
        ) );
        
        $this->addColumn ( 'Notification', array (
                'header' => Mage::helper ( 'pushnoteproducts' )->__ ( 'Notification' ),
                'index' => 'Push',
                'filter' => false,
                'renderer' => 'pushnoteproducts/adminhtml_pushnoteproducts_renderer_notification',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'pushnoteproducts' )->__ ( 'Push' ),
                                'url' => array (
                                        'base' => '*/',
                                        'params' => array (
                                                'store' => $this->getRequest ()->getParam ( 'store' ) 
                                        ) 
                                ),
                                'field' => 'id',
                                'target' => '_blank' 
                        ) 
                ) 
        ) );
        
        $this->addColumn ( 'Action', array (
                'header' => Mage::helper ( 'pushnoteproducts' )->__ ( 'Action' ),
                'index' => 'Push',
                'filter' => false,
                'renderer' => 'pushnoteproducts/adminhtml_pushnoteproducts_renderer_view',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'pushnoteproducts' )->__ ( 'View' ),
                                'url' => array (
                                        'base' => '*/',
                                        'params' => array (
                                                'store' => $this->getRequest ()->getParam ( 'store' ) 
                                        ) 
                                ),
                                'field' => 'id',
                                'target' => '_blank' 
                        ) 
                ) 
        ) );
        if (Mage::helper ( 'catalog' )->isModuleEnabled ( 'Mage_Rss' )) {
            $this->addRssList ( 'rss/catalog/notifystock', Mage::helper ( 'catalog' )->__ ( 'Notify Low Stock RSS' ) );
        }
        
        return parent::_prepareColumns ();
    }
    
    /**
     * Get store id
     * 
     * @return int $storeId
     */
    protected function _getStore() {
        $storeId = ( int ) $this->getRequest ()->getParam ( 'store', 0 );
        return Mage::app ()->getStore ( $storeId );
    }
    
    /**
     * Mass edit action
     *
     * @return mixed Returns updated collection values
     */
    protected function _prepareMassaction() {
        $this->setMassactionIdField ( 'pushnoteproducts_id' );
        $this->getMassactionBlock ()->setFormFieldName ( 'pushnoteproducts' );
        
        return $this;
    }
    public function getRowUrl($row) {
    }
}