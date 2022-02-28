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

require_once(dirname(__FILE__) . '/../wrapper.php');
require_once(dirname(__FILE__) . '/vidr_get_shipment.php');
require_once(dirname(__FILE__) . '/vidr_update_address.php');
require_once(dirname(__FILE__) . '/vidr_upload_vat_invoice.php');
require_once(dirname(__FILE__) . '/../../classes/amazon.logger.class.php');

/**
 * Class AmazonFunctionVIDRCronScript
 * VCS all cron job entries
 * Possible query params:
 * - General
 *  + mainAction: get | upload | update. Action to execution regardless run_order
 * - Upload VAT invoice
 *  + demo: not upload to Amazon, mimic the response only.
 *  + limitUpload: number of upload requests (pass all check and do a real upload invoice)
 *  + mpOrderId: Run for particular order
 * - Get shipment
 *  + forceUseFile: Use downloaded report file instead of request new file
 *  + forceDlReport: Request new report file instead of downloaded file
 *  + forceRqReport: Force requestReport API
 *   . rqrStart:    RequestReport StartDate
 *   . rqrEnd:      RequestReport EndDate
 *   . rqrOptions:  RequestReport ReportOptions (urlencode) 
 *  + forceGetRqList: Force getReportRequestList API
 *  + testXRowsOnly: Fetch x number of shipment only
 * - Update buyer VAT number / billing address for orders
 *  + mpOrderId: Run for particular order
 */
class AmazonFunctionVIDRCronScript extends AmazonFunctionWrapper
{
    protected $function = 'vidr_script';

    /** @var AmazonLogger */
    public $log;

    /**
     * This setting wil decide which action to be executed in this cron attempt.
     */
    const VIDR_CRON_RUN_ORDER = 'VIDR_CRON_RUN_ORDER';
    const VIDR_CRON_RUN_ORDER_ALL_MKP = 'VIDR_CRON_RUN_ORDER_ALL_MARKETPLACES';
    const VIDR_CRON_RUN_ORDER_ALL_MKP_4_6_54 = 'VIDR_CRON_RUN_ORDER_ALL_MARKETPLACES_4_6_54';

    const RUN_ACTION_GET_SHIPMENT = 'GET_SHIPMENT';
    const RUN_ACTION_UPLOAD_VAT_INVOICE = 'UPLOAD_VAT_INVOICE';
    const RUN_ACTION_UPDATE_VAT_NUMBER = 'UPDATE_VAT_NUMBER';

    public function __construct()
    {
        $cronToken = Tools::getValue('cron_token');
        $amazonLang = Tools::getValue('lang');
        parent::__construct($cronToken, $amazonLang);
    }

    public function runVIDRCronScript()
    {
        $cronToken = $this->cronToken;
        $amazonLang = $this->amazonLang;

        $action = $this->whatActionForThisTime();
        $this->pd(sprintf('VIDR action: %s', $action));
        $this->pd(sprintf('Language: %s', $amazonLang));
        $this->separate();

        switch ($action) {
            case self::RUN_ACTION_GET_SHIPMENT:
                $this->log = new AmazonLogger(array(AmazonLogger::CHANNEL_VCS, AmazonLogger::SUB_VCS_GET));

                $forceUseFile = Tools::getValue('forceUseFile');
                $forceDlReport = Tools::getValue('forceDlReport');
                $forceRqReport = Tools::getValue('forceRqReport');
                $forceGetRqList = Tools::getValue('forceGetRqList');
                $testXRowsOnly = Tools::getValue('testXRowsOnly');
                // Complex params
                $forceRqReportOption = array(
                    'active' => (bool)$forceRqReport,
                    'options' => array(),
                );
                if ($forceRqReport) {
                    $forceRqReportOption['options'] = array(
                        'StartDate' => Tools::getValue('rqrStart'),
                        'EndDate' => Tools::getValue('rqrEnd'),
                        'ReportOptions' => Tools::getValue('rqrOptions')
                    );
                }

                $getShipment = new AmazonFunctionVIDRGetShipment($cronToken, $amazonLang,
                    $forceUseFile, $forceDlReport, $forceRqReportOption, $forceGetRqList, $testXRowsOnly);
                $getShipment->runGetVIDRShipments();
                break;
            case self::RUN_ACTION_UPLOAD_VAT_INVOICE:
                $this->log = new AmazonLogger(array(AmazonLogger::CHANNEL_VCS, AmazonLogger::SUB_VCS_UPLOAD));

                $mpOrderId = Tools::getValue('mpOrderId');
                $isDemo = Tools::getValue('demo', false);
                $limitUpload = Tools::getValue('limitUpload', false);
                if (false !== $limitUpload) {
                    $sendInvoice = new AmazonFunctionVIDRUploadVATInvoice($cronToken, $amazonLang, $mpOrderId, $isDemo,
                        true, (int)$limitUpload);
                } else {
                    $sendInvoice = new AmazonFunctionVIDRUploadVATInvoice($cronToken, $amazonLang, $mpOrderId, $isDemo);
                }
                $sendInvoice->bulkUpload();
                break;
            case self::RUN_ACTION_UPDATE_VAT_NUMBER:
                $mpOrderId = Tools::getValue('mpOrderId');
                $updateVATNumber = new AmazonFunctionVIDRUpdateAddress($cronToken, $amazonLang, $mpOrderId);
                $updateVATNumber->updateCustomerAddress();
                break;
            default:
                $this->pd('Unknown action');
                break;
        }
    }

    protected function whatActionForThisTime()
    {
        $getShipment = self::RUN_ACTION_GET_SHIPMENT;
        $uploadInvoice = self::RUN_ACTION_UPLOAD_VAT_INVOICE;
        $updateVatNumber = self::RUN_ACTION_UPDATE_VAT_NUMBER;

        // Override action from parameter
        $mainAction = Tools::getValue('mainAction', false);
        if ($mainAction) {
            switch ($mainAction) {
                case 'get':
                    return $getShipment;
                case 'upload':
                    return $uploadInvoice;
                case 'update':
                    return $updateVatNumber;
                default:
                    $this->pd('Override main action not valid, use Run Order instead');
                    break;
            }
        }

        // Use run order
        $amazonLang = $this->amazonLang;
        $settingKey = self::VIDR_CRON_RUN_ORDER_ALL_MKP_4_6_54;
        $runOrder = AmazonConfiguration::get($settingKey);

        if (!$runOrder || !is_array($runOrder)) {
            // Run order not found, this is the first time, initialize it
            $thisTimeAction = $getShipment; // Setup get_shipment for next time
            $newRunOrder = array(
                // Double upload action because it's heaviest process
                $amazonLang => array($updateVatNumber, $uploadInvoice, $uploadInvoice, $getShipment)
            );

            // Delete legacy setting
            AmazonConfiguration::deleteKey(self::VIDR_CRON_RUN_ORDER);
            AmazonConfiguration::deleteKey(self::VIDR_CRON_RUN_ORDER_ALL_MKP);
        } elseif (!isset($runOrder[$amazonLang]) || !$runOrder[$amazonLang] || !is_array($runOrder[$amazonLang]) || !count($runOrder[$amazonLang])) {
            // Run order of this marketplace not exist, initialize it
            $thisTimeAction = $getShipment;
            $runOrder[$amazonLang] = array($updateVatNumber, $uploadInvoice, $uploadInvoice, $getShipment);
            $newRunOrder = $runOrder;
        } else {
            $thisTimeAction = array_shift($runOrder[$amazonLang]);
            $runOrder[$amazonLang][] = $thisTimeAction;  // Queue this time action to tail
            $newRunOrder = $runOrder;
        }

        AmazonConfiguration::updateValue($settingKey, $newRunOrder);

        return $thisTimeAction;
    }
}

$amazonVIDRCron = new AmazonFunctionVIDRCronScript();
$amazonVIDRCron->runVIDRCronScript();
$debugContent = $amazonVIDRCron->getDebugContent();
echo $debugContent;
if ($amazonVIDRCron->log) {
    $amazonVIDRCron->log->debug($debugContent);
}
