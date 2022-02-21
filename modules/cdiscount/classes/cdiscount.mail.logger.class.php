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

require_once(dirname(__FILE__).'/../classes/cdiscount.tools.class.php');

class CdiscountMailLogger extends Cdiscount
{
    public static $email_subjects = array(
        'fr' => 'Vous avez un nouveau message du module Cdiscount',
        'en' => 'You got a new message from Cdiscount module'
    );
    public static $messages       = array();
    private static $is_initialized   = false;
    private static $_debug         = false;
    private static $_active        = false;
    private static $mailto        = null;
    private static $_id_lang       = null;
    private static $_language      = null;

    public static function message($message)
    {
        if (!self::$is_initialized) {
            self::init();
        }

        if (!empty($message)) {
            self::$messages[] = $message;
        }
    }

    public static function init($debug = false)
    {
        $id_employee = Configuration::get('CDISCOUNT_EMPLOYEE');
        $employee = new Employee($id_employee ? $id_employee : 1);

        if ($debug) {
            self::$_debug = true;
        } else {
            self::$_debug = false;
        }

        if (!self::$_active) {
            register_shutdown_function(array('CdiscountMailLogger', 'send'));
            self::$_active = true;
        }
        if (!self::$mailto) {
            self::$mailto = Configuration::get('PS_SHOP_EMAIL');
        }

        if (!self::$_id_lang) {
            self::$_id_lang = $employee->id_lang;
            self::$_language = Language::getIsoById(self::$_id_lang);
        }
        self::$is_initialized = true;
    }

    public static function send()
    {
        if (!count(self::$messages)) {
            return (false);
        }

        if (!self::$is_initialized) {
            return (false);
        }

        if (isset(self::$email_subjects[self::$_language])) {
            $subject = self::$email_subjects[self::$_language];
        } else {
            $subject = self::$email_subjects['en'];
        }

        $template = 'reply_msg'; // template file
        $template_vars = array();
        $template_vars['{reply}'] = null;
        $template_vars['{link}'] = self::$mailto;
        $template_vars['{firstname}'] = self::$mailto;
        $template_vars['{lastname}'] = Configuration::get('PS_SHOP_NAME');

        foreach (self::$messages as $message) {
            $template_vars['{reply}'] .= nl2br($message);
        }
        try {
            Mail::Send(
                self::$_id_lang,
                $template, // template
                $subject, // subject
                $template_vars, // templateVars
                self::$mailto, // to
                'Cdiscount'
            );
        } catch (Exception $e) {
            return false;
        };

        return false;
    }
}
