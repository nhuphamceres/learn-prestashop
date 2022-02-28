<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Feed.biz
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Feed.biz is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Feed.biz.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class MiraklLogger
{
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const SUCCESS = 'SUCCESS';

    const CHANNEL_ORDER_IMPORT = 'order_import';

    protected $validLocations = array(
        self::CHANNEL_ORDER_IMPORT,
    );

    protected $filePath;

    /**
     * AmazonLogger constructor.
     * @param string|array $locations
     */
    public function __construct($locations, $overrideFileName = '')
    {
        if (is_string($locations)) {
            $locations = array($locations);
        }

        // Path
        $logPath = dirname(dirname(__FILE__)) . "/logs/";
        foreach ($locations as $location) {
            if (in_array($location, $this->validLocations)) {
                $logPath .= "$location/";
            }
        }

        // File name
        if ($overrideFileName) {
            $logPath .= "$overrideFileName.log";
        } else {
            $today = date('Y-m-d');
            $logPath .= "$today.log";
        }

        $this->filePath = $logPath;
    }

    public function debug($message, $context = array())
    {
        $this->log($message, self::DEBUG, $context);
    }

    public function info($message, $context = array())
    {
        $this->log($message, self::INFO, $context);
    }

    public function warn($message, $context = array())
    {
        $this->log($message, self::WARNING, $context);
    }

    public function error($message, $context = array())
    {
        $this->log($message, self::ERROR, $context);
    }

    public function success($message, $context = array())
    {
        $this->log($message, self::SUCCESS, $context);
    }

    protected function log($message, $level, $context)
    {
        if ($this->filePath) {
            $content = count($context) ?
                sprintf("%s > %s > %s\n%s\n", date('H:i:s', time()), $level, $message, print_r($context, true)) :
                sprintf("%s > %s > %s\n", date('H:i:s', time()), $level, $message);
            @file_put_contents($this->filePath, $content, FILE_APPEND);
        }
    }
}
