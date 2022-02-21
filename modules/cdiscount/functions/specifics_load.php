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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../cdiscount.php');

require_once(dirname(__FILE__).'/../classes/cdiscount.specificfield.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');

class CDiscountSpecificLoad extends CDiscount
{
    private $_debug;

    public function __construct()
    {
        parent::__construct();
        CDiscountContext::restore($this->context);
        $this->_debug = (int)Configuration::get(parent::KEY.'_DEBUG') ? true : false;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function action()
    {
        $categoryId = Tools::getValue('id_category');
        $model_id = Tools::getValue('id_model');
        $modelInternalId = Tools::getValue('modelInternalId');

        if (!$model_id || !$modelInternalId || !$categoryId) {
            $tpl = '';
        } else {
            $tpl = $this->context->smarty->assign(array(
                'specific_fields' => CDiscountSpecificField::displayFieldset($model_id, $modelInternalId, $categoryId)
            ))->fetch(_PS_MODULE_DIR_ . $this->name . DS . 'views/templates/admin/configure/model/model_specific_data.tpl');
        }

        echo json_encode(array('error' => !$tpl, 'tpl' => $tpl));
    }
}

$marketplaceSpecificLoad = new CDiscountSpecificLoad();
$marketplaceSpecificLoad->action();
