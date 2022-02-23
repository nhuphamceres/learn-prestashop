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
class ConfigureMessage
{
    const _MESSAGE_ = '<div class="%s">%s</div>';
    const _DEBUG_ = '<div class="%s"><pre>%s</pre></div>';

    const _CLASS_ERROR_15_ = 'error';
    const _CLASS_WARNING_15_ = 'warn';
    const _CLASS_SUCCESS_15_ = 'conf';
    const _CLASS_INFO_15_ = 'info hint';

    const _CLASS_ERROR_16_ = 'alert alert-danger';
    const _CLASS_WARNING_16_ = 'alert alert-warning';
    const _CLASS_SUCCESS_16_ = 'alert alert-success';
    const _CLASS_INFO_16_ = 'alert alert-info';

    protected static $msg_list        = array();
    protected static $has_error_msg   = false;
    protected static $has_warning_msg = false;
    protected static $has_success_msg = false;
    protected static $has_info_msg    = false;


    /**
     * Generate the HTML code corresponding to an error message
     *
     * @param String $msg
     * return void|null
     */
    public static function error($msg = 'Error')
    {
        if (is_array($msg) && count($msg)) {
            foreach ($msg as $ps_error) {
                self::error($ps_error);
            }

            return null;
        }

        $error_msg = null;

        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $error_msg = sprintf(self::_MESSAGE_, self::_CLASS_ERROR_15_, $msg);
        } else {
            $error_msg = '<div class="bootstrap">'.sprintf(self::_MESSAGE_, self::_CLASS_ERROR_16_, $msg).'</div>';
        }

        self::$has_error_msg = true;

        self::$msg_list[] = $error_msg;
    }


    /**
     * Generate the HTML code corresponding to a warning message
     *
     * @param String $msg
     */
    public static function warning($msg = 'Warning')
    {
        $warning_msg = null;

        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $warning_msg = sprintf(self::_MESSAGE_, self::_CLASS_WARNING_15_, $msg);
        } else {
            $warning_msg = '<div class="bootstrap">'.sprintf(self::_MESSAGE_, self::_CLASS_WARNING_16_, $msg).'</div>';
        }

        self::$has_warning_msg = true;

        self::$msg_list[] = $warning_msg;
    }


    /**
     * Generate the HTML code corresponding to a success message
     *
     * @param String $msg
     */
    public static function success($msg = 'Success')
    {
        $success_msg = null;

        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $success_msg = sprintf(self::_MESSAGE_, self::_CLASS_SUCCESS_15_, $msg);
        } else {
            $success_msg = '<div class="bootstrap">'.sprintf(self::_MESSAGE_, self::_CLASS_SUCCESS_16_, $msg).'</div>';
        }

        self::$has_success_msg = true;

        self::$msg_list[] = $success_msg;
    }


    /**
     * Generate the HTML code corresponding to an information message
     *
     * @param String $msg
     */
    public static function info($msg = 'Success')
    {
        $info_msg = null;

        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $info_msg = sprintf(self::_MESSAGE_, self::_CLASS_INFO_15_, $msg);
        } else {
            $info_msg = '<div class="bootstrap">'.sprintf(self::_MESSAGE_, self::_CLASS_INFO_16_, $msg).'</div>';
        }

        self::$has_info_msg = true;

        self::$msg_list[] = $info_msg;
    }


    /**
     * Generate the HTML code corresponding to a debug message
     *
     * @param String $msg
     */
    public static function debug($msg = 'Debug')
    {
        $info_msg = null;

        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $info_msg = sprintf(self::_DEBUG_, self::_CLASS_INFO_15_, $msg);
        } else {
            $info_msg = '<div class="bootstrap">'.sprintf(self::_DEBUG_, self::_CLASS_INFO_16_, $msg).'</div>';
        }

        self::$msg_list[] = $info_msg;
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
     * @return Array
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
}
