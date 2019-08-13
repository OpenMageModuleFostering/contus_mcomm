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
class Contus_Configuration_Block_Adminhtml_About extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {
    /**
     * Support tab
     * version 2.0.0
     */
    public function render(Varien_Data_Form_Element_Abstract $element) {
        
        $logoLink = Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_WEB, false ).'contus/image/splash_logo.png';
        $helper = Mage::helper ( 'configuration' );
       
        
        $html = '<style>
                .line {border-top: 1px solid #c6c6c6; padding-top: 10px;}
                .developer-label {color: #000000; font-weight:bold; width: 200px;}
                .developer-text { padding-bottom: 15px;}
                .developer {width: 600px; }
            </style>';
        
        $html .= '
            <table cellspacing="0" cellpading="0" class="developer">
                <tr>
                    <td class="developer-label "><img src="' . $logoLink . '" width="180px" > </td>
                    <td class="developer-text ">' . $helper->__ ( '<strong>version 2.1</strong> ' ) . '</td>
                </tr>
                <tr>
                    <td class="developer-label line">' . $helper->__ ( 'Extension:' ) . '</td>
                    <td class="developer-text line">' . $helper->__ ( ' version 1.0.0' ) . '</td>
                </tr>
                <tr>
                    <td class="developer-label line">' . $helper->__ ( 'License:' ) . '</td>
                    <td class="developer-text line">' . $helper->__ ( ' <a href="http://www.apptha.com/LICENSE.txt" target="_blank" >License</a>' ) . '</td>
                </tr>
                <tr>
                    <td class="developer-label line">' . $helper->__ ( 'Support:' ) . '</td>
                    <td class="developer-text line">' . $helper->__ ( 'Please, for request to Build Mobile applications, feature requests or report any bugs related to the Solution.<br>' . '<br><a href="http://www.contus.com/magento-mobile-app.php?utm_source=Mobile App for Magento – Contus M-Comm&utm_medium=CTA&utm_campaign=Magento Commerce#request_quote" target="_blank">Contact us</a>' ) . '</td>
                </tr>
                
            </table>';
        
        return $html;
    }
  
}