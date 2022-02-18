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

require_once(_PS_MODULE_DIR_.'priceminister/common/support.class.php');

class PriceMinisterSupport extends CommonSupport
{

    const PRICEMINISTER_DOCUMENTATION_URL = 'http://documentation.common-services.com/priceminister';
    /* Create Products */
    const FUNCTION_EXPORT_EMPTY_REFERENCE = 'produits-ayant-une-reference-manquante';
    const FUNCTION_EXPORT_DUPLICATE = 'reference-en-double-pour-ces-produits';
    const FUNCTION_EXPORT_WRONG_EAN = 'code-ean-incorrect-pour-les-produits';
    const FUNCTION_EXPORT_DUPLICATE_EAN = 'codes-ean-en-double-pour-les-produits';
    const FUNCTION_EXPORT_MISSING_EAN = 'code-ean-manquant';
    public $lang;

    public function __construct($id_lang)
    {
        $this->setLang($id_lang);

        parent::__construct();
    }

    public function setLang($id_lang)
    {
        $lang = Language::getIsoById($id_lang);

        switch ($lang) {
            case 'fr':
                $lang = 'fr';
                break;
            default:
                $lang = 'en';
        }

        return $this->lang = $lang;
    }

    public static function message($type, $msg)
    {
        static $priceminister = null;

        if ($type) {
            if (!$priceminister instanceof PriceMinister) {
                $priceminister = new PriceMinister();
            }

            $lang = Language::getIsoById($priceminister->id_lang);

            $lang_param = sprintf('lang=%s', $lang);

            $support_found = $priceminister->l('An online support has been found on this topic, click on this link to obtain support');

            $link = sprintf('<a href="%s/%s?%s" title="%s" target="_blank">%s</a>', self::PRICEMINISTER_DOCUMENTATION_URL, $type, $lang_param, $support_found, $msg);

            return $link;
        }

        return $msg;
    }

    public function gethreflink($type = null)
    {
        $lang_param = sprintf('?lang=%s', $this->lang);
        $displayed_url = sprintf('%s/%s%s', self::PRICEMINISTER_DOCUMENTATION_URL, preg_replace('/(?<=^.{32}).{4,}(?=.{30}$)/', '...', $type), $lang_param);
        $link = sprintf('<a href="%s/%s" title="%s" target="_blank">%s</a>', self::PRICEMINISTER_DOCUMENTATION_URL, $type, $type, $displayed_url);

        return ($link);
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }
}