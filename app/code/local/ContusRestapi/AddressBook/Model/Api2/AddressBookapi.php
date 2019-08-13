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
 * @package    ContusRestapi_AddressBook
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_AddressBook_Model_Api2_AddressBookapi extends Mage_Customer_Model_Api2_Customer_Address_Rest {
    
    // define static variable
    const STOREID = 'store_id';
    const WEBSITE_ID = 'website_id';
    const CUSTOMER_ID = 'customer_id';
    const ADDRESS_ID = 'address_id';
    const STREET = 'street';
    const COUNTRY_ID = 'country_id';
    const REGION = 'region';
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const ERROR = 'error';
    const RESULT = 'result';
    const TOKEN = 'token';
    const LOGIN_TOKEN = 'login/token';
    const VALID_TOKEN = 'isValidToken';
    const AUTH_FAIL = 'Authentication failed.';
    const ENTITY_ID = 'entity_id';
    
    /**
     * Add New Address to Customer Address Book
     *
     * @param array $data            
     * @return array json array
     */
    protected function _create(array $data) {
        $response = array ();
        // get website id
        $websiteId = ( int ) $data [static::WEBSITE_ID];
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get website id from request
        $storeId = ( int ) $data [static::STOREID];
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        // set street address as array
        $data [static::STREET] = json_decode ( '["' . ($data [static::STREET]) . '"]' );
        $customerId = ( int ) $data [static::CUSTOMER_ID];
        // get token
        $token = $data [static::TOKEN];
        
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = $this->_loadCustomerById ( $customerId );
        $validator = $this->_getValidator ();
        
        $data = $validator->filter ( $data );
        if (! $validator->isValidData ( $data ) || ! $validator->isValidDataForCreateAssociationWithCountry ( $data )) {
            foreach ( $validator->getErrors () as $error ) {
                $this->_error ( $error, Mage_Api2_Model_Server::HTTP_BAD_REQUEST );
                $message = $error;
                $success = 0;
            }
        }
        
        if (isset ( $data [static::REGION] ) && isset ( $data [static::COUNTRY_ID] )) {
            $data [static::REGION] = $this->_getRegionIdByNameOrCode ( $data [static::REGION], $data [static::COUNTRY_ID] );
        }
        
        /* @var $address Mage_Customer_Model_Address */
        $address = Mage::getModel ( 'customer/address' );
        $address->setData ( $data );
        $address->setCustomer ( $customer );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $addressData = array ();
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $token );
        if ($isValidToken) {
            try {
                $address->save ();
                $success = 1;
                $message = 'The address has been saved.';
                // get address by customer id
                $addressData = $this->_getAddressList ( $customerId, $storeId );
                // assign address to result tag
                $response [static::RESULT] = $addressData;
            } catch ( Mage_Core_Exception $e ) {
                $success = 0;
                $message = $e->getMessage ();
            }
            $this->_getLocation ( $address );
        } else {
            $success = 0;
            $message = static::AUTH_FAIL;
        }
        $response [static::VALID_TOKEN] = $isValidToken;
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * function that is called when put is done **
     * Update Address from Customer Address Book
     *
     * @param array $data            
     * @return array json array
     */
    protected function _update(array $data) {
        $response = array ();
        $addressData = array ();
        // get website id
        $websiteId = ( int ) $data [static::WEBSITE_ID];
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get website id from request
        $storeId = ( int ) $data [static::STOREID];
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        // get address id
        $addressId = ( int ) $data [static::ADDRESS_ID];
        $customerId = ( int ) $data [static::CUSTOMER_ID];
        // get token
        $token = $data [static::TOKEN];
        // set street address as array
        $data [static::STREET] = json_decode ( '["' . ($data [static::STREET]) . '"]' );
        
        /* @var $address Mage_Customer_Model_Address */
        $address = $this->_loadCustomerAddressById ( $addressId );
        $validator = $this->_getValidator ();
        
        $data = $validator->filter ( $data );
        if (! $validator->isValidData ( $data, true ) || ! $validator->isValidDataForChangeAssociationWithCountry ( $address, $data )) {
            foreach ( $validator->getErrors () as $error ) {
                $this->_error ( $error, Mage_Api2_Model_Server::HTTP_BAD_REQUEST );
                $err_message = $error;
            }
        }
        if (isset ( $data [static::REGION] )) {
            $data [static::REGION] = $this->_getRegionIdByNameOrCode ( $data [static::REGION], isset ( $data [static::COUNTRY_ID] ) ? $data [static::COUNTRY_ID] : $address->getCountryId () );
            // to avoid overwrite region during update in address model _beforeSave()
            $data ['region_id'] = null;
        }
        $address->addData ( $data );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $token );
        if ($isValidToken) {
            try {
                $address->save ();
                $success = 1;
                $err_message = 'The address has been updated.';
                // get address by customer
                $addressData = $this->_getAddressList ( $customerId, $storeId );
                // assign address to result tag
                $response [static::RESULT] = $addressData;
            } catch ( Mage_Core_Exception $e ) {
                $success = 0;
                $err_message = $e->getMessage ();
            }
            $response [static::VALID_TOKEN] = true;
        } else {
            $success = 0;
            $err_message = static::AUTH_FAIL;
            $response [static::VALID_TOKEN] = false;
        }
        
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $err_message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * function that is called when delete is done **
     * Delete Address from Customer Address Book
     *
     * @return array json array
     */
    protected function _delete() {
        $response = array ();
        
        // get address id from request
        $addressId = ( int ) $this->getRequest ()->getParam ( static::ADDRESS_ID );
        // get website id from request
        $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITE_ID );
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get website id from request
        $storeId = ( int ) $this->getRequest ()->getParam ( static::STOREID );
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        $customerId = ( int ) $this->getRequest ()->getParam ( static::CUSTOMER_ID );
        // get token
        $token = $this->getRequest ()->getParam ( static::TOKEN );
        /* @var $address Mage_Customer_Model_Address */
        $address = $this->_loadCustomerAddressById ( $addressId );
        $delete = TRUE;
        if ($this->_isDefaultBillingAddress ( $address ) || $this->_isDefaultShippingAddress ( $address )) {
            $delete = FALSE;
        }
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $token );
        if ($isValidToken) {
            try {
                if ($delete) {
                    $address->delete ();
                    $success = 1;
                    $message = 'The address has been deleted.';
                } else {
                    $success = 0;
                    $message = 'Address is default for customer so is not allowed to be deleted.';
                }
            } catch ( Mage_Core_Exception $e ) {
                $success = 0;
                $message = $e->getMessage ();
            } catch ( Exception $e ) {
                $success = 0;
                $message = $e->getMessage ();
            }
        } else {
            $success = 0;
            $message = static::AUTH_FAIL;
        }
        $response [static::VALID_TOKEN] = $isValidToken;
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        $this->getResponse ()->setBody ( json_encode ( $response ) );
        return;
    }
    
    /**
     * Retrieve information about specified customer address
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve() {
        /* @var $address Mage_Customer_Model_Address */
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $customerId = ( int ) $this->getRequest ()->getParam ( static::CUSTOMER_ID );
        $token = $this->getRequest ()->getParam ( static::TOKEN );
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $token );
        if ($isValidToken) {
            $address = $this->_loadCustomerAddressById ( $this->getRequest ()->getParam ( 'id' ) );
            $addressData = $address->getData ();
            $addressData [static::STREET] = $address->getStreet ();
            $addressData ['region_code'] = $address->getRegionCode ();
            $addressData [static::SUCCESS] = 1;
            $addressData [static::MESSAGE] = 'Get Address Detail.';
        } else {
            $addressData [static::SUCCESS] = 0;
            $addressData [static::MESSAGE] = static::AUTH_FAIL;
        }
        $addressData [static::VALID_TOKEN] = $isValidToken;
        $addressData [static::ERROR] = false;
        return $addressData;
    }
    
    /**
     * Get customer addresses list
     *
     * @return array
     */
    protected function _retrieveCollection() {
        $response = array ();
        
        // get website id from request
        $websiteId = ( int ) $this->getRequest ()->getParam ( static::WEBSITE_ID );
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get website id from request
        $storeId = ( int ) $this->getRequest ()->getParam ( static::STOREID );
        if ($storeId <= 0) {
            Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        $customerId = ( int ) $this->getRequest ()->getParam ( static::CUSTOMER_ID );
        
        /**
         * Check this customer has valid token or not
         *
         * @var $isValidToken Mage_Login_Model_Token
         */
        $token = $this->getRequest ()->getParam ( static::TOKEN );
        $isValidToken = Mage::getModel ( static::LOGIN_TOKEN )->checkUserToken ( $customerId, $token );
        if ($isValidToken) {
            $data = $this->_getAddressList ( $customerId, $storeId );
            
            if (count ( $data ) > 0) {
                $message = 'Get Address Successfully.';
            } else {
                $message = 'No Address Available.';
            }
            $success = 1;
            $response [static::VALID_TOKEN] = $isValidToken;
        } else {
            $response [static::VALID_TOKEN] = $isValidToken;
            $message = static::AUTH_FAIL;
            $success = 0;
        }
        $response [static::ERROR] = false;
        $response [static::SUCCESS] = $success;
        $response [static::MESSAGE] = $message;
        $response [static::RESULT] = $data;
        return json_encode ( $response );
    }
    
    /**
     * Retrieve customer address collection
     *
     * @param int $customerId            
     * @param int $storeId            
     * @return Mage_Customer_Model_Address
     */
    public function _getAddressList($customerId, $storeId) {
        $data = array ();
        /* @var $address Mage_Customer_Model_Address */
        
        foreach ( $this->_getCollectionForRetrieve ( $customerId ) as $address ) {
            $address->setStoreId ( $storeId )->load ( $address->getEntityId () );
            $addressData = $address->getData ();
            $addressData [static::STREET] = $address->getStreet ();
            $country = Mage::getModel ( 'directory/country' )->loadByCode ( $address->getCountryId () );
            $addressData ['region_code'] = $address->getRegionCode ();
            // get region id
            $regionModel = Mage::getModel ( 'directory/region' )->loadByCode ( $address->getRegionCode (), $address->getCountryId () );
            $addressData ['region_id'] = $regionModel->getId ();
            // get country name
            $addressData ['country_name'] = $country->getName ();
            $data [] = array_merge ( $addressData, $this->_getDefaultAddressesInfo ( $address ) );
        }
        $sort = array ();
        foreach ( $data as $k => $v ) {
            $sort [static::ENTITY_ID] [$k] = $v [static::ENTITY_ID];
        }
        
        array_multisort ( $sort [static::ENTITY_ID], SORT_DESC, $data );
        return $data;
    }
    
    /**
     * Retrieve collection instances
     *
     * @param int $customerId            
     * @return Mage_Customer_Model_Resource_Address_Collection
     */
    protected function _getCollectionForRetrieve($customerId) {
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = $this->_loadCustomerById ( $customerId );
        
        /* @var $collection Mage_Customer_Model_Resource_Address_Collection */
        $collection = $customer->getAddressesCollection ();
        
        $this->_applyCollectionModifiers ( $collection );
        return $collection;
    }
    
    /**
     * Get array with default addresses information if possible
     *
     * @param Mage_Customer_Model_Address $address            
     * @return array
     */
    protected function _getDefaultAddressesInfo(Mage_Customer_Model_Address $address) {
        return array (
                'is_default_billing' => (( int ) $this->_isDefaultBillingAddress ( $address ) > 0),
                'is_default_shipping' => (( int ) $this->_isDefaultShippingAddress ( $address ) > 0) 
        );
    }
}