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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class AmazonReportRequest
{
    const WSTR = AmazonWebService::WSTR_REPORTS;

    public $configKey;
    public $amzLang;
    public $reportType;
    public $expiredSeconds;

    public $lastTime;
    public $reportRequestId;
    public $reportId;

    /** @var AmazonWebService */
    protected $api;

    public function __construct($configKey, $amzLang, $reportType, $expiredSeconds, $api)
    {
        $this->configKey = $configKey;
        $this->amzLang = $amzLang;
        $this->reportType = $reportType;
        $this->expiredSeconds = $expiredSeconds;
        $this->api = $api;

        $requestInfo = AmazonConfiguration::get($configKey);
        if ($requestInfo && is_array($requestInfo) && isset($requestInfo[$amzLang]) && is_array($requestInfo[$amzLang])) {
            $lastRequestOfMkp = $requestInfo[$amzLang];
            $this->lastTime = isset($lastRequestOfMkp['last_time']) ? $lastRequestOfMkp['last_time'] : 0;
            $this->reportRequestId = isset($lastRequestOfMkp['report_request_id']) ? $lastRequestOfMkp['report_request_id'] : 0;
        }
    }

    public function shouldRequestListOrRequestReport()
    {
        // If we have request ID, use it to get the report. Otherwise, request a new one
        return $this->reportRequestId && $this->lastTime;
    }

    public function saveReportRequestId($requestId)
    {
        $this->reportRequestId = $requestId;
        $this->lastTime = time();
        $configForMkp = array('last_time' => $this->lastTime, 'report_request_id' => $this->reportRequestId);

        $saved = AmazonConfiguration::get($this->configKey);
        if (!$saved || !is_array($saved)) {
            $tobeSaved = array($this->amzLang => $configForMkp);
        } else {
            $saved[$this->amzLang] = $configForMkp;
            $tobeSaved = $saved;
        }

        AmazonConfiguration::updateValue($this->configKey, $tobeSaved);
    }

    public function reportRequestAPI($optionalParams, $mkpId)
    {
        $params = array_merge($optionalParams, array(
            'Action' => 'RequestReport',
            'MarketplaceIdList.Id.1' => $mkpId,
            'ReportType' => $this->reportType,
//            'ReportOptions' => 'reportoption=All',
        ));

        return $this->api->_simpleCallWs(self::WSTR, $params);
    }

    public function getRequestListAPIByRequestId()
    {
        if ($this->reportRequestId) {
            $params = array(
                'Action' => 'GetReportRequestList',
                'ReportRequestIdList.Id.1' => $this->reportRequestId,
            );

            return $this->api->_simpleCallWS(self::WSTR, $params);
        }

        return false;
    }

    public function getRequestListAPIBySearchCriteria()
    {
        $params = array(
            'Action' => 'GetReportRequestList',
            'ReportTypeList.Type.1' => $this->reportType,
            'ReportProcessingStatusList.Status.1' => '_DONE_',
            'RequestedFromDate' => gmdate('Y-m-d\TH:i:s\Z', time() - $this->expiredSeconds)
        );

        return $this->api->_simpleCallWS(self::WSTR, $params);
    }

    public function getReport($returnXml)
    {
        $params = array(
            'Action' => 'GetReport',
            'ReportId' => $this->reportId
        );
        if ($this->reportId) {
            return $this->api->_simpleCallWs(self::WSTR, $params, $returnXml);
        }

        return false;
    }

    public function ackReport()
    {
        return $this->api->_simpleCallWs(self::WSTR, array(
            'Action' => 'UpdateReportAcknowledgements',
            'Acknowledged' => 'true',
            'ReportIdList.Id.1' => (string)$this->reportId
        ));
    }

    public function refreshRequestInfo()
    {
        $configForMkp = array('last_time' => $this->lastTime, 'report_request_id' => 0);

        $saved = AmazonConfiguration::get($this->configKey);
        if (!$saved || !is_array($saved)) {
            $tobeSaved = array($this->amzLang => $configForMkp);
        } else {
            $saved[$this->amzLang] = $configForMkp;
            $tobeSaved = $saved;
        }

        return AmazonConfiguration::updateValue($this->configKey, $tobeSaved);
    }
}
