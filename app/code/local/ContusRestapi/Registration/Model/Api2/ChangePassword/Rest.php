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
 * @package    ContusRestapi_Registration
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Registration_Model_Api2_ChangePassword_Rest extends Mage_Customer_Model_Api2_Customer_Rest {
    const CUSTOMERMODEL = 'customer/customer';
    
    /**
     * function that is called when post is done **
     *
     * Change Customer Password
     *
     * @param array $data            
     *
     * @return array json array
     */
    protected function _create(array $data) {
        $response = array();
        $validate = 0;
        $message = '';
        $customerId = $data ['customer_id'];
        $customer = Mage::getModel ( static::CUSTOMERMODEL );
        $customer->load ( $customerId );
        
        // get website id value
        if ($data ['website_id']) {
            $websiteId = $data ['website_id'];
        } else {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( 'login/token' )->checkUserToken ( $customerId, $data ['token'] );
        $response ['isValidToken'] = true;
        try {
            $loginCheck = Mage::getModel ( static::CUSTOMERMODEL )->setWebsiteId ( $websiteId )->authenticate ( $customer ['email'], $data ['old_password'] );
            $validate = 1;
            
            if (! $isValidToken) {
                $response ['isValidToken'] = false;
                throw new Exception ( 'Authentication failed.' );
            }
        } catch ( Exception $ex ) {
            $validate = 0;
            $message = $ex->getMessage ();
            $loginCheck = false;
        }
        
        //If old password wrong return this error message.
        if(!$loginCheck){
            $message = 'Your old password is wrong. Please check your credentials.';
        }
        
        if ($validate == 1) {
            try {
                $customer = Mage::getModel ( static::CUSTOMERMODEL )->load ( $customerId );
                $customer->setPassword ( $data ['new_password'] );
                $customer->save ();
                $message = 'Your Password has been Changed Successfully';
            } catch ( Exception $ex ) {
                $message = $ex->getMessage ();
            }
        }
        $response ['error'] = false;
        $response ['success'] = $validate;
        $response [message] = $message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
}