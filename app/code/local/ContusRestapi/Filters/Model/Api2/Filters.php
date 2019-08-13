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
 * @package    ContusRestapi_Filters
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class ContusRestapi_Filters_Model_Api2_Filters extends Mage_Api2_Model_Resource {
    
    // define static variable
    const PRICE = 'price';
    const ATTR_CODE = 'attribute_code';
    const ATTR_ID = 'attribute_id';
    const VALUES = 'values';
    const VALUE = 'value';
    const LABEL = 'label';
    const COUNT = 'count';
    const CATALOG_PRODUCT = 'catalog/product';
    const E_ATTR_SET_ID = 'e.attribute_set_id';
    
    /**
     * function that is called when post is done **
     *
     * Get product types for filter category products based on category id
     *
     * @param array $data            
     *
     * @return array json array
     */
    protected function _retrieveCollection() {
        $response = array ();
        $filters = array ();
        // get customer id from request
        $categoryId = ( int ) $this->getRequest ()->getParam ( 'category_id' );
        
        // get website id from request
        $websiteId = ( int ) $this->getRequest ()->getParam ( 'website_id' );
        if ($websiteId <= 0) {
            $websiteId = Mage::app ()->getWebsite ( 'base' )->getId ();
        }
        // get store id from request
        $storeId = ( int ) $this->getRequest ()->getParam ( 'store_id' );
        if ($storeId <= 0) {
            $storeId = Mage::app ()->getWebsite ( $websiteId )->getDefaultGroup ()->getDefaultStoreId ();
        }
        // load category model
        $category = Mage::getModel ( 'catalog/category' )->load ( $categoryId );
        
        // Get attribute set ids based on category
        $setIds = $this->getAttributeSetIds ( $storeId, $category );
        // get searchable attributes from attribute set id
        $attribuets = $this->getSearchableAttr ( $setIds, $storeId );
        
        foreach ( $attribuets as $attribute ) {
            $result = array ();
            // assigen attribute code
            $result [static::ATTR_CODE] = $attribute [static::ATTR_CODE];
            // assigen attribute label
            $result ['attribute_label'] = $attribute ['store_label'];
            // assigen attribute id
            $result [static::ATTR_ID] = $attribute [static::ATTR_ID];
            
            // get attribute values from CATEGORY model
            $attrCollection = $this->getAttributeCollection ( $category, $attribute [static::ATTR_CODE] );
            
            // remove duplicate attribute value from array
            $attriArray = $this->remove_duplicateKeys ( $attribute [static::ATTR_CODE], $attrCollection );
            
            // get product count in each attriubute
            $productCount = $this->getProductCount ( $attribute [static::ATTR_CODE], $attribute [static::ATTR_ID], $storeId, $category );
            // get attribute value array
            $result [static::VALUES] = $this->setAttr ( $attriArray, $attribute [static::ATTR_CODE], $attribute [static::ATTR_ID], $productCount );
            // form array
            $filters [] = $result;
        }
        // form array for sock availability
        $availability [static::ATTR_CODE] = 'availability';
        $availability ['attribute_label'] = 'Availability';
        $values [static::VALUE] = 1;
        $values [static::LABEL] = 'Availability';
        $values [static::COUNT] = '';
        $availability [static::VALUES] [] = $values;
        $filters [] = $availability;
        
        $response ['error'] = false;
        $response ['success'] = 1;
        $response ['message'] = '';
        $response ['result'] = $filters;
        return json_encode ( $response );
    }
    
    /**
     * Get attribute set ids
     *
     * @param int $storeId            
     * @param int $categoryId            
     */
    public function getAttributeSetIds($storeId, $category) {
        $select = Mage::getResourceModel ( 'catalog/product_collection' )->setStoreId ( $storeId )->addCategoryFilter ( $category );
        $select->getSelect ()->distinct ( static::E_ATTR_SET_ID );
        
        $select->getSelect ()->reset ( Zend_Db_Select::COLUMNS )->columns ( static::E_ATTR_SET_ID );
        
        return $select->getData ();
    }
    
    /**
     * Get searchable attributes from attribute set id
     *
     * @param array $setIds            
     * @param int $storeId            
     */
    public function getSearchableAttr($setIds, $storeId) {
        $collection = Mage::getResourceModel ( 'catalog/product_attribute_collection' );
        $collection->setItemObjectClass ( 'catalog/resource_eav_attribute' )->setAttributeSetFilter ( array (
                $setIds 
        ) )->addStoreLabel ( $storeId )->setOrder ( 'position', 'ASC' );
        $collection = $collection->addIsFilterableFilter ();
        
        return $collection->load ()->getData ();
    }
    
    /**
     * Get attribute collection from category products
     *
     * @param object $category            
     * @param string $attributeCode            
     * @return array $attrCollection
     */
    public function getAttributeCollection($category, $attributeCode) {
        $config = false;
        $productAttr = array ();
        // dtabase read
        $connection = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_read' );
        
        // get these type of products only
        $productType = array (
                "simple",
                "configurable"
        );
        // get attribute values from CATEGORY model
        $productAttr = Mage::getModel ( static::CATALOG_PRODUCT )->getCollection ()->addCategoryFilter ( $category )->addAttributeToFilter ( 'type_id', array (
                'in' => $productType 
        ) );
        
        // get attribute value from associaded products
        $childproduct = Mage::getModel ( static::CATALOG_PRODUCT )->getCollection ();
        foreach ( $productAttr as $_product ) {
            if ($_product ['type_id'] == 'configurable') {
                
                // get the children ids through a simple query
                $ids = '';
                $ids = Mage::getModel ( 'catalog/product_type_configurable' )->getChildrenIds ( $_product ['entity_id'] );
                // get associated products count
                $counted = count ( $ids [0] );
                if ($counted > 0) {
                    $config = true;
                    $filter_attr ['attribute'] = 'entity_id';
                    $filter_attr ['in'] = array (
                            $ids 
                    );
                    $data [] = $filter_attr;
                }
            } else {
                $productAttr->addAttributeToSelect ( $attributeCode, 'asc' );
                $productAttr->getSelect ()->reset ( Zend_Db_Select::COLUMNS )->columns ( 'e.entity_id' )->columns ( static::E_ATTR_SET_ID )->columns ( 'e.type_id' )->columns ( 'e.sku' )->columns ( 'at_' . $attributeCode . '.value as ' . $attributeCode );
            }
        }
        
        if ($config) {
            $childproduct->addAttributeToFilter ( $data, '', 'left' )->addAttributeToSelect ( $attributeCode, 'asc' );
            $childproduct->getSelect ()->reset ( Zend_Db_Select::COLUMNS )->columns ( 'e.entity_id' )->columns ( 'e.attribute_set_id' )->columns ( 'e.type_id' )->columns ( 'e.sku' )->columns ( 'at_' . $attributeCode . '.value as ' . $attributeCode );
            $childproduct->getSelect ()->group ( $attributeCode );
            // union queries in product and associated product
            $main_select = $connection->select ()->union ( array (
                    $childproduct->getSelectSql ( true ),
                    $productAttr->getSelectSql ( true ) 
            ), Zend_Db_Select::SQL_UNION )->group ( $attributeCode );
            
            $attrCollection = $connection->fetchAll ( $main_select );
        } else {
            $productAttr->getSelect ()->group ( $attributeCode );
            $attrCollection = $productAttr->getConnection ()->fetchAll ( $productAttr->getSelect () );
        }
        
        return $attrCollection;
    }
    
    /**
     * Retrieve array with products counts per attribute option
     *
     * @param Mage_Catalog_Model_Layer_Filter_Attribute $filter            
     * @return array
     */
    public function getProductCount($attributeCode, $attributeId, $storeId, $category) {
        // get the table preix value
        $prefix = Mage::getConfig ()->getTablePrefix ();
        // dtabase read
        $connection = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_read' );
        
        $select = Mage::getResourceModel ( 'catalog/product_collection' )->setStoreId ( $storeId )->addCategoryFilter ( $category );
        Mage::getSingleton ( 'catalog/product_visibility' )->addVisibleInCatalogFilterToCollection ( $select );
        $query_count = $select->getSelect ()->reset ( Zend_Db_Select::COLUMNS )->reset ( Zend_Db_Select::ORDER )->reset ( Zend_Db_Select::LIMIT_COUNT )->reset ( Zend_Db_Select::LIMIT_OFFSET )->join ( array (
                $attributeCode . '_idx' => $prefix . 'catalog_product_index_eav' 
        ), $attributeCode . '_idx.entity_id = e.entity_id AND ' . $attributeCode . '_idx.attribute_id = ' . $attributeId . ' AND ' . $attributeCode . '_idx.store_id = ' . $storeId, array (
                $attributeCode . '_idx.value, COUNT(' . $attributeCode . '_idx.entity_id) AS count' 
        ) )->group ( $attributeCode . '_idx.value' );
        return $connection->fetchPairs ( $query_count );
    }
    
    /**
     * Set attibute values as array with product count
     *
     * @param array $productAttr            
     * @param string $attributeCode            
     * @param int $productCount            
     * @return array $result
     */
    public function setAttr($productAttr, $attributeCode, $attributeId, $productCount) {
        $result = array ();
        $price = array ();
        foreach ( $productAttr as $attr ) {
            if ($attr [$attributeCode] != '') {
                $values = array ();
                // is attribute id is not price ste fale
                $price_flag = false;
                // check attribute id price
                if ($attributeCode == static::PRICE) {
                    array_push ( $price, $attr [$attributeCode] );
                    $price_flag = true;
                } else {
                    // get attribute value id
                    $values [static::VALUE] = $attr [$attributeCode];
                    // get attribute value label
                    $productModel = Mage::getModel ( static::CATALOG_PRODUCT )->getResource ()->getAttribute ( $attributeId );
                    $values [static::LABEL] = $productModel->getSource ()->getOptionText ( $attr [$attributeCode] );
                    // get product count
                    $values [static::COUNT] = isset ( $productCount [$attr [$attributeCode]] ) ? $productCount [$attr [$attributeCode]] : 0;
                    $result [] = $values;
                }
            }
        }
        
        // get minmum and maximum price from price attribute
        if ($price_flag) {
            // sort price array
            sort ( $price );
            // get minimum price
            $result [0] [static::VALUE] = number_format ( $price [0], 2, '.', '' );
            $result [0] [static::LABEL] = 'min_price';
            $result [0] [static::COUNT] = '';
            // get maximum price
            $result [1] [static::VALUE] = number_format ( end ( $price ), 2, '.', '' );
            $result [1] [static::LABEL] = 'max_price';
            $result [1] [static::COUNT] = '';
        }
        
        return $result;
    }
    
    /**
     *
     * @param string $key            
     * @param array $data            
     * @return array $data
     */
    public function remove_duplicateKeys($key, $data) {
        $_data = array ();
        
        foreach ( $data as $v ) {
            if (isset ( $_data [$v [$key]] )) {
                // found duplicate
                continue;
            }
            // remember unique item
            $_data [$v [$key]] = $v;
        }
        // if you need a zero-based array, otheriwse work with $_data
        return array_values ( $_data );
    }
}