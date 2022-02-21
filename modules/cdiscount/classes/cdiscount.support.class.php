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

class CDiscountSupport extends CDiscount
{
    const CDISCOUNT_DOCUMENTATION_URL = 'http://documentation.common-services.com/cdiscount';


    /* Create Products */
    const FUNCTION_EXPORT_EMPTY_REFERENCE = 'produits-ayant-une-reference-manquante';
    const FUNCTION_EXPORT_DUPLICATE = 'reference-en-double-pour-ces-produits';
    const FUNCTION_EXPORT_WRONG_EAN = 'code-ean-incorrect-pour-les-produits';
    const FUNCTION_EXPORT_DUPLICATE_EAN = 'codes-ean-en-double-pour-les-produits';
    const FUNCTION_EXPORT_MISSING_EAN = 'code-ean-manquant';

    /* Tutorials */
    const TUTORIAL_MODULE_UPDATE = 'apres-maj-du-module';
    const TUTORIAL_PHP = 'configuration-php';
    const TUTORIAL_CATEGORIES = 'selection-de-categories';

    public $lang;

    public function __construct($id_lang)
    {
        $lang = Language::getIsoById($id_lang);

        switch ($lang) {
            case 'fr':
                $lang = 'fr';
                break;
            default:
                $lang = 'en';
        }
        $this->lang = $lang;

        parent::__construct();
    }

    public function gethreflink($type = null)
    {
        $lang_param = sprintf('?lang=%s', $this->lang);
        $displayed_url = sprintf('%s/%s%s', self::CDISCOUNT_DOCUMENTATION_URL, preg_replace('/(?<=^.{32}).{4,}(?=.{30}$)/', '...', $type), $lang_param);
        $link = sprintf('<a href="%s/%s" title="%s" target="_blank">%s</a>', self::CDISCOUNT_DOCUMENTATION_URL, $type, $type, $displayed_url);

        return ($link);
    }

    public function message($msg, $type = null)
    {
        if ($type) {
            $lang_param = sprintf('?lang=%s', $this->lang);
            $displayed_url = sprintf('%s/%s%s', self::CDISCOUNT_DOCUMENTATION_URL, preg_replace('/(?<=^.{42}).{4,}(?=.{40}$)/', '...', $type), $lang_param);

            $support_found = $this->l('An online support has been found on this topic');
            $click = $this->l('Click on this link to obtain support');

            $link = sprintf('<a href="%s/%s" title="%s" target="_blank">%s</a>', self::CDISCOUNT_DOCUMENTATION_URL, $type, $type, $displayed_url);

            if ($msg) {
                $help_msg = $msg.'<br /><br />';
            } else {
                $help_msg = null;
            }

            $help_msg .= '<div class="support-msg">';
            $help_msg .= sprintf('%s, <br />', $support_found);
            $help_msg .= sprintf("%s: %s<br />\n", $click, $link);
            $help_msg .= '</div>';

            return $help_msg;
        }

        return $msg;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }
}
