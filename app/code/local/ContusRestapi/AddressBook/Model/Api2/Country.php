<?php
/**
 * Contus
 * 
 * Country API
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
class ContusRestapi_AddressBook_Model_Api2_Country extends Mage_Api2_Model_Resource {
    
    /**
     * Get Country collection
     * function that is called when post is done **
     */
    protected function _retrieveCollection() {
        $countries = array ();
        $countryCollection = Mage::getResourceModel ( 'directory/country_collection' )->loadByStore ()->toOptionArray ();
        if (count ( $countryCollection ) > 0) {
            $i = 0;
            foreach ( $countryCollection as $country ) {
                if ($country ['value'] != '') {
                    $countries [$i] ['country_id'] = $country ['value'];
                    $countries [$i] ['name'] = $country ['label'];
                    $i ++;
                }
            }
            $response ['success'] = 1;
            $response ['result'] = $countries;
        } else {
            $response ['success'] = 0;
            $response ['result'] = $countries;
        }
        $response ['error'] = false;
        return json_encode ( $response );
    }
}