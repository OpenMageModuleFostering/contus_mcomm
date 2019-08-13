<?php

/**
 * Apptha
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
 * @category    Contus
 * @package     Contus_Pushnoteproducts
 * @version     0.1
 * @author      Contus Team <developers@contus.in>
 * @copyright   Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 */

/**
 * Renderer to get product Url 
 */
class Contus_Pushnoteproducts_Block_Adminhtml_Pushnoteproducts_Renderer_Notification extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to get product Url
     * 
     * Return the Total sales
     * 
     * @return string
     */

    public function render(Varien_Object $row) {
        
     	$id = $row->getId ();
     	//getting product model
     	$model = Mage::getModel('catalog/product'); 
     	//getting product object for particular product id
     	$product = $model->load($id); 
     	$name=$product->getName();
		$baseUrl = Mage::getBaseUrl ();
		return '<a class="PushnoteProducts" id=' . $id . ' href="#" name="' . $name . ' ">' . $this->__ ( 'Push' ) . '</a>';
    }

}

