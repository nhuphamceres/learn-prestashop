<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Common-Services Co., Ltd.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2017 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
  * Support by mail:  support.cdiscount@common-services.com
 */

require_once(dirname(__FILE__).'/../classes/cdiscount.webservice.class.php');
require_once(dirname(__FILE__).'/../common/configuration.class.php');

class CDiscountConfig extends CDiscount
{
    const EXPIRES = 14400;
    const CARRIERS_EXPIRES = 86400;

    const OFFER_POOL_CDISCOUNT = 1;
    const OFFER_POOL_BELGIUM = 14;
    const OFFER_POOL_PRO = 16;

    public static function getFilename()
    {
        $seller_informations_dir = dirname(__FILE__).DS.'..'.DS.Cdiscount::XML_DIRECTORY.DS;
        $seller_informations_file = $seller_informations_dir.'seller_informations.xml.cache.gz';

        return($seller_informations_file);
    }

    public static function removeCache()
    {
        $file = self::getFilename();
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public static function getSellerInformation($debug = false, $realconfig = false)
    {
        $cr = "<br />\n";

        $dev_mode = (bool)Configuration::get(parent::KEY.'_DEV_MODE');

        $sellerinformations = null;
        $seller_informations_dir = dirname(__FILE__).DS.'..'.DS.Cdiscount::XML_DIRECTORY.DS;
        $seller_informations_file = self::getFilename();

        $pass = false;

        /** @var Cdiscount $module */
        $module = Module::getInstanceByName('cdiscount');
        // In cache
        if (CDiscountTools::currentToken() && file_exists($seller_informations_file) && (time() - filectime($seller_informations_file)) < self::EXPIRES) {
            $module->debugDetails->webservice("Reading xml from cache: $seller_informations_file");

            $seller_informations_content = CDiscountTools::file_get_contents('compress.zlib://'.$seller_informations_file);

            if ($seller_informations_content && Tools::strlen($seller_informations_content)) {
                libxml_use_internal_errors(true);

                $seller_informations_xml = simplexml_load_string($seller_informations_content);

                libxml_clear_errors();

                if ($seller_informations_xml instanceof SimpleXMLElement) {
                    $module->debugDetails->webservice("Reading xml from cache successfully: $seller_informations_file");
                    $pass = true;
                }

                if (!isset($seller_informations_xml->SellerLogin) || $seller_informations_xml->SellerLogin != Configuration::get(parent::KEY.'_USERNAME')) {
                    $module->debugDetails->webservice("Force expiration of cache: $seller_informations_file");
                    $pass = false;
                }
            }
        }

        // Refresh
        if (!$pass) {
            $module->debugDetails->webservice("Querying webservice - GetSellerInformation");

            $username = Configuration::get(parent::KEY.'_USERNAME');
            $password = Configuration::get(parent::KEY.'_PASSWORD');
            $debug = Configuration::get(parent::KEY.'_DEBUG') ? true : false;
            $production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);

            if (empty($username) || empty($password)) {
                $module->debugDetails->webservice("GetSellerInformation failed - login password not yet configured");
                return (false);
            }
            $webservice = new CDiscountWebService($username, $password, $production, $debug, $dev_mode);
            $webservice->token = CDiscountTools::auth();

            if (!$webservice->token) {
                $module->debugDetails->webservice("Auth failed");
                return (false);
            }

            if (!($seller_informations_xml = $webservice->GetSellerInformation()) instanceof SimpleXMLElement) {
                $module->debugDetails->webservice("GetSellerInformation failed");
                return (false);
            }

            // Cache
            if (file_exists($seller_informations_file)) {
                unlink($seller_informations_file);
            }

            if (CommonTools::isDirWriteable($seller_informations_dir)) {
                file_put_contents('compress.zlib://'.$seller_informations_file, $seller_informations_xml->saveXML());
            }

            $module->debugDetails->webservice("Querying webservice - GetSellerInformation - success");

            $pass = true;
        }

        $dom = dom_import_simplexml($seller_informations_xml)->ownerDocument;
        $dom->formatOutput = true;
        $module->debugDetails->webservice("Webservice result", htmlspecialchars($dom->saveXML()));

        if ($pass) {
            $offerpool = $seller_informations_xml->xpath('//OfferPoolList/OfferPool');
            $deliverymodeinformation = $seller_informations_xml->xpath('//DeliveryModes/DeliveryModeInformation');

            if (!is_array($offerpool) || !count($offerpool)) {
                printf('%s/%s: ERROR - GetSellerInformation - Failed to retrieve "OfferPool"'.$cr, basename(__FILE__), __LINE__);
            }

            if (!is_array($deliverymodeinformation) || !count($deliverymodeinformation)) {
                printf('%s/%s: ERROR - GetSellerInformation - Failed to retrieve "DeliveryModeInformation"'.$cr, basename(__FILE__), __LINE__);
            }

            $sellerinformations = array();
            $sellerinformations['CLogistique'] = false;
            $sellerinformations['OfferPool'] = Tools::jsonDecode(Tools::jsonEncode($offerpool), true);
            $sellerinformations['DeliveryModeInformation'] = Tools::jsonDecode(Tools::jsonEncode($deliverymodeinformation), true);

            if (!is_array($sellerinformations['DeliveryModeInformation']) || !is_array($sellerinformations['OfferPool'])) {
                $module->debugDetails->webservice("Unable to get data");
                return (false);
            }

            $offer_pools = &$sellerinformations['OfferPool'];
            $has_france = false;
            $has_belgium = false;
            $multitenant = false;

            if (is_array($offer_pools) && count($offer_pools)) {
                foreach ($offer_pools as $offer_pool) {
                    switch ($offer_pool['Id']) {
                        case self::OFFER_POOL_CDISCOUNT:
                            $has_france = true;
                            break;
                        case self::OFFER_POOL_BELGIUM:
                            $has_belgium = true;
                            break;
                        default:
                            $multitenant = true;
                            break;
                    }
                }
            }
            $sellerinformations['Multichannel'] = array();
            $sellerinformations['Multichannel']['France'] = $has_france;
            $sellerinformations['Multichannel']['Belgium'] = $has_belgium;
            $sellerinformations['Multichannel']['Multitenant'] = $multitenant;

            /*
                        // Patch: CDiscount doesnt return the merchant has clogistique, but there is an additionnal carrier in that case
                        if (!$realconfig && in_array((string)$seller_informations_xml->SellerLogin, self::$has_clogistique))
                        {
                            $seller_informations['CLogistique'] = true;
                            $seller_informations['DeliveryModeInformation'] = array_merge($seller_informations['DeliveryModeInformation'],
                                      array(
                                        array('Code' => 'TNT', 'Name' => 'TNT'),
                                        array('Code' => 'RCO', 'Name' => 'RelaisColis'),
                                        array('Code' => 'SO1', 'Name' => 'SoColissimo'),
                                        array('Code' => 'REL', 'Name' => 'MondialRelay')
                                        ));
                        }
            */
            $sellerinformations['Seller'] = array_fill_keys(array('State', 'ShopName', 'ShopUrl', 'IsAvailable'), null);

            $seller = $seller_informations_xml->xpath('//Seller');

            if (is_array($seller) && count($seller) && property_exists(reset($seller), 'IsAvailable')) {
                $sellerinformations['Seller'] = Tools::jsonDecode(Tools::jsonEncode(reset($seller)), true);
            }
        }

        return ($sellerinformations);
    }

    public static function GetGlobalConfiguration($debug = false)
    {
        $cr = "<br />\n";

        $dev_mode = (bool)Configuration::get(parent::KEY.'_DEBUG');

        $global_configuration = null;
        $global_configuration_dir = dirname(__FILE__).DS.'..'.DS.Cdiscount::XML_DIRECTORY.DS;
        $global_configuration_file = $global_configuration_dir.'carriers.xml.cache.gz';

        $pass = false;

        /** @var Cdiscount $module */
        $module = Module::getInstanceByName('cdiscount');
        // In cache
        if (file_exists($global_configuration_file)) {
            $module->debugDetails->webservice("Reading xml from cache: $global_configuration_file");

            $global_configuration_content = CDiscountTools::file_get_contents('compress.zlib://'.$global_configuration_file);

            if ($global_configuration_content && Tools::strlen($global_configuration_content)) {
                libxml_use_internal_errors(true);

                $global_configuration_xml = simplexml_load_string($global_configuration_content);

                libxml_clear_errors();

                if ($global_configuration_xml instanceof SimpleXMLElement) {
                    $module->debugDetails->webservice("Reading xml from cache successfully: $global_configuration_file");
                    $pass = true;
                }

                if (!isset($global_configuration_xml->SellerLogin) || $global_configuration_xml->SellerLogin != Configuration::get(parent::KEY.'_USERNAME')) {
                    $module->debugDetails->webservice("Force expiration of cache: $global_configuration_file");
                    $pass = false;
                }
            }
        }

        if (!file_exists($global_configuration_file) || (time() - filectime($global_configuration_file)) < self::CARRIERS_EXPIRES) {
            $pass = false;
        }

        // Refresh
        if (!$pass) {
            $module->debugDetails->webservice("Querying webservice - GetGlobalConfiguration");

            $username = Configuration::get(parent::KEY.'_USERNAME');
            $password = Configuration::get(parent::KEY.'_PASSWORD');
            $debug = (bool)Configuration::get(parent::KEY . '_DEBUG');
            $production = !(bool)Configuration::get(parent::KEY . '_PREPRODUCTION');

            if (empty($username) || empty($password)) {
                $module->debugDetails->webservice("GetGlobalConfiguration failed - login password not yet configured");
                return (false);
            }
            $webservice = new CDiscountWebService($username, $password, $production, $debug, $dev_mode);
            $webservice->token = CDiscountTools::auth();

            if (!$webservice->token) {
                $module->debugDetails->webservice("Auth failed");
                return (false);
            }

            if (!($global_configuration_xml = $webservice->GetGlobalConfigurationCarriers()) instanceof SimpleXMLElement) {
                $module->debugDetails->webservice("GetGlobalConfigurationCarriers failed...");
                return (false);
            }

            // Cache
            if (file_exists($global_configuration_file)) {
                unlink($global_configuration_file);
            }

            if (CommonTools::isDirWriteable($global_configuration_dir)) {
                file_put_contents('compress.zlib://'.$global_configuration_file, $global_configuration_xml->saveXML());
            }

            $module->debugDetails->webservice("Querying webservice - GetGlobalConfigurationCarriers - success");
        }

        $dom = dom_import_simplexml($global_configuration_xml)->ownerDocument;
        $dom->formatOutput = true;
        $module->debugDetails->webservice("Webservice result", htmlspecialchars($dom->saveXML()));

        $carriers = $global_configuration_xml->xpath('//CarrierList/Carrier');

        if (is_array($carriers) && count($carriers)) {
            $carrier_list = Tools::jsonDecode(Tools::jsonEncode($carriers), true);
            $global_configuration = array();

            if (is_array($carrier_list) && count($carrier_list)) {
                foreach ($carrier_list as $carrier) {
                    if (array_key_exists('CarrierId', $carrier)) {
                        $id_carrier = (int)$carrier['CarrierId'];
                        $global_configuration['Carriers'][$id_carrier] = $carrier;
                    }
                }
            }
        }
        return ($global_configuration);
    }
}
