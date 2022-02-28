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

class AmazonLogger
{
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const SUCCESS = 'SUCCESS';

    const LIFE_TIME = 7776000;    // 3 * 30 * 24 * 3600;

    const CHANNEL_PRIME = 'prime';
    const CHANNEL_VCS = 'vcs';
    const CHANNEL_ORDER_IMPORT = 'order_import';
    const CHANNEL_ORDER_UPDATE_STATUS = 'order_fulfillment';

    const SUB_VCS_UPLOAD = 'upload';
    const SUB_VCS_GET = 'get';
    const SUB_OI_ACK = 'acknowledge';

    protected $validLocations = array(
        self::CHANNEL_PRIME,
        self::CHANNEL_VCS,
        self::SUB_VCS_UPLOAD,
        self::SUB_VCS_GET,
        self::CHANNEL_ORDER_IMPORT,
        self::SUB_OI_ACK,
        self::CHANNEL_ORDER_UPDATE_STATUS,
    );

    protected $filePath;

    public function clearOldLogs()
    {
        $allLocations = array(
            self::CHANNEL_ORDER_IMPORT,
            array(self::CHANNEL_ORDER_IMPORT, self::SUB_OI_ACK),
            self::CHANNEL_ORDER_UPDATE_STATUS,
            self::CHANNEL_PRIME,
            array(self::CHANNEL_VCS, self::SUB_VCS_GET),
            array(self::CHANNEL_VCS, self::SUB_VCS_UPLOAD),
            array(self::CHANNEL_VCS, self::SUB_VCS_UPLOAD),
        );

        foreach ($allLocations as $location) {
            $logPath = $this->constructLogPath($location);
            $logs = glob($logPath . '*.log');
            if ($logs && is_array($logs)) {
                foreach ($logs as $log) {
                    if ((time() - filemtime($log)) > self::LIFE_TIME) {
                        unlink($log);
                    }
                }
            }
        }
    }

    /**
     * AmazonLogger constructor.
     * @param string|array $locations
     */
    public function __construct($locations, $overrideFileName = '')
    {
        $logPath = $this->constructLogPath($locations);
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

    protected function constructLogPath($locations)
    {
        if (is_string($locations)) {
            $locations = array($locations);
        }

        $logPath = dirname(dirname(__FILE__)) . "/logs/";
        foreach ($locations as $location) {
            if (in_array($location, $this->validLocations)) {
                $logPath .= "$location/";
            }
        }

        return $logPath;
    }
}
