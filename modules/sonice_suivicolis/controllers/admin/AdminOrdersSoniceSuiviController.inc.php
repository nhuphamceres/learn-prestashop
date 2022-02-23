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
 * ...........................................................................
 *
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 */

if (isset($_SERVER['DropBox']) && $_SERVER['DropBox']) {
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../config/config.inc.php'));
    require_once(readlink(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviTools.php'));
} else {
    require_once(dirname(__FILE__).'/../../../../config/config.inc.php');
    require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviTools.php');
}


class AdminSoniceOrdersControllerExt extends Module
{

    private $id_lang;
    private $module = 'sonice_suivicolis';
    protected $url;
    protected $path;
    protected $images;
    protected $debug;
    protected $conf;

    public $ps15x;
    public $ps16x;
    public $img;


    public function __construct($id_lang)
    {
        parent::__construct();
        $this->id_lang = $id_lang;
        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->module.'/';
        $this->path = str_replace('\\', '/', _PS_MODULE_DIR_).$this->module.'/';
        $this->img = $this->url.'views/img/';

        $this->ps16x = false;
        if (version_compare(_PS_VERSION_, '1.6', '>=')) {
            $this->ps16x = true;
        }

        $this->conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));
        $this->debug =  isset($this->conf['debug']) && $this->conf['debug'];
    }


    /**
     * @param $smarty Smarty
     * @return mixed
     */
    public function content($smarty)
    {
        $alert_class = array();
        $alert_class['danger'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $alert_class['warning'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        $alert_class['success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $alert_class['info'] = $this->ps16x ? 'alert alert-info' : 'info';

        $token = Configuration::get('SONICE_SUIVI_TOKEN');
        $id_shop = isset(Context::getContext()->shop) && Validate::isLoadedObject(Context::getContext()->shop) ?
            (int)Context::getContext()->shop->id : (int)Configuration::get('PS_SHOP_DEFAULT');

        $smarty->assign(array(
            'snsc_img_dir' => $this->img,
            'snsc_webservice_url' => $this->url.'functions/getparcels.php?id_shop='.$id_shop.'&token='.$token,
            'snsc_sendmail_url' => $this->url.'functions/sendmails.php?id_shop='.$id_shop.'&token='.$token,
            'snsc_orderstate_template' => $this->getOrderStateTemplate(),
            'snsc_demo' => $this->demoIsEnable(),
            'snsc_debug' => $this->debug,
            'alert_class' => $alert_class,
            'snsc_id_shop' => (int)Context::getContext()->shop->id,
            'snsc_token_order' => Tools::getAdminToken(
                'AdminOrders'.(int)Tab::getIdFromClassName('AdminOrders').(int)$this->context->employee->id
            )
        ));



        return $smarty->fetch($this->path.'views/templates/admin/tab/adminOrdersSoniceSuivi.tpl');
    }


    public function getOrderShippedStates()
    {
        $states = null;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $states = Db::getInstance()->executeS(
                'SELECT osl.`name`
                     FROM `'._DB_PREFIX_.'order_state_lang` osl, `'._DB_PREFIX_.'order_state` os
                     WHERE os.`shipped` = 1
                     AND os.`id_order_state` = osl.`id_order_state`
                     AND osl.`id_lang` = '.(int)$this->id_lang
            );
        } else {
            $states = Db::getInstance()->executeS(
                'SELECT osl.`name`
                     FROM `'._DB_PREFIX_.'order_state_lang` osl, `'._DB_PREFIX_.'order_state` os
                     WHERE os.`delivery` = 1
                     AND os.`id_order_state` = osl.`id_order_state`
                     AND osl.`id_lang` = '.(int)$this->id_lang
            );
        }

        return ($states);
    }


    public function getOrderStateTemplate()
    {
        $states = array();
        $state_list = OrderState::getOrderStates($this->id_lang);
        $mapping_conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_MAPPING'));

        if (is_array($mapping_conf) && count($mapping_conf)) {
            $orderstates = array_keys($mapping_conf);
        } else {
            return (false);
        }

        foreach ($state_list as $state) {
            if (in_array($state['id_order_state'], $orderstates)) {
                $states[] = $state;
            }
        }

        return ($states);
    }


    public function demoIsEnable()
    {
        return isset($this->conf['demo']) && $this->conf['demo'];
    }
}
