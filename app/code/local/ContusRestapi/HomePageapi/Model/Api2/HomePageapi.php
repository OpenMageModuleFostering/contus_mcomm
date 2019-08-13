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
 * @package    ContusRestapi_HomePageapi
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_HomePageapi_Model_Api2_HomePageapi extends Mage_Api2_Model_Resource {
    
    // Declaring the string literals variable
    const ENTITYID = 'entity_id';
    const NAME = 'name';
    const IMGURL = 'image_url';
    const REGULARPRICE = 'regular_price_with_tax';
    const FINALPRICE = 'final_price_with_tax';
    const PRODUCTTYPE = 'product_type';
    const PROCOLLECTION = 'collection';
    const VISIBILITY = 'visibility';
    const STATUS = 'status';
    const CATIMG = 'catalog/image';
    const SMALLIMG = 'small_image';
    const TYPE_ID = 'type_id';
    const OFFER = 'offer';
    const SETTINGS = 'settings';
    const BANNER_TYPE = 'banner_type';
    const LOGIN_TOKEN = 'login/token';
    const WEBSITE_ID = 'website_id';
    const STORE_ID = 'store_id';
    const DETAIL = 'detail';
    
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
        // set page size as 1
        $page = 1;
        
        // get website id from request
        $websiteId = ( int ) Mage::app ()->getRequest ()->getParam ( static::WEBSITE_ID );
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get store id from request
        $storeId = ( int ) Mage::app ()->getRequest ()->getParam ( static::STORE_ID );
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        
        // get image size for resize
        $imageSize = ( int ) Mage::app ()->getRequest ()->getParam ( 'image_size' );
        
        // get today date
        $todayDate = Mage::app ()->getLocale ()->date ()->toString ( Varien_Date::DATETIME_INTERNAL_FORMAT );
        // get these type of products only
        $productType = array (
                "simple",
                "configurable" 
        );
        
        // Get Categories
        // get root category for store base
        $rootCatId = Mage::app ()->getStore ( $storeId )->getRootCategoryId ();
        $response [static::SETTINGS] ['root_category_id'] = $rootCatId;
        $response ['categories'] = $this->getCategoryList ( $storeId, $rootCatId );
        
        // Get new arrival products
        $limit = 10;
        $newProdCollection = array ();
        $newProdCollection = $this->getNewArrivalProducts ( $storeId, $page, $limit, $todayDate, $productType, $imageSize );
        $response [static::PROCOLLECTION] = $newProdCollection;
        $collection_type = 'New Arrivals';
        // get new product count
        $newProCount = count ( $newProdCollection );
        
        // Get best selling products
        if ($newProCount < 3) {
            $bestSelling = $this->getbestSellingProducts ( $storeId, $page, $limit, $imageSize );
            $response [static::PROCOLLECTION] = $bestSelling;
            $collection_type = 'Best Seller';
        }
        // get best selling products count
        $bestProCount = count ( $bestSelling );
        
        // Get all product collection
        if ($newProCount < 3 && $bestProCount < 3) {
            $allProduct = $this->getallProducts ( $storeId, $page, $limit, $productType, $imageSize );
            $response [static::PROCOLLECTION] = $allProduct;
            $collection_type = 'All Products';
        }
        $response [static::SETTINGS] [static::PRODUCTTYPE] = $collection_type;
        
        /**
         * Home page banner
         * Get enabled home page product from configuration
         */
        $offersCollection = array ();
        $bannerProducts = array ();
        $response [static::OFFER] = array ();
        $homeBanner = Mage::getStoreConfig ( 'contus/configuration_home/home_banner', $storeId );
        $BannerCount = Mage::getStoreConfig ( 'contus/configuration_home/banner_count', $storeId );
        
        if ($homeBanner == 'offers') {
            $offersCollection = $this->getOffers ( $BannerCount );
            $response [static::SETTINGS] [static::BANNER_TYPE] = 'list';
            $response [static::OFFER] = $offersCollection;
        } else if ($homeBanner == 'bestsellers') {
            $response [static::SETTINGS] [static::BANNER_TYPE] = static::DETAIL;
            $response [static::OFFER] = $this->getbestSellingProducts ( $storeId, $page, $BannerCount, $imageSize );
        } else if ($homeBanner == 'newarrivals') {
            $response [static::SETTINGS] [static::BANNER_TYPE] = static::DETAIL;
            $response [static::OFFER] = array_slice ( $newProdCollection, 0, $BannerCount );
        } else {
            $bannerProducts = array_slice ( $response [static::PROCOLLECTION], 0, $BannerCount );
            $response [static::OFFER] = $bannerProducts;
            $response [static::SETTINGS] [static::BANNER_TYPE] = static::DETAIL;
        }
        
        // Get Available stores based on website id
        $response ['available_stores'] = $this->getAvailableStores ( $websiteId );
        $response ['error'] = false;
        $response ['success'] = 1;
        return json_encode ( $response );
    }
    
    /**
     * Get Special offerd Products
     *
     * @param int $page            
     * @param int $storeId            
     * @param int $limit            
     *
     * @param date $todayDate            
     * @return array $offerProducts
     */
    public function getOfferedProducts($storeId, $page, $limit, $todayDate, $productType, $imageSize) {
        $_productCollection = Mage::getResourceModel ( 'catalog/product_collection' );
        $_productCollection->setStoreId ( $storeId );
        
        $_productCollection->addAttributeToFilter ( 'special_from_date', array (
                'date' => true,
                'to' => $todayDate 
        ) )->addAttributeToFilter ( static::TYPE_ID, array (
                'in' => $productType 
        ) )->

        addAttributeToFilter ( 'special_to_date', array (
                'or' => array (
                        
                        0 => array (
                                'date' => true,
                                'from' => $todayDate 
                        ),
                        
                        1 => array (
                                'is' => new Zend_Db_Expr ( 'null' ) 
                        ) 
                ) 
        ), 'left' )->

        addAttributeToSort ( 'special_price', 'asc' );
        $_productCollection->addAttributeToFilter ( static::VISIBILITY, array (
                'neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE 
        ) )->addAttributeToFilter ( static::STATUS, array (
                'eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED 
        ) );
        $_productCollection->addAttributeToFilter ( 'special_price', array (
                'neq' => '0' 
        ) );
        $_productCollection->setPage ( $page, $limit );
        
        $offerProducts = array ();
        $i = 0;
        
        foreach ( $_productCollection as $item ) {
            $item->setStoreId ( $storeId )->load ( $item->getId () );
            // get product id
            $offerProducts [$i] [static::ENTITYID] = $item->getId ();
            // get type id
            $offerProducts [$i] [static::TYPE_ID] = $item->getTypeId ();
            // get product name
            $offerProducts [$i] [static::NAME] = $item->getName ();
            // get product small image
            if ($imageSize <= 0) {
                $offerProducts [$i] [static::IMGURL] = ( string ) Mage::helper ( static::CATIMG )->init ( $item, static::SMALLIMG );
            } else {
                $offerProducts [$i] [static::IMGURL] = ( string ) Mage::helper ( static::CATIMG )->init ( $item, static::SMALLIMG )->constrainOnly ( TRUE )->keepAspectRatio ( TRUE )->keepFrame ( FALSE )->resize ( $imageSize, null );
            }
            // get the product final price
            $offerProducts [$i] [static::REGULARPRICE] = number_format ( $item->getPrice (), 2, '.', '' );
            $offerProducts [$i] [static::FINALPRICE] = number_format ( $item->getFinalPrice (), 2, '.', '' );
            $i ++;
        }
        
        return $offerProducts;
    }
    
    /**
     * Get new arrival products
     * Based on news_from_date attribute
     *
     * @param int $storeId            
     * @param int $page            
     * @param $limit 10            
     * @param date $todayDate            
     * @return array $newProducts
     */
    public function getNewArrivalProducts($storeId, $page, $limit, $todayDate, $productType, $imageSize) {
        $collection = Mage::getResourceModel ( 'catalog/product_collection' );
        $collection->setStoreId ( $storeId );
        
        $collection->addAttributeToFilter ( static::TYPE_ID, array (
                'in' => $productType 
        ) )->addAttributeToFilter ( 'news_from_date', array (
                'date' => true,
                'to' => $todayDate 
        ) )->addAttributeToFilter ( 'news_to_date', array (
                'or' => array (
                        
                        0 => array (
                                'date' => true,
                                'from' => $todayDate 
                        ),
                        
                        1 => array (
                                'is' => new Zend_Db_Expr ( 'null' ) 
                        ) 
                ) 
        ), 'left' )->

        addAttributeToSort ( 'news_from_date', 'desc' );
        $collection->addAttributeToFilter ( static::VISIBILITY, array (
                'neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE 
        ) )->addAttributeToFilter ( static::STATUS, array (
                'eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED 
        ) );
        $collection->setPage ( $page, $limit );
        
        return $this->getProductDetail ( $collection, $storeId, $imageSize );
    }
    
    /**
     * Get best selling products
     *
     * @param int $storeId            
     * @param int $page            
     * @param $limit 10            
     * @return array $bestProducts
     */
    public function getbestSellingProducts($storeId, $page, $limit, $imageSize) {
        $visibility = array (
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG 
        );
        
        $productType [0] = 'simple';
        $productType [1] = 'virtual';
        $productType [2] = 'downloadable';
        
        $_productCollection = Mage::getResourceModel ( 'reports/product_collection' )->addAttributeToSelect ( '*' )->addOrderedQty ()->addAttributeToFilter ( static::VISIBILITY, $visibility )->addAttributeToFilter ( static::STATUS, array (
                'eq' => '1' 
        ) )->addAttributeToFilter ( static::TYPE_ID, array (
                'in' => $productType 
        ) )->setPage ( $page, $limit )->setOrder ( 'ordered_qty', 'desc' );
        
        return $this->getProductDetail ( $_productCollection, $storeId, $imageSize );
    }
    
    /**
     * Get Most viewed products
     *
     * @param int $storeId            
     * @param int $page            
     * @param $limit 10            
     * @return array $mostViewProducts
     */
    public function geMostViewProducts($storeId, $page, $limit, $productType, $imageSize) {
        $visibility = array (
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG 
        );
        $_productCollection = Mage::getResourceModel ( 'reports/product_collection' )->addAttributeToSelect ( '*' )->setStoreId ( $storeId )->addStoreFilter ( $storeId )->addAttributeToFilter ( static::VISIBILITY, $visibility )->addAttributeToFilter ( static::STATUS, array (
                'eq' => '1' 
        ) )->addAttributeToFilter ( static::TYPE_ID, array (
                'in' => $productType 
        ) )->addViewsCount ()->setPage ( $page, $limit );
        
        return $this->getProductDetail ( $_productCollection, $storeId, $imageSize );
    }
    
    /**
     * Get all products collection
     *
     * @param int $storeId            
     * @param int $page            
     * @param $limit 10            
     * @return array $productColl
     */
    public function getallProducts($storeId, $page, $limit, $productType, $imageSize) {
        $visibility = array (
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG 
        );
        $collection = Mage::getModel ( 'catalog/product' )->getCollection ()->addAttributeToSelect ( array (
                '*' 
        ) )->addAttributeToFilter ( static::STATUS, array (
                'eq' => '1' 
        ) )->addAttributeToFilter ( static::TYPE_ID, array (
                'in' => $productType 
        ) )->addAttributeToFilter ( static::VISIBILITY, $visibility )->setStoreId ( $storeId )->addStoreFilter ( $storeId )->setPage ( $page, $limit )->addAttributeToSort ( 'created_at', 'desc' );
        
        return $this->getProductDetail ( $collection, $storeId, $imageSize );
    }
    
    /**
     * Get Product details
     *
     * @param array $collection            
     * @param int $storeId            
     * @param int $imageSize            
     * @return $Products
     */
    public function getProductDetail($collection, $storeId, $imageSize) {
        $Products = array ();
        $j = 0;
        foreach ( $collection as $product ) {
            $product->setStoreId ( $storeId )->load ( $product->getId () );
            // get product id
            $Products [$j] [static::ENTITYID] = $product->getId ();
            // get type id
            $Products [$j] [static::TYPE_ID] = $product->getTypeId ();
            // get product name
            $Products [$j] [static::NAME] = $product->getName ();
            // get product small image
            if ($imageSize <= 0) {
                $Products [$j] [static::IMGURL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, static::SMALLIMG );
            } else {
                $Products [$j] [static::IMGURL] = ( string ) Mage::helper ( static::CATIMG )->init ( $product, static::SMALLIMG )->constrainOnly ( TRUE )->keepAspectRatio ( TRUE )->keepFrame ( FALSE )->resize ( $imageSize, null );
            }
            // get the product final price
            $Products [$j] [static::REGULARPRICE] = number_format ( $product->getPrice (), 2, '.', '' );
            $Products [$j] [static::FINALPRICE] = number_format ( $product->getFinalPrice (), 2, '.', '' );
            $j ++;
        }
        return $Products;
    }
    /**
     * Get Categories
     *
     * @param int $storeId            
     * @param int $rootCatId            
     *
     * @return array $curcategory
     */
    public function getCategoryList($storeId, $rootCatId) {
        // declare empty array
        $curcategory = array ();
        
        /**
         *
         * @var $categories Mage_Catalog_Model_Category
         */
        $categories = Mage::getModel ( 'catalog/category' )->getCollection ()->setStoreId ( $storeId )->addFieldToFilter ( 'is_active', 1 )->addAttributeToFilter ( 'path', array (
                'like' => "1/{$rootCatId}/%" 
        ) )->addAttributeToSelect ( '*' );
        $k = 0;
        foreach ( $categories as $category ) {
            // get category id
            $curcategory [$k] ['category_id'] = $category->getId ();
            // get Category name
            $curcategory [$k] [static::NAME] = $category->getName ();
            
            // get image url
            if ($category->getImageUrl () != '') {
                $curcategory [$k] [static::IMGURL] = $category->getImageUrl ();
            } else {
                $curcategory [$k] [static::IMGURL] = '';
            }
            // get category position for sorting
            $curcategory [$k] ['position'] = $category->getPosition ();
            // get parent category of current category
            $curcategory [$k] ['parent_id'] = $category->getParent_id ();
            
            // check category has sub category or not
            $subCategory = $category->getChildren ();
            if ($subCategory != "") {
                $curcategory [$k] ['is_child'] = true;
            } else {
                $curcategory [$k] ['is_child'] = false;
            }
            $k ++;
        }
        return $curcategory;
    }
    
    /**
     * Get Available stores based on website id
     *
     * @param int $storeId            
     * @param int $websiteId            
     * @return array $storesArray
     */
    public function getAvailableStores($websiteId) {
        $storesArray = array ();
        try {
            $website = Mage::app ()->getWebsite ( $websiteId );
            foreach ( $website->getGroups () as $group ) {
                $stores = $group->getStores ();
                $l = 0;
                foreach ( $stores as $store ) {
                    $storesArray [$l] ['store_id'] = $store->getId ();
                    $storesArray [$l] [static::NAME] = $store->getName ();
                    $currency_code = Mage::app ()->getStore ( $store->getId () )->getCurrentCurrencyCode ();
                    $storesArray [$l] ['currency_code'] = $currency_code;
                    $storesArray [$l] ['currency_symbol'] = Mage::app ()->getLocale ()->currency ( $currency_code )->getSymbol ();
                    $l ++;
                }
            }
        } catch ( Exception $e ) {
            $e->getMessage ();
        }
        return $storesArray;
    }
    
    /**
     * Get offers list
     *
     * @return array $OffersList
     */
    public function getOffers($count) {
        // Get today date
        $today_date = Date ( 'Y-m-d' );
        $model = Mage::getModel ( 'offers/offers' );
        $collection = $model->getCollection ()->addFieldToFilter ( 'status', '1' )->addFieldToFilter ( 'from_date', array (
                array (
                        'lteq' => $today_date 
                ),
                array (
                        'from_date',
                        'null' => '' 
                ) 
        ) )->addFieldToFilter ( 'to_date', array (
                array (
                        'gteq' => $today_date 
                ),
                array (
                        'to_date',
                        'null' => '' 
                ) 
        ) )->setOrder ( "offers_id", 'DESC' );
        $collection->setPageSize ( $count )->setCurPage ( 1 );
        $i = 0;
        $OffersList = array ();
        foreach ( $collection as $offer ) {
            $offer = $model->load ( $offer->getOffersId () );
            // get offer id
            $OffersList [$i] [static::ENTITYID] = $offer->getOffersId ();
            // get type id as null
            $OffersList [$i] [static::TYPE_ID] = '';
            // get offer Title
            $OffersList [$i] [static::NAME] = $offer->getOfferTitle ();
            // get Offer image
            $OffersList [$i] [static::IMGURL] = Mage::getBaseUrl ( 'media' ) . $offer->getOfferImg ();
            $OffersList [$i] [static::REGULARPRICE] = '';
            $OffersList [$i] [static::FINALPRICE] = '';
            
            $i ++;
        }
        
        return $OffersList;
    }
    
    /**
     * function that is called when post is done **
     * offer products list
     *
     * @param array $data            
     *
     * @return array json array
     */
    protected function _retrieve() {
        $response = array ();
        
        $offerId = ( int ) Mage::app ()->getRequest ()->getParam ( 'offer_id' );
        
        // get website id from request
        $websiteId = ( int ) Mage::app ()->getRequest ()->getParam ( static::WEBSITE_ID );
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        
        // get store id
        $storeId = ( int ) Mage::app ()->getRequest ()->getParam ( static::STORE_ID );
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        
        // get the offer model
        $model = Mage::getModel ( 'offers/offers' )->load ( $offerId );
        
        $response ['offer_title'] = $model ['offer_title'];
        // get offer products
        $offer_products = $model->getOfferProducts ();
        
        // set offer_products data string to array to model
        if (isset ( $offer_products ) && $offer_products != '') {
            $offer_products = explode ( ',', $offer_products );
        }
        
        $productCollection = $this->offerProductList ( $offer_products, $storeId );
        
        $response ['success'] = 1;
        $response ['error'] = false;
        $response ['total_count'] = count ( $offer_products );
        $response ['result'] = $productCollection;
        
        return $response;
    }
    
    /**
     * Get offer products list
     *
     * @param array $offer_products            
     * @param int $storeId            
     * @return $offerProdcuts
     */
    public function offerProductList($offer_products, $storeId) {
        $offerProdcuts = array ();
        $customerId = ( int ) Mage::app ()->getRequest ()->getParam ( 'customer_id' );
        // get image size for resize
        $imageSize = ( int ) $this->getRequest ()->getParam ( 'image_size' );
        // get page from request
        $page = ( int ) $this->getRequest ()->getParam ( 'page' );
        if ($page <= 0) {
            $page = 1;
        }
        // get page from request
        $limit = ( int ) $this->getRequest ()->getParam ( 'limit' );
        if ($limit <= 0) {
            $limit = 10;
        }
        $page = ($page - 1) * $limit;
        // for pagination
        $offer_products = array_slice ( $offer_products, $page, $limit );
        
        foreach ( $offer_products as $key => $productid ) {
            $item = Mage::getModel ( 'catalog/product' )->load ( $productid );
            $item->setStoreId ( $storeId )->load ( $item->getId () );
            // get product status
            $productStatus = $item->getStatus ();
            // get only enabled products
            if ($productStatus == 1 && $item->getId () != '') {
                // get product id
                $products [static::ENTITYID] = $item->getId ();
                
                // get product name
                $products [static::NAME] = $item->getName ();
                $products [static::TYPE_ID] = $item->getTypeId ();
                // get the product final price
                $products [static::REGULARPRICE] = number_format ( $item->getPrice (), 2, '.', '' );
                $products [static::FINALPRICE] = number_format ( $item->getFinalPrice (), 2, '.', '' );
                // get product stock details
                $stockDetail = Mage::getModel ( static::LOGIN_TOKEN )->getStockDetail ( $item );
                $products ['is_saleable'] = $stockDetail ['is_saleable'];
                $products ['is_stock'] = $stockDetail ['is_stock'];
                // get product image
                if ($imageSize <= 0) {
                    $products [static::IMGURL] = ( string ) Mage::helper ( static::CATIMG )->init ( $item, static::SMALLIMG );
                } else {
                    $products [static::IMGURL] = ( string ) Mage::helper ( static::CATIMG )->init ( $item, static::SMALLIMG )->constrainOnly ( TRUE )->keepAspectRatio ( TRUE )->keepFrame ( FALSE )->resize ( $imageSize, null );
                }
                
                // get rating
                $products ['summary_rating'] = Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $item->getId (), $storeId ) ? Mage::getModel ( static::LOGIN_TOKEN )->rateSummary ( $item->getId (), $storeId ) : '0';
                
                // get wishlisted products by customer
                $wishListIds = array ();
                if ($customerId > 0) {
                    $wishListIds = Mage::getModel ( static::LOGIN_TOKEN )->getWishlistByCustomer ( $customerId );
                }
                // Check to see the product is in wishlist
                if (in_array ( $item->getId (), $wishListIds )) {
                    $products ['is_wishlist'] = true;
                } else {
                    $products ['is_wishlist'] = false;
                }
                $offerProdcuts [] = $products;
            }
            $i ++;
        }
        return $offerProdcuts;
    }
}