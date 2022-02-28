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
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 * ...........................................................................
 *
 * @author    Debusschere A.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 */

if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Class generating configuration message
 */
class MiraklConfigureMessage
{
    const INFO      = 'info';
    const SUCCESS   = 'success';
    const WARNING   = 'warning';
    const ERROR     = 'error';

    protected static $msg_list        = array();
    protected static $has_error_msg   = false;
    protected static $has_warning_msg = false;
    protected static $has_success_msg = false;
    protected static $has_info_msg    = false;

    // Smart instance
    protected static $smarty;

    /**
     * Generate the HTML code corresponding to an error message
     * @param string|array $msg
     */
    public static function error($msg = 'Error')
    {
        self::$msg_list[] = self::messageDisplay(self::ERROR, $msg);
        self::$has_error_msg = true;
    }

    /**
     * Generate the HTML code corresponding to a warning message
     *
     * @param string|array $msg
     */
    public static function warning($msg = 'Warning')
    {
        self::$msg_list[] = self::messageDisplay(self::WARNING, $msg);
        self::$has_warning_msg = true;
    }

    /**
     * Generate the HTML code corresponding to a success message
     *
     * @param string|array $msg
     */
    public static function success($msg = 'Success')
    {
        self::$msg_list[] = self::messageDisplay(self::SUCCESS, $msg);
        self::$has_success_msg = true;
    }

    /**
     * Generate the HTML code corresponding to an information message
     *
     * @param string|array $msg
     */
    public static function info($msg = 'Success')
    {
        self::$msg_list[] = self::messageDisplay(self::INFO, $msg);
        self::$has_info_msg = true;
    }

    /**
     * Generate the HTML code corresponding to a debug message
     *
     * @param string|array $msg
     */
    public static function debug($msg = 'Debug')
    {
        self::$msg_list[] = self::messageDisplay(self::INFO, $msg);
    }

    /**
     * Return the html code of all messages to be displayed
     *
     * @return String
     */
    public static function display()
    {
        $html = '';

        if (count(self::$msg_list)) {
            foreach (self::$msg_list as $msg) {
                $html .= $msg;
            }
        }

        return ($html);
    }

    /**
     * Return the list of message
     *
     * @return array
     */
    public static function getMessageList()
    {
        return (self::$msg_list);
    }

    /**
     * Return true if the message list contain at least 1 error message
     *
     * @return Boolean
     */
    public static function hasErrorMessage()
    {
        return (self::$has_error_msg);
    }

    /**
     * Return true if the message list contain at least 1 warning message
     *
     * @return Boolean
     */
    public static function hasWarningMessage()
    {
        return (self::$has_warning_msg);
    }

    /**
     * Return true if the message list contain at least 1 success message
     *
     * @return Boolean
     */
    public static function hasSuccessMessage()
    {
        return (self::$has_success_msg);
    }

    /**
     * Return true if the message list contain at least 1 information message
     *
     * @return Boolean
     */
    public static function hasInfoMessage()
    {
        return (self::$has_info_msg);
    }

    /**
     * Use one default template for all messages
     * @return string
     */
    protected static function getTemplatePath()
    {
        return str_replace('\\', '/', dirname(__FILE__)).'/../../views/templates/admin/shared/message.tpl';
    }

    /**
     * Return well-format message
     * @param $type
     * @param $message
     * @return string
     */
    protected static function messageDisplay($type, $message)
    {
        if (!self::$smarty) {
            self::$smarty = Context::getContext()->smarty;
        }

        $below16 = version_compare(_PS_VERSION_, '1.6', '<');
        switch ($type) {
            case self::ERROR:
                $class = $below16 ? 'error' : 'alert alert-danger';
                break;
            case self::WARNING:
                $class = $below16 ? 'warn' : 'alert alert-warning';
                break;
            case self::SUCCESS:
                $class = $below16 ? 'conf' : 'alert alert-success';
                break;
            default:
                $class = $below16 ? 'info hint' : 'alert alert-info';
                break;
        }

        self::$smarty->assign(array('below16' => $below16, 'class' => $class, 'message' => $message));

        return self::$smarty->fetch(self::getTemplatePath());
    }
}
