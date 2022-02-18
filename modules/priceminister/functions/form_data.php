<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

if (isset($_SERVER['DropBox']) && $_SERVER['DropBox']) {
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config/config.inc.php'));
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../init.php'));
} else {
    @require_once(dirname(__FILE__).'/../../../config/config.inc.php');
    @require_once(dirname(__FILE__).'/../../../init.php');
}

require_once '../priceminister.php';
require_once(dirname(__FILE__).'/../classes/priceminister.tools.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.mappings.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.models.class.php');

class PriceMinisterFormData extends PriceMinister
{

    private $form;
    private $action;

    public function __construct()
    {
        parent::__construct();
        $this->loadGeneralModuleConfig();

        require_once(dirname(__FILE__).'/../classes/priceminister.api.webservices.php');
        require_once(dirname(__FILE__).'/../classes/priceminister.form.class.php');
        require_once(dirname(__FILE__).'/../classes/priceminister.api.products.class.php');

        $this->form = new PriceMinisterForm();
        $this->product_type = Tools::getValue('product_type');
        $this->action = Tools::getValue('action');
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return parent::l($string, basename(__FILE__, '.php'), $id_lang);
    }

    public function dispatch()
    {
        if (!$this->action) {
            die('No action selected');
        }

        $output = '';
        switch ($this->action) {
            case 'product_type_template':
                if (!$this->product_type) {
                    die('No product selected');
                }

                $product_type_template = $this->form->getProductTypeTemplate($this->product_type);
                $output = json_encode(
                    array(
                        'product_type_template' => $product_type_template,
                        'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
                    )
                );
                break;
            default:
                break;
        }

        echo $output;
    }
}

$formData = new PriceMinisterFormData();
$formData->dispatch();