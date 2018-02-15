<?php
/*
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http:* If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http:*
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2015 PrestaShop SA
 *  @license    http:*  International Registered Trademark & Property of PrestaShop SA
 */
 
 
/*
 * Override from FeaturesSupplier module
 *
 */
 
class Product extends ProductCore {
	
   /*
    * Select all features for a given language
    *
    * @param $id_lang Language id
    * @return array Array with feature's data
    */
    public static function getFrontFeaturesStatic($id_lang, $id_product)
    {
		if(Configuration::get('FEATURESSUPPLIER_PIECE_ID') && Configuration::get('FEATURESSUPPLIER_PIECE_ID') != "" && Configuration::get('FEATURESSUPPLIER_PRODUIT_FINI_ID') && Configuration::get('FEATURESSUPPLIER_PRODUIT_FINI_ID') != ""){
			$features_to_hide = Configuration::get('FEATURESSUPPLIER_PIECE_ID').','.Configuration::get('FEATURESSUPPLIER_PRODUIT_FINI_ID');
		}
		
        if (!Feature::isFeatureActive()) {
            return array();
        }
		
		$sql = 'SELECT name, value, pf.id_feature
				FROM '._DB_PREFIX_.'feature_product pf
				LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = '.(int)$id_lang.')
				LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = '.(int)$id_lang.')
				LEFT JOIN '._DB_PREFIX_.'feature f ON (f.id_feature = pf.id_feature AND fl.id_lang = '.(int)$id_lang.')
				'.Shop::addSqlAssociation('feature', 'f').'
				WHERE pf.id_product = '.(int)$id_product.'
				'.($features_to_hide ? " AND f.id_feature NOT IN (".$features_to_hide.")" : "" ).'
				ORDER BY f.position ASC';
		
        if (!array_key_exists($id_product.'-'.$id_lang, self::$_frontFeaturesCache)) {
            self::$_frontFeaturesCache[$id_product.'-'.$id_lang] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        }
		
        return self::$_frontFeaturesCache[$id_product.'-'.$id_lang];
    }
}