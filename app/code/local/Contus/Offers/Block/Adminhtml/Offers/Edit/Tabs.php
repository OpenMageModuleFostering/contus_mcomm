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
class Contus_Offers_Block_Adminhtml_Offers_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {
    
    /**
     * Sets sidebar labels
     */
    public function __construct() {
        parent::__construct ();
        $this->setId ( 'offers_tabs' );
        $this->setDestElementId ( 'edit_form' );
        $this->setTitle ( Mage::helper ( 'offers' )->__ ( 'Offers Information' ) );
    }
    
    /**
     * Returns edit tab contents
     *
     * @return string Returns edit tab content
     */
    protected function _beforeToHtml() {
        $this->addTab ( 'form_section', array (
                'label' => Mage::helper ( 'offers' )->__ ( 'Offer Information' ),
                'title' => Mage::helper ( 'offers' )->__ ( 'Offer Information' ),
                'content' => $this->getLayout ()->createBlock ( 'offers/adminhtml_offers_edit_tab_form' )->toHtml () 
        ) );
        
        $this->addTab ( 'form_section1', array (
                'label' => Mage::helper ( 'offers' )->__ ( 'Products' ),
                'title' => Mage::helper ( 'offers' )->__ ( 'Products' ),
                'content' => $this->getLayout ()->createBlock ( 'offers/adminhtml_offers_edit_tab_productlist' )->toHtml () 
        ) );
        
        return parent::_beforeToHtml ();
    }
}