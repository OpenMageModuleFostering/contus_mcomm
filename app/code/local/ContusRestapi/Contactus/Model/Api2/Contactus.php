<?php
/**
 * Contus
 * 
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
 * @package    ContusRestapi_Contactus
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Contactus_Model_Api2_Contactus extends Mage_Api2_Model_Resource {
    
    
    // Declaring the string literals variable
    const EMAIL = 'email';
    
    /**
     * function that is called when post is done **
     *
     * Contact us form
     *
     * @param array $data            
     *
     * @return array json array
     */
    protected function _create(array $data) {
        $response = array ();
        
        // get website id from request
        $websiteId = ( int ) $data ['website_id'];
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get store id from request
        $storeId = ( int ) $data ['store_id'];
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        
        try {
            $postObject = new Varien_Object ();
            $postObject->setData ( $data );
            
            $error = false;
          
            if (! Zend_Validate::is ( trim ( $data ['comment'] ), 'NotEmpty' )) {
                $error = true;
                $message = 'Message is required field';
            }
            
            if (! Zend_Validate::is ( trim ( $data [static::EMAIL] ), 'EmailAddress' )) {
                $error = true;
                $message = 'Email Address is required field';
            }
            
            if ($error) {
                throw new Exception ( $message );
            }
            $mailTemplate = Mage::getModel ( 'core/email_template' );
            /* @var $mailTemplate Mage_Core_Model_Email_Template */
            $mailTemplate->setDesignConfig ( array (
                    'area' => 'frontend' 
            ) )->setReplyTo ( $data [static::EMAIL] )->sendTransactional ( Mage::getStoreConfig ( 'contacts/email/email_template', $storeId ), Mage::getStoreConfig ( 'contacts/email/sender_email_identity', $storeId ), Mage::getStoreConfig ( 'contacts/email/recipient_email', $storeId ), null, array (
                    'data' => $postObject 
            ) );
            $mailTemplate->setStoreId ( $storeId );
            
            if (! $mailTemplate->getSentSuccess ()) {
                throw new Exception ( 'Unable to submit your request. Please, try again later' );
            }
            $success = 1;
            $message = 'Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.';
        } catch ( Exception $e ) {
            $success = 0;
            $message = $e->getMessage ();
        }
        
        $response ['error'] = false;
        $response ['success'] = $success;
        $response ['message'] = $message;
        
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Get Contact details about store
     *
     * @param array $data            
     *
     * @return array json array
     */
    protected function _retrieve() {
        $response = array ();
        $contactDetail = array ();
        
        // get website id from request
        $websiteId = ( int ) Mage::app ()->getRequest ()->getParam ( 'website_id' );
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        
        // get store id
        $storeId = ( int ) Mage::app ()->getRequest ()->getParam ( 'store_id' );
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        // get store name
        $contactDetail ['name'] = Mage::getStoreConfig ( 'general/store_information/name', $storeId );
        // get store phone number
        $contactDetail ['phone'] = Mage::getStoreConfig ( 'general/store_information/phone', $storeId );
        // get store address
        $contactDetail ['address'] = Mage::getStoreConfig ( 'general/store_information/address', $storeId );
        // get store general email address
        $contactDetail [static::EMAIL] = Mage::getStoreConfig ( 'trans_email/ident_general/email', $storeId );
        $response ['success'] = 1;
        $response ['error'] = false;
     
        $response ['result'] = $contactDetail;
        
        return $response;
    }
}