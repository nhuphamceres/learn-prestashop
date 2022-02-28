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

require_once(dirname(__FILE__) . '/env.php');
require_once(dirname(__FILE__) . '/../amazon.php');
require_once(dirname(__FILE__) . '/../classes/amazon.webservice.class.php');
require_once(dirname(__FILE__) . '/../classes/amazon.tools.class.php');

class AmazonFunctionWrapper extends Amazon
{
    /** @var string Store all debug messages printed while running */
    public static $debugContent;

    protected $cr;
    protected $debug;

    /** @var null path to downloaded report file */
    protected $file_inventory = null;
    /** @var null Report file will be prepend with this string */
    protected $report_prefix = null;

    /** @var string Configuration key in DB to store last request report data (time, requestId) */
    protected $reportConfigKey;

    /**
     * @var int After this amount of seconds, report file need to be re-downloaded.
     * Report file is processed as long as downloaded, maybe this is not need.
     * Because if process it again, shipments are insert again in DB.
     */
    protected $expired_report_file_seconds = 3600;
    /** @var int After this amount of seconds (5h), report request is consider outdated and need to make another one */
    protected $expired_report_request_seconds = 18000;
    /** @var AmazonReportRequest */
    protected $amzReportRequest;

    protected $marketplaceId;
    protected $idLangList;

    /** @var string For debug message only. Children should overwrite this for easier debugging. To know which function create debug message */
    protected $function = 'function_wrapper';

    /** @var AmazonWebService */
    protected $api;

    /** @var array Amazon platform after init webservice */
    protected $amazonPlatform;

    protected $cronToken;
    protected $amazonLang;

    /**
     * Flags for testing report handler
     */
    protected $forceUseFile;
    protected $forceDlReport;
    /** @var array('active' => bool, 'options' => []) */
    protected $forceRqReport;
    protected $forceGetRqList;
    protected $testXRowsOnly;

    public function __construct($cronToken, $amazonLang)
    {
        parent::__construct();
        $this->cr = Amazon::LF;
        $this->debug = Amazon::$debug_mode;

        AmazonContext::restore($this->context);

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        $this->cronToken = $cronToken;
        $this->amazonLang = $amazonLang;
    }

    protected function preCheckRequest()
    {
        if (!AmazonTools::checkToken($this->cronToken)) {
            die('Wrong Token');
        }

        if (!$this->amazonLang) {
            echo $this->l('No selected language, nothing to do...');
            die;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function resolveNecessaryParameters()
    {
        // Use lang iso instead of our definition code. Eg 'gb' instead of 'uk'
        $amazonIsoLang = $this->amazonLang;
        $langId = Language::getIdByIso($amazonIsoLang);
        if (!$langId) {
            $this->pd('Wrong parameter lang');
            return false;
        }

        $marketPlaceIds = AmazonConfiguration::get('MARKETPLACE_ID');
        $this->marketplaceId = trim($marketPlaceIds[$langId]);

        return true;
    }

    protected function initWebservice()
    {
        $id_lang = Language::getIdByIso($this->amazonLang);
        $platform = AmazonTools::selectPlatform($id_lang, $this->debug);

        if ($this->debug) {
            CommonTools::p(print_r($platform['auth'], true) .
                print_r($platform['params'], true) .
                print_r($platform['platforms'], true));
        }

        $this->amazonPlatform = $platform;
        $this->api = new AmazonWebService($platform['auth'], $platform['params'], null, $this->debug);
    }

    /********************************************* Report API flow ****************************************************/

    /**
     * @return bool
     */
    protected function reportUseDownloadedFileOrDownloadNew()
    {
        // 1. If we can use downloaded report file. Is it a new file?
        if ($this->reportCanUseDownloadedFile()) {
            // 1.1 Process file.
            return true;
        } else {
            // 1.2 Need download new file
            // 2. Pull report or request Amazon to generate report. Last request report is long ago?
            if (!$this->reportGetReportRequestListRatherThanRequestReport()) {
                // 2.1 Request report.
                if ($this->reportRequestAPI($this->forceRqReport['active'] ? $this->forceRqReport['options'] : array())) {
                    $this->pd('Report in preparation, please start the script again in a while.');
                } else {
                    $this->pd('RequestReport failed!');
                }
            } else {
                // 2.2 Get report complex flow
                /**
                 * 2.2.1 Get Report Request List by request ID. Return ReportRequestId | GeneratedReportId
                 * To get a particular report, we requested earlier
                 */
                $generatedReportIdOrReportId = $this->reportGetRequestListAPI();

                // 2.2.2 Get Report. Use GeneratedReportId | ReportId
                if (!$generatedReportIdOrReportId) {
                    $this->pd('A report has been already requested and there is not any available report yet');
                } else {
                    $this->amzReportRequest->reportId = $generatedReportIdOrReportId;
                    $getReportResult = $this->reportGetAPI();
                    if ($getReportResult) {
                        // Additional step: Acknowledge report
                        return $this->reportAckReport($generatedReportIdOrReportId)
                            && $this->amzReportRequest->refreshRequestInfo();
                    } else {
                        $this->pd('GetReport failed!');
                    }
                }
            }
        }

        return false;
    }

    protected function reportCanUseDownloadedFile()
    {
        // For testing only
        if ($this->forceUseFile) {
            return true;
        }
        if ($this->forceDlReport) {
            return false;
        }

        $downloadedFile = $this->file_inventory;
        $this->pdd('Looking report file at: ' . $downloadedFile, __LINE__, true);

        // Check downloaded file
        if ($downloadedFile && file_exists($downloadedFile)) {
            $fileTime = filemtime($downloadedFile);
            return $fileTime > (time() - $this->expired_report_file_seconds);
        }

        return false;
    }

    /**
     * rename downloaded file
     *
     * @return bool
     */
    protected function renameDownloadedFile()
    {
        $fileName = $this->file_inventory;
        if ($fileName && file_exists($fileName)) {
            // -4 because the file's extension is .raw
            $newName = substr_replace($fileName, '_used', -4, 0);
            $this->pdd('Renaming downloaded report file from "' . $fileName . '" to "' . $newName . '"', __LINE__, true);

            return rename($fileName, $newName);
        }

        return false;
    }
    
    protected function reportGetReportRequestListRatherThanRequestReport()
    {
        if ($this->forceRqReport['active']) {
            return false;
        }
        if ($this->forceGetRqList) {
            return true;
        }

        return $this->amzReportRequest->shouldRequestListOrRequestReport();
    }

    /********************************************* API requests *******************************************************/

    /**
     * @param array $optionalParams
     * @return bool
     */
    protected function reportRequestAPI($optionalParams = array())
    {
        $this->pdE('RequestReport API - Begin');

        // Truncate empty params
        $optionalParams = array_filter($optionalParams);

        // Format each optional param
        array_walk($optionalParams, function (&$paramValue, $paramKey) {
            switch ($paramKey) {
                // Assume it is string
                case 'ReportOptions':
//                    if (is_array($paramValue)) {
//                        $append = array();
//                        foreach ($paramValue as $key => $value) {
//                            $append[] = "$key=$value";
//                        }
//                        $paramValue = urlencode($paramValue);
//                    }
                    break;
                case 'StartDate':
                case 'EndDate':
                    $paramValue = gmdate('c', strtotime($paramValue));
                    break;
                default:
                    break;
            }
        });

        $requestReportXml = $this->amzReportRequest->reportRequestAPI($optionalParams, $this->marketplaceId);

        if (!$requestReportXml) {
            $this->pdE('RequestReport API failed!', $this->api->getResponseError());
            return false;
        }

        $this->pd('RequestReport XML result:', true);
        $this->debugXML($requestReportXml, true);

        if (!isset($requestReportXml->RequestReportResult->ReportRequestInfo->ReportProcessingStatus) ||
            !isset($requestReportXml->RequestReportResult->ReportRequestInfo->ReportRequestId)) {
            $this->pdd('RequestReport API failed! Strange XML format!', __LINE__);
            return false;
        }

        // Save last request
        if ($requestReportXml->RequestReportResult->ReportRequestInfo->ReportProcessingStatus == '_SUBMITTED_') {
            $requestId = (string)$requestReportXml->RequestReportResult->ReportRequestInfo->ReportRequestId;
            $this->amzReportRequest->saveReportRequestId($requestId);
            return true;
        } else {
            $this->pdd('Cannot save request data, strange XML format!', __LINE__);
            return false;
        }
    }

    protected function reportGetRequestListAPI()
    {
        $this->pd('GetReportRequestList API', true);

        $requestListXml = $this->amzReportRequest->getRequestListAPIByRequestId();
        if (!$requestListXml instanceof SimpleXMLElement or isset($requestListXml->Error)) {
            $this->pdd('GetReportRequestList API failed!', __LINE__);
            return false;
        }

        $this->pd('RequestReport XML result:', true);
        $this->debugXML($requestListXml, true);

        $requestListXml->registerXPathNamespace('xmlns', 'http://mws.amazonaws.com/doc/2009-01-01/');
        $xpathResult = $requestListXml->xpath('//xmlns:GetReportRequestListResponse/xmlns:GetReportRequestListResult/xmlns:ReportRequestInfo');
        $this->pdd('GetReportRequestList Xpath result: ' . print_r($xpathResult, true), __LINE__, true);

        if (is_array($xpathResult) && !count($xpathResult)) {
            return false;
        } else {
            // The report is available, take the first one :
            $reportData = reset($xpathResult);

            if (!($reportData instanceof SimpleXMLElement)) {
                return false;
            } else {
                $this->pd('GetReportRequestList - Selected report:', true);
                $this->debugXML($reportData, true);

                if (isset($reportData->GeneratedReportId) && $reportData->GeneratedReportId) {
                    return (string)$reportData->GeneratedReportId;
                }
                if (isset($reportData->ReportId) && $reportData->ReportId) {
                    return (string)$reportData->ReportId;
                }
            }
        }

        return false;
    }

    protected function reportGetAPI()
    {
        $this->pd('GetReport API', true);
        
        // By default, VIDR only return shipments within last 90 days for which invoices are due and must be upload (ReportOption=PendingInvoices)
        // Simply loop through the result to process all pending shipments
        $reportFlat = $this->amzReportRequest->getReport(false);

        $this->pdd('GetReport result:', __LINE__, true);
        $this->pd(print_r($reportFlat, true), true);

        if (empty($reportFlat)) {
            $this->pdd('GetReport - Empty report', __LINE__);
            return false;
        }

        $fileInventory = $this->file_inventory;
        if ($fileInventory) {
            $saveFile = file_put_contents($this->file_inventory, $reportFlat);
            if (false === $saveFile) {
                $this->pdd(sprintf('GetReport - Unable to write to output file: %s', $fileInventory), __LINE__);
                return false;
            }
        }

        return true;
    }

    /**
     * We are not using scheduling order reports. Maybe this step is not need
     * @param $reportId
     * @return bool
     */
    protected function reportAckReport($reportId)
    {
        $this->pd('UpdateReportAcknowledgements API', true);

        $ackResultXml = $this->amzReportRequest->ackReport();
        if ($ackResultXml instanceof SimpleXMLElement && (int)$ackResultXml->UpdateReportAcknowledgementsResult->Count) {
            $this->pd(sprintf('Successfully acknowledge report: %s', $reportId));
            $this->debugXML($ackResultXml, true);
            return true;
        } else {
            $this->pdd(sprintf('Acknowledge report failed! - %s', $reportId), __LINE__);
            $this->pdd(print_r($ackResultXml, true), __LINE__, true);
            return false;
        }
    }

    /******************************************* Helper functions *****************************************************/

    /**
     * @param $line
     * @param $separate
     * @return array
     */
    protected function explodeReportLine($line, $separate)
    {
        $lineExplode = explode($separate, $line);

        // Some column is enclose inside "", trim them
        return array_map(function ($element) {
            return trim($element, " \t\n\r\0\x0B\"");
        }, $lineExplode);
    }

    /**
     * @param bool $debugModeOnly
     */
    protected function separate($debugModeOnly = false)
    {
        if (!$debugModeOnly || $this->debug) {
            self::$debugContent .= str_repeat('-', 160) . $this->cr;
        }
    }

    /**
     * Print debug message
     * @param $message
     * @param bool $debugModeOnly
     */
    protected function pd($message, $debugModeOnly = false)
    {
        if (!$debugModeOnly || $this->debug) {
            self::$debugContent .= AmazonTools::pre(array($message), true) . $this->cr;
        }
    }

    /**
     * Print debug message with file & line detail
     * @param $message
     * @param $line
     * @param bool $debugModeOnly
     */
    public function pdd($message, $line, $debugModeOnly = false)
    {
        if (!$debugModeOnly || $this->debug) {
            self::$debugContent .= AmazonTools::pre(array(
                    sprintf('%s(#%d): ', $this->function, $line),
                    $message
                ), true)
                . $this->cr;
        }
    }

    public function pddE()
    {
        if ($this->debug) {
            $this->_debug(func_get_args());
        }
    }

    public function pdE()
    {
        $this->_debug(func_get_args());
    }

    private function _debug($args)
    {
        $backTrace = debug_backtrace();
        $callerStack = array();
        for ($i = 1; $i <= 3; $i++) {   // Get 3 back trace
            $caller = array_shift($backTrace);
            $fileSegment = explode(DIRECTORY_SEPARATOR, $caller['file']);
            $file = array_pop($fileSegment);
            $callerStack[] = sprintf('%s(#%d)', $file, $caller['line']);
        }

        $callerStackStr = implode(' - ', $callerStack) . ': ';
        foreach ($args as $arg) {
            self::$debugContent .= AmazonTools::pre(array($callerStackStr, $arg), true) . $this->cr;
        }
    }

    public function concatDebug($addition)
    {
        self::$debugContent .= $addition;
    }

    public function getDebugContent()
    {
        $content = self::$debugContent;
        self::$debugContent = '';
        return $content;
    }

    public function debugXML($xml, $debugModeOnly = false)
    {
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $saveXML = htmlspecialchars($dom->saveXML());
        $this->pd($saveXML, $debugModeOnly);
    }
}
