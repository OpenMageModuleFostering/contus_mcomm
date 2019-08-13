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
 * @package    Contus_Offers
 * @author     Contus Team <developers@contus.in>
 * @copyright  Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license    http://www.apptha.com/LICENSE.txt Open Software License ("OSL") v. 3.0
 * @since      1.0
 */

class Contus_Offers_Block_Adminhtml_Offers_Renderer_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
	/**
	 * This function is used to render image in grid page.
	 *
	 * @param Varien_Object $row
	 *        	Current grid row id
	 *        	
	 * @return string Returns uploaded image
	 */
	public function render(Varien_Object $row) {
		$url = Mage::getBaseUrl ( 'media' );
		if ($row->getData ( $this->getColumn ()->getIndex () ) != '') {
			
			$html = '<img ';
			$html .= 'id="' . $this->getColumn ()->getId () . '" ';
			$html .= 'src="' . $url . $row->getData ( $this->getColumn ()->getIndex () ) . '"' . 'width = "60px"';
			$html .= 'class="grid-image ' . $this->getColumn ()->getInlineCss () . '"/>';
			$html .= '<br/><p>' . $row->getData ( $this->getColumn ()->getIndex () ) . '</p>';
		} else {
			$html = 'No image';
		}
		return $html;
	}
}