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
class Contus_Offers_Block_Adminhtml_Offers_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {
    
    /**
     * Generate pre filled form details and save link for saving edited datas
     *
     * @return mixed Returns pre filled form details and save link
     */
    protected function _prepareForm() {
        $form = new Varien_Data_Form ( array (
                'id' => 'edit_form',
                'action' => $this->getUrl ( '*/*/save', array (
                        'id' => $this->getRequest ()->getParam ( 'id' ) 
                ) ),
                'method' => 'post',
                'enctype' => 'multipart/form-data' 
        ) );
        
        $form->setUseContainer ( true );
        $this->setForm ( $form );
        return parent::_prepareForm ();
    }
}