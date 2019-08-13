
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
 * @package    Contus_Configuration
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */
class Contus_Configuration_Model_Adminhtml_Homelist {
    public function getCommentText() {
        return "Some text here";
    }
    public function toOptionArray() {
        return array (
                array (
                        'value' => 'newarrivals',
                        'label' => 'New Arrivals'
                ),
                array (
                        'value' => 'bestsellers',
                        'label' => 'Best sellers' 
                ),
                array (
                        'value' => 'offers',
                        'label' => 'Offers' 
                ) 
        );
    }
}