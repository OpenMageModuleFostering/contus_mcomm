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
class Contus_Offers_Block_Adminhtml_Offers_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
    /**
     * Initializing the default sort offer id for the grid
     */
    public function __construct() {
        parent::__construct ();
        $this->setId ( 'offersGrid' );
        $this->setDefaultSort ( 'offers_id' );
        $this->setDefaultDir ( 'ASC' );
        $this->setSaveParametersInSession ( true );
    }
    
    /**
     * It will fetch all offer details
     *
     * @return mixed Offer list collection
     */
    protected function _prepareCollection() {
        $collection = Mage::getModel ( 'offers/offers' )->getCollection ();
        $this->setCollection ( $collection );
        return parent::_prepareCollection ();
    }
    
    /**
     * It will prepare the fields to be displayed in Grid view
     *
     * @return mixed It will prepare the fields to be displayed in Grid view
     */
    protected function _prepareColumns() {
        $this->addColumn ( 'offers_id', array (
                'header' => Mage::helper ( 'offers' )->__ ( 'ID' ),
                'align' => 'right',
                'width' => '50px',
                'index' => 'offers_id' 
        ) );
        
        $this->addColumn ( 'offer_title', array (
                'header' => Mage::helper ( 'offers' )->__ ( 'Title' ),
                'align' => 'left',
                'index' => 'offer_title' 
        ) );
        
        $this->addColumn ( 'offer_img', array (
                'header' => Mage::helper ( 'offers' )->__ ( 'Promotion Image' ),
                'align' => 'left',
                'index' => 'offer_img',
                'renderer' => 'offers/adminhtml_offers_renderer_image',
                'attr1' => 'value1' 
        ) );
        
        $this->addColumn ( 'status', array (
                'header' => Mage::helper ( 'offers' )->__ ( 'Status' ),
                'align' => 'left',
                'width' => '80px',
                'index' => 'status',
                'type' => 'options',
                'options' => array (
                        1 => 'Enabled',
                        2 => 'Disabled' 
                ) 
        ) );
        
        $this->addColumn ( 'action', array (
                'header' => Mage::helper ( 'offers' )->__ ( 'Action' ),
                'width' => '100',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'offers' )->__ ( 'Edit' ),
                                'url' => array (
                                        'base' => '*/*/edit' 
                                ),
                                'field' => 'id' 
                        ) 
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true 
        ) );
        
        $this->addColumn ( 'Notification', array (
                'header' => Mage::helper ( 'offers' )->__ ( 'Notification' ),
                'index' => 'Push',
                'filter' => false,
                'renderer' => 'offers/adminhtml_offers_renderer_notification',
                'actions' => array (
                        array (
                                'caption' => Mage::helper ( 'offers' )->__ ( 'Push' ),
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
        
        $this->addExportType ( '*/*/exportCsv', Mage::helper ( 'offers' )->__ ( 'CSV' ) );
        $this->addExportType ( '*/*/exportXml', Mage::helper ( 'offers' )->__ ( 'XML' ) );
        
        return parent::_prepareColumns ();
    }
    
    /**
     * Mass edit action
     *
     * @return mixed Returns updated collection values
     */
    protected function _prepareMassaction() {
        $this->setMassactionIdField ( 'offers_id' );
        $this->getMassactionBlock ()->setFormFieldName ( 'offers' );
        
        $this->getMassactionBlock ()->addItem ( 'delete', array (
                'label' => Mage::helper ( 'offers' )->__ ( 'Delete' ),
                'url' => $this->getUrl ( '*/*/massDelete' ),
                'confirm' => Mage::helper ( 'offers' )->__ ( 'Are you sure?' ) 
        ) );
        
        $statuses = Mage::getSingleton ( 'offers/status' )->getOptionArray ();
        
        array_unshift ( $statuses, array (
                'label' => '',
                'value' => '' 
        ) );
        $this->getMassactionBlock ()->addItem ( 'status', array (
                'label' => Mage::helper ( 'offers' )->__ ( 'Change status' ),
                'url' => $this->getUrl ( '*/*/massStatus', array (
                        '_current' => true 
                ) ),
                'additional' => array (
                        'visibility' => array (
                                'name' => 'status',
                                'type' => 'select',
                                'class' => 'required-entry',
                                'label' => Mage::helper ( 'offers' )->__ ( 'Status' ),
                                'values' => $statuses 
                        ) 
                ) 
        ) );
        return $this;
    }
    
    /**
     * Returns current row url to be edited
     *
     * @param id $row
     *            Current row id
     *            
     * @return string Returns edit url
     */
    public function getRowUrl($row) {
    }
}