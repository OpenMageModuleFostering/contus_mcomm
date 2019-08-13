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
 * @package    Contus_Pushnoteproducts
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class Contus_Pushnoteproducts_Block_Adminhtml_Pushnoteproducts extends Mage_Adminhtml_Block_Widget_Grid_Container {
    public function __construct() {
        $this->_controller = 'adminhtml_pushnoteproducts';
        $this->_blockGroup = 'pushnoteproducts';
        $this->_headerText = Mage::helper ( 'pushnoteproducts' )->__ ( 'Products' );
        $this->_addButtonLabel = Mage::helper ( 'pushnoteproducts' )->__ ( 'Add Item' );
        parent::__construct ();
        // Remove add button in admin grid page
        $this->_removeButton ( 'add' );
    }
}