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
 * @package    Contus_Offers
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class Contus_Offers_Block_Adminhtml_Offers_Edit_Tab_Productlist extends Mage_Adminhtml_Block_Widget_Grid {
    
    /**
     * Initializing the default sort entity_id for the grid
     */
    public function __construct() {
        parent::__construct ();
        $this->setId ( 'catalog_category_products' );
        $this->setDefaultSort ( 'entity_id' );
        $this->setDefaultDir ( 'DESC' );
        $this->setSaveParametersInSession ( true );
    }
    protected function _prepareCollection() {
        // get these type of products only
        $productType = array (
                "simple",
                "configurable" 
        );
        $collection = Mage::getModel ( 'catalog/product' )->getCollection ()->addAttributeToFilter ( 'visibility', array (
                'neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE 
        ) )->addAttributeToFilter ( 'type_id', array (
                'in' => $productType 
        ) )->addAttributeToSelect ( 'name' )->addAttributeToSelect ( 'sku' )->addAttributeToSelect ( 'price' )->addAttributeToFilter ( 'visibility', 4 )->addStoreFilter ( $this->getRequest ()->getParam ( 'store' ) );
        $this->setCollection ( $collection );
        
        return parent::_prepareCollection ();
    }
    
    /**
     * Defines Form field to be edited
     *
     * @return object Form field to be edited
     */
    protected function _prepareColumns() {
        $this->addColumn ( 'offer_products', array (
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'offer_products[]',
                'values' => $this->_getSelectedProducts (),
                'align' => 'center',
                'index' => 'entity_id',
                'field_name' => 'offer_products[]' 
        ) );
        
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
        
        $this->addColumn ( 'Action', array (
                'header' => Mage::helper ( 'offers' )->__ ( 'Action' ),
                'index' => 'Push',
                'filter' => false,
                'renderer' => 'offers/adminhtml_offers_renderer_view',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'offers' )->__ ( 'View' ),
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
        
        return parent::_prepareColumns ();
    }
    
    /**
     * Get products related to offer
     *
     * @return array as product ids
     */
    protected function _getSelectedProducts() {
        $data = Mage::registry ( 'offers_data' );
        $event_products = $data->getData ( 'offer_products' );
        
        if (is_null ( $event_products )) {
            return array_keys ( $event_products );
        }
        return $event_products;
    }
    protected function _prepareMassaction() {
        $this->setMassactionIdField ( 'entity_id' );
        $this->getMassactionBlock ()->setFormFieldName ( 'offer_products[]' );
        
        return $this;
    }
}