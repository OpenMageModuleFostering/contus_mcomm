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
 * @package    Contus_Pushnoteorders
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class Contus_Pushnoteorders_Block_Adminhtml_Pushnoteorders_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct() {
        parent::__construct ();
        $this->setId ( 'pushnoteordersGrid' );
        $this->setDefaultSort ( 'pushnoteorders_id' );
        $this->setDefaultDir ( 'ASC' );
        $this->setSaveParametersInSession ( true );
    }
    
    /**
     * Retrieve collection class
     *
     * @return string
     */
    protected function _getCollectionClass() {
        return 'sales/order_grid_collection';
    }
    protected function _prepareCollection() {
        $collection = Mage::getResourceModel ( $this->_getCollectionClass () );
        $this->setCollection ( $collection );
        return parent::_prepareCollection ();
    }
    protected function _prepareColumns() {
        $this->addColumn ( 'real_order_id', array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Order #' ),
                'width' => '80px',
                'type' => 'text',
                'index' => 'increment_id' 
        ) );
        
        if (! Mage::app ()->isSingleStoreMode ()) {
            $this->addColumn ( 'store_id', array (
                    'header' => Mage::helper ( 'sales' )->__ ( 'Purchased From (Store)' ),
                    'index' => 'store_id',
                    'type' => 'store',
                    'store_view' => true,
                    'display_deleted' => true 
            ) );
        }
        
        $this->addColumn ( 'created_at', array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Purchased On' ),
                'index' => 'created_at',
                'type' => 'datetime',
                'width' => '100px' 
        ) );
        
        $this->addColumn ( 'billing_name', array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Bill to Name' ),
                'index' => 'billing_name' 
        ) );
        
        $this->addColumn ( 'shipping_name', array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Ship to Name' ),
                'index' => 'shipping_name' 
        ) );
        
        $this->addColumn ( 'base_grand_total', array (
                'header' => Mage::helper ( 'sales' )->__ ( 'G.T. (Base)' ),
                'index' => 'base_grand_total',
                'type' => 'currency',
                'currency' => 'base_currency_code' 
        ) );
        
        $this->addColumn ( 'grand_total', array (
                'header' => Mage::helper ( 'sales' )->__ ( 'G.T. (Purchased)' ),
                'index' => 'grand_total',
                'type' => 'currency',
                'currency' => 'order_currency_code' 
        ) );
        
        $this->addColumn ( 'status', array (
                'header' => Mage::helper ( 'sales' )->__ ( 'Status' ),
                'index' => 'status',
                'type' => 'options',
                'width' => '70px',
                'options' => Mage::getSingleton ( 'sales/order_config' )->getStatuses () 
        ) );
        
        $this->addColumn ( 'Notification', array (
                'header' => Mage::helper ( 'pushnoteproducts' )->__ ( 'Notification' ),
                'index' => 'Push',
                'filter' => false,
                'renderer' => 'pushnoteorders/adminhtml_pushnoteorders_renderer_notification',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'pushnoteorders' )->__ ( 'Push' ),
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
                'renderer' => 'pushnoteorders/adminhtml_pushnoteorders_renderer_view',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'pushnoteorders' )->__ ( 'View' ),
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
        $this->setMassactionIdField ( 'pushnoteorders_id' );
        $this->getMassactionBlock ()->setFormFieldName ( 'pushnoteorders' );
        
        return $this;
    }
    public function getRowUrl($row) {
    }
}