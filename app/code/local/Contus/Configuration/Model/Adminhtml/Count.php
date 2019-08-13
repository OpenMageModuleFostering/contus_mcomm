
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
class Contus_Configuration_Model_Adminhtml_Count {
  
    public function toOptionArray() {
        return array (
                array (
                        'value' => '1',
                        'label' => '1' 
                ),
                array (
                        'value' => '2',
                        'label' => '2' 
                ),
                array (
                        'value' => '3',
                        'label' => '3' 
                ),
                array (
                        'value' => '4',
                        'label' => '4' 
                ),
                array (
                        'value' => '5',
                        'label' => '5' 
                ) 
        );
    }
}