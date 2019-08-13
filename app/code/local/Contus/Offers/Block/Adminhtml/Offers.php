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
class Contus_Offers_Block_Adminhtml_Offers extends Mage_Adminhtml_Block_Widget_Grid_Container {
    
    /**
     * Constructor initializes the controller and blocks
     */
    public function __construct() {
        $this->_controller = 'adminhtml_offers';
        $this->_blockGroup = 'offers';
        $this->_headerText = Mage::helper ( 'offers' )->__ ( 'Offers' );
        $this->_addButtonLabel = Mage::helper ( 'offers' )->__ ( 'Add Offer' );
        
        $this->_addButton('button1', array(
                'label'     => Mage::helper('offers')->__('Add Offer'),
                'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/checkCount') .'\')',
                'class'     => 'add',
        ));
       
        parent::__construct ();
        $this->_removeButton ( 'add' );
    }
}