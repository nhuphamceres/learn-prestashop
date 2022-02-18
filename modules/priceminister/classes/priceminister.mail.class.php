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

require_once(dirname(__FILE__).'/../classes/priceminister.api.webservices.php');
require_once(dirname(__FILE__).'/../classes/priceminister.sales.api.php');
require_once(dirname(__FILE__).'/../classes/priceminister.tools.class.php');

class PSPM_Mail
{

    public static function Send($id_lang, $template, $subject, $templateVars, $to, $toName = null, $from = null, $fromName = null, $fileAttachment = null, $modeSMTP = null, $templatePath = _PS_MAIL_DIR_)
    {
        $configuration = Configuration::getMultiple(array('PS_SHOP_EMAIL', 'PS_MAIL_METHOD', 'PS_MAIL_SERVER', 'PS_MAIL_USER', 'PS_MAIL_PASSWD', 'PS_SHOP_NAME', 'PS_MAIL_SMTP_ENCRYPTION', 'PS_MAIL_SMTP_PORT', 'PS_MAIL_METHOD', 'PS_MAIL_TYPE'));
        if (!isset($configuration['PS_MAIL_SMTP_ENCRYPTION'])) {
            $configuration['PS_MAIL_SMTP_ENCRYPTION'] = 'off';
        }
        if (!isset($configuration['PS_MAIL_SMTP_PORT'])) {
            $configuration['PS_MAIL_SMTP_PORT'] = 'default';
        }

        if (!isset($from)) {
            $from = $configuration['PS_SHOP_EMAIL'];
        }
        if (!isset($fromName)) {
            $fromName = $configuration['PS_SHOP_NAME'];
        }

        if (!empty($fromName) && !Validate::isMailName($fromName)) {
            die(Tools::displayError('error / mail: parameter "fromName" is corrupted'));
        }

        if (!is_array($templateVars)) {
            die(Tools::displayError('error / mail: parameter "templateVars" is not an array'));
        }

        // Do not crash for this error, that may be a complicated customer name
        if (!empty($toName) && !Validate::isMailName($toName)) {
            $toName = null;
        }

        if (!Validate::isTplName($template)) {
            die(Tools::displayError('error / mail: template name is corrupted'));
        }

        if (!Validate::isMailSubject($subject)) {
            die(Tools::displayError('error / mail: subject name is not valid'));
        }

        // Get templates content
        $iso = Language::getIsoById((int)$id_lang);
        if (!$iso) {
            die(Tools::displayError('Error - No iso code for email !'));
        }

        $templatePath = realpath(dirname(__FILE__).'/..').'/';
        $template = sprintf('%s_%s', $template, $iso);

        if (!file_exists($templatePath.$template.'.txt')) {
            die(Tools::displayError('Error - The following email template is missing:').' '.$templatePath.$template.'.txt');
        }

        $templateTxt = strip_tags(html_entity_decode(PriceMinisterTools::file_get_contents($templatePath.$template.'.txt'), null, 'utf-8'));

        include_once(_PS_ROOT_DIR_.'/mails/'.$iso.'/lang.php');

        $templateVars['{shop_name}'] = Tools::safeOutput(Configuration::get('PS_SHOP_NAME'));
        $templateVars['{shop_url}'] = 'http://'.Tools::getHttpHost(false, true).__PS_BASE_URI__;

        $message = $templateTxt;

        foreach ($templateVars as $var => $text) {
            $message = str_replace($var, $text, $message);
        }

        // **********************************************************************
        // Send the message throught the Price Minister API
        // **********************************************************************

        $lang = Language::getIsoById($id_lang);

        // PM Configuration
        //
        $config = array();
        $config = PriceMinisterTools::Auth($lang);

        $itemid = $templateVars['{itemid}'];

        $config['itemid'] = $itemid;
        $config['content'] = $message;

        $params = array();

        $oSales = new PM_Sales($config);
        $result = $oSales->contactuseraboutitem($params);

        return (true);
    }
}