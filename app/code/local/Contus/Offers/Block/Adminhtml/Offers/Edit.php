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
class Contus_Offers_Block_Adminhtml_Offers_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {
    
    /**
     * Initialize controller, blockgroup and currently editable record id
     */
    public function __construct() {
        parent::__construct ();
        
        $this->_objectId = 'id';
        $this->_blockGroup = 'offers';
        $this->_controller = 'adminhtml_offers';
        
        $this->_updateButton ( 'save', 'label', Mage::helper ( 'offers' )->__ ( 'Save Item' ) );
        $this->_updateButton ( 'delete', 'label', Mage::helper ( 'offers' )->__ ( 'Delete Item' ) );
        
        $this->_addButton ( 'saveandcontinue', array (
                'label' => Mage::helper ( 'adminhtml' )->__ ( 'Save And Continue Edit' ),
                'onclick' => 'saveAndContinueEdit()',
                'class' => 'save' 
        ), - 100 );
        
        $this->_formScripts [] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('offers_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'offers_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'offers_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }
    
    /**
     * Set edit page header with offer title
     *
     * @return string Returns page header and Includes MCE
     */
    public function getHeaderText() {
        if (Mage::registry ( 'offers_data' ) && Mage::registry ( 'offers_data' )->getId ()) {
            return Mage::helper ( 'offers' )->__ ( "Edit offer '%s'", $this->htmlEscape ( Mage::registry ( 'offers_data' )->getOfferTitle () ) );
        } else {
            return Mage::helper ( 'offers' )->__ ( 'Add Offer' );
        }
    }
}