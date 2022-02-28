<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
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
 * @author    Tran Pham
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

class MiraklMarketplace
{
    const MKP_TEMP = 'temp';
    const LEROY_MERLIN = 'leroymerlin';
    const GO_SPORT = 'gosport';
    const MATY = 'maty';
    const RIPLEY = 'ripley';
    const VENCA = 'venca';
    const TWIL = 'twil';
    const EL_CORTE_INGLES = 'elcorteingles';
    const SHOWROOMPRIVE = 'showroomprive';
    const SHOWROOMPRIVE_DEV = 'showroomprive.dev';
    const SHOWROOMPRIVE_DROPSHIP = 'showroomprive.dropship';

    // Order importing: Prices are tax included
    const CONFORAMA = 'conforama';
    const UBALDI = 'ubaldi';
    const RUE_DU_COMMERCE = 'rueducommerce';
    const DARTY = 'darty';
    const WORTEN = 'worten';
    const MACWAY = 'macway';

    const CARREFOUR = 'carrefour';  // todo: Migrate to 2 specific mkp below
    const CARREFOUR_ES = 'carrefour_es';
    const CARREFOUR_FR = 'carrefour_fr';

    const DISPLAY_NAME_LEROY_MERLIN = 'Leroy Merlin';
    const DISPLAY_NAME_GO_SPORT = 'Go-Sport';
    const DISPLAY_NAME_RUE_DU_COMMERCE = 'Rue du Commerce';
    const DISPLAY_NAME_UBALDI = 'Ubaldi';
    const DISPLAY_NAME_EL_CORTE_INGLES = 'El Corte Ingles';

    const NATURE_DECOUVERTES = 'naturedecouvertes';

    // Configuration
    const PRODUCT_ID_TYPE_EAN = 'EAN';
    const PRODUCT_ID_TYPE_GTIN = 'GTIN';    // Carethy understand GTIN as EAN
    const PRODUCT_ID_TYPE_SKU = 'SHOP_SKU';

    // todo: Can we take advance of new property <order_tax_mode>TAX_INCLUDED</order_tax_mode>
    public static $oiPricesTaxIncludedMkps = array(
        self::CONFORAMA,
        self::UBALDI,
        self::RUE_DU_COMMERCE,
        self::DARTY,
        self::LEROY_MERLIN,
        self::WORTEN,
        self::MACWAY,
        self::VENCA,
        self::SHOWROOMPRIVE,
        self::SHOWROOMPRIVE_DEV,
        self::SHOWROOMPRIVE_DROPSHIP,
    );
    
    public static $ouDeliveryStatusMkps = array(
        self::EL_CORTE_INGLES,
    );

    protected static $eanExemption = array(
        self::MATY,
        self::RIPLEY,
        self::VENCA,
        self::TWIL,
    );

    /** @var string MD5 of the shop name, to fetch merchant's marketplace file */
    public static $shop_name_md5;

    /** @var string Where to fetch the merchant's marketplace file. */
    public static $mkps_bucket = 'https://s3-us-west-2.amazonaws.com/common-services-public/mirakl/ini/';

    /** @var string ID of the currently selected marketplace */
    // todo: Use getCurrentMarketplaceR() instead
    public static $current_mkp;

    /** @var array List of available marketplaces for the merchant */
    protected static $mkps = array();

    /** @var array All enabled marketplaces */
    public $availableMkps = array();

    /**
     * Get list of marketplaces
     * @return array
     */
    public static function getMarketplaces()
    {
        return self::$mkps;
    }

    /**
     * @deprecated use getCurrentMarketplaceR() instead
     * Get key of current marketplace
     * @return string
     */
    public static function getCurrentMarketplace()
    {
        return self::$current_mkp;
    }

    /**
     * Get shop name as md5 encryption. This is used as config file name to get list of marketplaces.
     * @return string
     */
    public static function getConfigFileName()
    {
        return self::$shop_name_md5;
    }

    /** @var MiraklMarketplace[] */
    private static $instances = array();

    public static function getInstance($currentMkp, $mkpData)
    {
        // Resolve store context (id_shop & id_shop_group)
        // Copy from init. This may not support multi-stores
        $id_shop = Tools::getValue('id_shop', null);
        $id_shop_group = Tools::getValue('id_shop_group', null);
        if (!$id_shop && !$id_shop_group && Tools::strlen(Tools::getValue('context_key'))) {
            $context_key = Tools::getValue('context_key');
            $stored_contexts = unserialize(MiraklTools::decode(Configuration::getGlobalValue(Mirakl::CONFIG_CONTEXT_DATA))); // TODO: Validation: Configuration Requirement
            if (isset($stored_contexts[$context_key])) {
                $id_shop = $stored_contexts[$context_key]->id_shop;
                $id_shop_group = $stored_contexts[$context_key]->id_shop_group;
            }
        }

        // Build instance key
        $idShop = (int)$id_shop;
        $idShopGroup = (int)$id_shop_group;
        $instanceKey = "$idShop-$idShopGroup";

        if (!isset(self::$instances[$instanceKey])) {
            self::$instances[$instanceKey] = new self($currentMkp, $mkpData);
        }

        return self::$instances[$instanceKey];
    }

    public $name;
    public $displayName;
    public $endpoint;
    public $ean_field_name = self::PRODUCT_ID_TYPE_EAN;
    public $productIDPrefix = '';
    // array configuration of a marketplace of a store context
    protected $currentMkp;
    protected $exclude;
    protected $exclude_product_update;
    protected $fields;
    protected $options;
    protected $additional;
    protected $conditions;
    protected $isEanExemption = false;
    protected $doesUpdateDeliveredOrders = false;
    public $logo;
    // todo: Maybe not used parameters
    public $categorySeparator;

    private function __construct($current_mkp, $marketplace)
    {
        $this->availableMkps = self::$mkps;
        $this->currentMkp = $current_mkp;
        $this->name = $marketplace['name'];
        $this->displayName = $marketplace['display_name'];
        $this->endpoint = $marketplace['endpoint'];
        $this->isEanExemption = in_array($current_mkp, self::$eanExemption);
        $this->productIDPrefix = $current_mkp == self::NATURE_DECOUVERTES ? 'EAN|' : '';

        // todo: Already parsed in init()
        $this->exclude = $marketplace['exclude'];
        $this->exclude_product_update = $marketplace['exclude_product_update'];
        $this->fields = $marketplace['fields'];
        $this->options = $marketplace['options'];
        $this->additional = $marketplace['additionnals'];
        $this->conditions = $marketplace['conditions'];
        $this->logo = $marketplace['logo'];
        $this->categorySeparator = isset($marketplace['category_separator']) ? $marketplace['category_separator'] : '';
        $this->ean_field_name = (isset($marketplace['ean_field_name']) && $marketplace['ean_field_name'])
            ? $marketplace['ean_field_name'] : self::PRODUCT_ID_TYPE_EAN;
        $this->doesUpdateDeliveredOrders = isset($marketplace['does_update_delivered_orders']) && $marketplace['does_update_delivered_orders'];
    }

    public function getCurrentMarketplaceR()
    {
        return $this->currentMkp;
    }

    public function getDisplayNameR()
    {
        return $this->displayName;
    }

    public function getExcludeProductUpdate()
    {
        return $this->exclude_product_update;
    }

    public function isEanExemption()
    {
        return $this->isEanExemption;
    }

    public function hasUpdateOrderDeliveryStatus()
    {
        return in_array($this->getCurrentMarketplaceR(), self::$ouDeliveryStatusMkps) && $this->doesUpdateDeliveredOrders;
    }

    public function hasAdditionalConfiguration()
    {
        return in_array(
            $this->currentMkp,
            array_map(function ($sf) {
                return $sf['name'];
            }, self::getAllSpecificFields())
        );
    }

    public function getSpecificFields()
    {
        $currentMkp = $this->getCurrentMarketplaceR();

        return current(array_filter(MiraklMarketplace::getAllSpecificFields(), function ($sf) use ($currentMkp) {
            return $sf['name'] == $currentMkp;
        }));
    }

    // todo: Translation
    public static function getAllSpecificFields()
    {
        return array(
            array(
                'name' => self::LEROY_MERLIN,
                'display_name' => self::DISPLAY_NAME_LEROY_MERLIN,
                'specific_fields' => array(
                    'shipment_origin' => array(
                        'type' => 'select',
                        'api' => 'shipment-origin', // column label in export file
                        'label' => 'Shipment origin',
                        'value' => array('AD','AL','AT','BE','BG','CH','CN','CY','CZ','DE','DK','EE','ES','FI','FR','GB','GR','HR','HU','IE','IS','IT','LI','LT','LU','LV','MC','MD','MK','MT','NL','NO','PL','PT','RO','SE','SI','SK','SM','UA'),
                    ),
                    // Keep the db key for compatible
                    'vat_type' => array(
                        'type' => 'select',
                        'api' => 'vat-lmfr',
                        'label' => 'TVA Leroy Merlin FR',
                        'value' =>  array('Standard', 'Reduced'),
                    ),
                    'vat_type_it' => array(
                        'type' => 'select',
                        'api' => 'vat-lmit',
                        'label' => 'TVA Leroy Merlin IT',
                        'value' =>  array('Standard', 'Reduced'),
                    ),
                ),
            ),
            array(
                'name' => self::GO_SPORT,
                'display_name' => self::DISPLAY_NAME_GO_SPORT,
                'specific_fields' => array(
                    'shipment_origin' => array(
                        'type' => 'select',
                        'api' => 'shippingfrom',
                        'label' => 'Shipment origin',
                        'value' =>  array('AT','BE','BG','CY','CZ','DE','DK','EE','EL','ES','FI','FR','HR','HU','IE','IT','LT','LU','LV','MT','NL','PL','PT','RO','SE','SI','SK','Out_of_EU'),
                    ),
                ),
            ),
            array(
                'name' => self::RUE_DU_COMMERCE,
                'display_name' => self::DISPLAY_NAME_RUE_DU_COMMERCE,
                'specific_fields' => array(
                    'shipment_origin' => array(
                        'type' => 'select',
                        'api' => 'shippingfrom',
                        'label' => 'Shipping from',
                        'value' => array('FRA','ESP','DEU','ITA','CHN','BEL'),
                    ),
                )
            ),
            array(
                'name' => self::UBALDI,
                'display_name' => self::DISPLAY_NAME_UBALDI,
                'specific_fields' => array(
                    'shipment_from_eu' => array(
                        'type' => 'select',
                        'api' => 'shipped-from-eu-zone',
                        'label' => 'Shipped from EU Zone',
                        'value' => array('yes', 'no'),
                    ),
                )
            ),
            array(
                'name' => self::EL_CORTE_INGLES,
                'display_name' => self::DISPLAY_NAME_EL_CORTE_INGLES,
                'specific_fields' => array(
                    'referencia_generica_eci' => array(
                        'type' => 'input',
                        'api' => 'referenciagenericaeci',
                        'label' => 'Referencia generica ECI',
                        'value' => '',
                    ),
                )
            ),
        );
    }

    // todo: Migrate to OOP
    public static function init()
    {
        $id_shop = Tools::getValue('id_shop', null);
        $id_shop_group = Tools::getValue('id_shop_group', null);

        if (!$id_shop && !$id_shop_group && Tools::strlen(Tools::getValue('context_key'))) {
            $context_key = Tools::getValue('context_key');
            $stored_contexts = unserialize(MiraklTools::decode(Configuration::get(Mirakl::CONFIG_CONTEXT_DATA))); // TODO: Validation: Configuration Requirement
            if (isset($stored_contexts[$context_key])) {
                $id_shop = $stored_contexts[$context_key]->id_shop;
                $id_shop_group = $stored_contexts[$context_key]->id_shop_group;
            }
        }

        $shop_name = md5(Configuration::get('PS_SHOP_NAME', null, $id_shop_group, $id_shop));
        $mkp_files = new SplFileInfo(_PS_MODULE_DIR_.'mirakl/mkps/' . $shop_name . '.ini');

        if ($mkp_files->isFile()) {
            if ($mkp_files->isReadable() && $mkp_files->getSize() > 10) {
                $ini_file_exist = true;
                $marketplaces = parse_ini_file($mkp_files->getRealPath(), true);

                if ($marketplaces == false) {
                    var_dump('Unable to parse the ini file.');
                }
            } else {
                var_dump(
                    'Unable to read the ini file.',
                    $mkp_files->getRealPath(),
                    $mkp_files->getPerms(),
                    $mkp_files->isReadable(),
                    $mkp_files->getSize()
                );
            }
        } else {
            $ini_file_exist = false;
            // Download ini file for this shop and store.
            if (self::downloadInit($mkp_files, $shop_name)) {
                $marketplaces = parse_ini_file($mkp_files->getRealPath(), true);
            } else {
                $marketplaces = array();
            }
        }

        if (is_array($marketplaces) && count($marketplaces)) {
            $current_mkp = array_keys($marketplaces);
            $current_mkp = reset($current_mkp);
            $current_mkp = Tools::getValue('selected-mkp', $current_mkp);
        } else {
            $current_mkp = self::MKP_TEMP;
            $marketplaces = array(
                self::MKP_TEMP => array(
                    'name'          => 'Mirakl',
                    'display_name'  => 'Mirakl',
                    'endpoint'      => 'https://moa-recette.mirakl.net/api/',
                    'lang'          => 'en',    // Fix this
                    'email'         => 'support@mirakl.com'
                )
            );
        }

        if ($current_mkp == 'afound') {
            $current_mkp = 'afoundse';
        }

        // Marketplace data
        $marketplace = (array)$marketplaces[$current_mkp];

        // Fill in additional data
        $marketplace['exclude'] = array_key_exists('exclude', $marketplace) ? self::parseExcludeFields($marketplace) : array();
        $marketplace['exclude_product_update'] = array_key_exists('exclude_product_update', $marketplace) ? self::parseExcludeProductUpdateFields($marketplace) : array();
        $marketplace['fields'] = array_key_exists('optionnal_fields', $marketplace) ? self::parseOptionalFields($marketplace) : array();
        $marketplace['options'] = array_key_exists('options', $marketplace) ? self::parseOptionsFields($marketplace) : array();
        $marketplace['additionnals'] = array_key_exists('additionnal_fields', $marketplace) ? self::parseAdditionalFields($marketplace) : array();
        $marketplace['conditions'] = array_key_exists('conditions', $marketplace) ? self::parseConditions($marketplace) : array();

        self::$current_mkp      = $current_mkp;
        self::$mkps             = $marketplaces;
        self::$shop_name_md5    = $shop_name;

        // Fill in logos, force download if ini file is not exist before
        return self::getLogo($marketplace, !$ini_file_exist);
    }

    /**
     * Get path of logos. All paths are relative compare to root image directory
     * @param $mkp_data
     * @param $force
     * @return mixed
     */
    private static function getLogo($mkp_data, $force)
    {
        $imgDir = _PS_MODULE_DIR_ . 'mirakl/views/img/';
        $mkpDir = 'marketplace/';
        $imgMkpDir = $imgDir . $mkpDir;

        $defaultLogoKey = 'logo';
        $defaultLogo = 'logo.png';
        if (isset($mkp_data[$defaultLogoKey])) {
            $remoteUrl = $mkp_data[$defaultLogoKey];
            $logoSegment = explode('/', $remoteUrl);
            if ($logoSegment && count($logoSegment)) {
                $logoFileName = array_pop($logoSegment);
                $localPath = $imgMkpDir . $logoFileName;
                $localRelativePath = $mkpDir . $logoFileName;
                // Replace remote logo by local logo (after download)
                $logoPath = self::downloadFile($localPath, $remoteUrl, $force) ? $localRelativePath : $defaultLogo;
                $mkp_data['marketplace_logo'] = $logoPath;
                // todo: Remove legacy logo key
                $mkp_data['logo'] = $logoPath;
            }
        }

        // Fallback logo
        if (!isset($mkp_data['marketplace_logo'])) {
            $mkp_data['marketplace_logo'] = 'banner.png';
        }
        if (!isset($mkp_data['logo'])) {
            $mkp_data['logo'] = 'banner.png';
        }

        return $mkp_data;
    }

    /**
     * Download remote file to local path
     * @param $local
     * @param $remote
     * @param bool $force
     * @return bool
     */
    private static function downloadFile($local, $remote, $force = false)
    {
        if (!file_exists($local) || $force) {
            if ($image = Tools::file_get_contents($remote, false, null, 30)) {
                if (!file_put_contents($local, $image)) {
                    return (false);
                }
            } else {
                return (false);
            }
        }

        return ($local);
    }

    /**
     * @param SplFileInfo $mkp_files
     * @param string $shop_name
     * @return bool|int
     */
    private static function downloadInit($mkp_files, $shop_name)
    {
        $ini_file_content = @MiraklTools::fileGetContents(// Validation: silence error if file not exist
            self::$mkps_bucket . $shop_name . '.ini'
        );

        $parsed_ini_file = parse_ini_string($ini_file_content, true);
        if (is_array($parsed_ini_file) && count($parsed_ini_file)) {
            try {
                $new_ini_file = new SplFileObject($mkp_files->getPath() . '/' . $mkp_files->getBasename(), 'w+');
            } catch (LogicException $e) {
                Tools::dieOrLog($e->getMessage(), false);
            } catch (RuntimeException $e) {
                Tools::dieOrLog($e->getMessage(), false);
            }

            /** @var SplFileObject $new_ini_file */
            return $new_ini_file->fwrite($ini_file_content);
        }

        return false;
    }

    /**
     * Optional custom fields
     * @param $marketplace_ini
     * @return array
     */
    private static function parseExcludeFields($marketplace_ini)
    {
        $fields = array();

        $exclude_fields = explode(',', $marketplace_ini['exclude']);

        if (is_array($exclude_fields) && count($exclude_fields)) {
            foreach ($exclude_fields as $exclude_field) {
                if (strpos($exclude_field, '[')) {
                    preg_match('#([^\[]+)\[(\w+)\]#i', $exclude_field, $result);

                    if (is_array($result) && count($result) > 2) {
                        if (stripos($result[2], 'offers') !== false && stripos($_SERVER['SCRIPT_NAME'], 'products_update') !== false) {
                            $exclude_field = $result[1];
                        } elseif (stripos($result[2], 'products') !== false && stripos($_SERVER['SCRIPT_NAME'], 'products_create') !== false) {
                            $exclude_field = $result[1];
                        } else {
                            continue;
                        }
                    }
                }
                $fields[$exclude_field] = true;
            }
        }

        return ($fields);
    }

    /**
     * Optional custom fields
     * @param $marketplace_ini
     * @return array
     */
    private static function parseExcludeProductUpdateFields($marketplace_ini)
    {
        $fields = array();

        $exclude_fields = explode(',', $marketplace_ini['exclude_product_update']);

        if (is_array($exclude_fields) && count($exclude_fields)) {
            foreach ($exclude_fields as $exclude_field) {
                $fields[] = $exclude_field;
            }
        }

        return ($fields);
    }

    private static function parseOptionalFields($marketplace_ini)
    {
        $fields = array();

        $additional_fields = explode(',', $marketplace_ini['optionnal_fields']);

        if (is_array($additional_fields) && count($additional_fields)) {
            foreach ($additional_fields as $additional_field) {
                if (array_key_exists($additional_field, $marketplace_ini)) {
                    $params = explode(',', $marketplace_ini[$additional_field]);

                    if (is_array($params) && count($params) == 3) {
                        $field = array();
                        $field['mirakl'] = $params[0];
                        $field['prestashop'] = $params[1];
                        $field['default'] = $params[2];

                        $fields[] = $field;
                    }
                }
            }
        }

        return ($fields);
    }

    private static function parseOptionsFields($marketplace_ini)
    {
        $fields = array();

        $options_fields = explode(',', $marketplace_ini['options']);

        if (is_array($options_fields) && count($options_fields)) {
            foreach ($options_fields as $option_field) {
                $fields[$option_field] = true;
            }
        }

        return ($fields);
    }

    private static function parseAdditionalFields($marketplace_ini)
    {
        $fields = array();

        $additional_fields = explode(',', $marketplace_ini['additionnal_fields']);

        if (is_array($additional_fields) && count($additional_fields)) {
            foreach ($additional_fields as $additional_field) {
                if (array_key_exists($additional_field, $marketplace_ini)) {
                    $params = explode(',', $marketplace_ini[$additional_field]);

                    if (is_array($params)) {
                        $field = array();
                        $field['mirakl'] = $params[0];
                        $field['prestashop'] = isset($params[1]) ? $params[1] : null;
                        $field['default'] = isset($params[2]) ? $params[2] : null;
                        $field['required'] = isset($params[3]) ? (bool)$params[3] : false;

                        $fields[] = $field;
                    }
                }
            }
        }

        return ($fields);
    }

    private static function parseConditions($marketplace_ini)
    {
        $conditions = array();

        $conditions_text = preg_replace('/\s/', '', $marketplace_ini['conditions']);

        if (!Tools::strlen($conditions_text)) {
            return(false);
        }

        $conditions_fields = explode(',', $conditions_text);

        if (is_array($conditions_fields) && count($conditions_fields)) {
            foreach ($conditions_fields as $conditions_field) {
                $condition_set = explode(':', $conditions_field);

                if (count($condition_set) == 2) {
                    $state = MiraklTools::toKey($condition_set[0]);
                    $code = $condition_set[1];
                    $conditions[$state] = $code;
                }
            }
        }
        return ($conditions);
    }
}
