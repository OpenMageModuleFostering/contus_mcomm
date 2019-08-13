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
class Contus_Offers_Block_Adminhtml_Offers_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {
   
   /**
    * Defines Form field to be edited
    *
    * @return object Form field to be edited
    */
   protected function _prepareForm() {
      $form = new Varien_Data_Form ();
      $this->setForm ( $form );
      $fieldset = $form->addFieldset ( 'offers_form', array (
            'legend' => Mage::helper ( 'offers' )->__ ( 'Offer information' ) 
      ) );
      
      $fieldset->addField ( 'offer_title', 'text', array (
            'label' => Mage::helper ( 'offers' )->__ ( 'Offer Title' ),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'offer_title' 
      ) );
      
      // display Promotion image if already exit
      $data = Mage::registry ( 'offers_data' );
      
      if ($data->getData ( 'offer_img' ) != '') {
         $html = '';
         $url = Mage::getBaseUrl ( 'media' );
         $html = '<img ';
         $html .= 'src="' . $url . $data->getData ( 'offer_img' ) . '"' . 'width = "60px" />';
         Mage::registry ( 'offers_data' )->setData ( 'offer_img_view', $data->getData ( 'offer_img' ) );
         
         $fieldset->addField ( 'offer_img_view', 'text', array (
               'label' => '',
               'required' => false,
               'style' => "display:none",
               'name' => "offer_img_view",
               'after_element_html' => $html 
         ) );
         
         $fieldset->addField ( 'offer_img', 'file', array (
               'label' => Mage::helper ( 'offers' )->__ ( 'Promotion Image' ),
               'required' => false,
               'name' => 'offer_img',
               'after_element_html' => '<small style="display:block">Upload image size (1200 X 900 pixels)<small>' 
         ) );
      } else {
         $fieldset->addField ( 'offer_img', 'file', array (
               'label' => Mage::helper ( 'offers' )->__ ( 'Promotion Image ' ),
               'required' => true,
               'name' => 'offer_img',
               'class' => 'required-entry required-file',
               'after_element_html' => '<small style="display:block">Upload image size (1200 X 900 pixels)<small>' 
         ) );
      }
      
      $fieldset->addField ( 'store_id', 'multiselect', array (
            'name' => 'stores[]',
            'label' => Mage::helper ( 'offers' )->__ ( 'Store View' ),
            'title' => Mage::helper ( 'offers' )->__ ( 'Store View' ),
            'required' => true,
            'values' => Mage::getSingleton ( 'adminhtml/system_store' )->getStoreValuesForForm ( false, true ) 
      ) );
      
      $fieldset->addField ( 'from_date', 'date', array (
            'name' => 'from_date',
            'label' => Mage::helper ( 'offers' )->__ ( 'From Date' ),
            'style' => 'width:200px;',
            'image' => $this->getSkinUrl ( 'images/grid-cal.gif' ),
            'format' => Mage::app ()->getLocale ()->getDateFormat ( Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM ),
            'required' => false,
            'class' => ' validate-date validate-date-range date-range-to_date-from' 
      ) );
      
      $fieldset->addField ( 'to_date', 'date', array (
            'name' => 'to_date',
            'label' => Mage::helper ( 'offers' )->__ ( 'To Date' ),
            'style' => 'width:200px;',
            'image' => $this->getSkinUrl ( 'images/grid-cal.gif' ),
            'format' => Mage::app ()->getLocale ()->getDateFormat ( Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM ),
            'required' => false,
            'class' => 'validate-date validate-date-range date-range-to_date-to' 
      ) );
      
      $fieldset->addField ( 'status', 'select', array (
            'label' => Mage::helper ( 'offers' )->__ ( 'Status' ),
            'name' => 'status',
            'values' => array (
                  array (
                        'value' => 1,
                        'label' => Mage::helper ( 'offers' )->__ ( 'Enabled' ) 
                  ),
                  
                  array (
                        'value' => 2,
                        'label' => Mage::helper ( 'offers' )->__ ( 'Disabled' ) 
                  ) 
            ) 
      ) );
      
      if (Mage::getSingleton ( 'adminhtml/session' )->getOffersData ()) {
         $form->setValues ( Mage::getSingleton ( 'adminhtml/session' )->getOffersData () );
         Mage::getSingleton ( 'adminhtml/session' )->setOffersData ( null );
      } elseif (Mage::registry ( 'offers_data' )) {
         $form->setValues ( Mage::registry ( 'offers_data' )->getData () );
      }
      return parent::_prepareForm ();
   }
}