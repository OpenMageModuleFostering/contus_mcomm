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

/**
 * Created table offers to offer information
 */
$installer = $this;

$installer->startSetup ();

$installer->run ( "

-- DROP TABLE IF EXISTS {$this->getTable('mcomm_offers')};
CREATE TABLE {$this->getTable('mcomm_offers')} (
  `offers_id` int(11) unsigned NOT NULL auto_increment,
  `offer_title` varchar(255) NOT NULL default '',
  `offer_img` varchar(255) NOT NULL default '',
  `store_id` varchar( 100 ) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `offer_products` varchar(255) NOT NULL default '',
  `status` int(1) NOT NULL default '2',
  `created_on` datetime NULL,
  `update_on` datetime NULL,
  PRIMARY KEY (`offers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    " );


$installer->endSetup ();  
