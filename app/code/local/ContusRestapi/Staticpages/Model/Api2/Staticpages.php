<?php
/**
 * Contus
 * 
 *  Forgotpassword API
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
 * @package    ContusRestapi_Staticpages
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Staticpages_Model_Api2_Staticpages extends Mage_Api2_Model_Resource {
    const CONTENT = 'content';
    const MESSAGE = 'message';
    const SUCCESS = 'success';
    
    /**
     * function that is called when post is done **
     *
     * Get Static pages
     *
     * @return array json array
     */
    protected function _retrieve() {
        $response = array ();
        
        // get website id from request
        $websiteId = $this->getRequest ()->getParam ( 'website_id' );
        if (! isset ( $websiteId ) && $websiteId == '') {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        
        // get store id from request
        $storeId = ( int ) $this->getRequest ()->getParam ( 'store_id' );
        if (! isset ( $storeId ) && $storeId == '') {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        $pageKey = $this->getRequest ()->getParam ( 'page_key' );
        
        // get cms page content based on store id and page_key
        if ($pageKey) {
            $collection = Mage::getModel ( 'cms/page' )->getCollection ()->addStoreFilter ( $storeId )->addFieldToFilter ( 'is_active', 1 )->addFieldToFilter ( 'identifier', $pageKey );
            foreach ( $collection as $content ) {
                $content = Mage::helper ( 'cms' )->getPageTemplateProcessor ()->filter ( $content->getContent () );
                $response [static::CONTENT] = htmlentities ( $content );
                $response [static::SUCCESS] = 1;
            }
        } else {
            $response [static::MESSAGE] = 'No Data Available';
            $response [static::SUCCESS] = 0;
        }
        if (empty ( $response )) {
            $response [static::MESSAGE] = 'No Data Available';
            $response [static::SUCCESS] = 0;
        }
        $response ['error'] = false;
        return json_encode ( $response );
    }
}