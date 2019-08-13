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
 * @package    ContusRestapi_SplashPage
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_SplashPage_Model_Api2_SplashPage extends Mage_Api2_Model_Resource {
   
   /**
    * function that is called when post is done **
    * Home page details
    *
    * @param array $data           
    *
    * @return array json array
    */
   protected function _retrieveCollection() {
      $response = array ();
      
      $response ['error'] = false;
      $response ['success'] = 1;
      // Get Available cities
      $response ['result'] = $this->getlist ();
      return json_encode ( $response );
   }
   public function getlist() {
      $cities = array ();
      $i = 0;
      
      $model = Mage::getModel ( 'cities/cities' );
      $collection = $model->getCollection ();
      foreach ( $collection as  $city ) {
         // load city module by id
         $city = $model->load ( $city->getCitiesId () );
         // get country id
         $cities [$i] ['country_id'] = $city->getCountryId ();
         // get country name
         $country = Mage::getModel ( 'directory/country' )->loadByCode ( $cities [$i] ['country_id'] );
         $cities [$i] ['country_name'] = $country->getName ();
         // get state id
         $cities [$i] ['state_id'] = $city->getRegionId ();
         // get state name
         $region = Mage::getModel ( 'directory/region' )->load ( $cities [$i] ['state_id'] );
         $cities [$i] ['state_name'] = $region->getName ();
         // get city id
         $cities [$i] ['city_id'] = $city->getCitiesId ();
         // get city name
         $cities [$i] ['city_name'] = $city->getCityName ();
         
         $i ++;
      }
      
      return $cities;
   }
}