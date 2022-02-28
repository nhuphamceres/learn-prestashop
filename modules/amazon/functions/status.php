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
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
*/

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../amazon.php');

require_once(dirname(__FILE__).'/../classes/amazon.order_info.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.carrier.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.batch.class.php');
require_once(dirname(__FILE__).'/../common/order.class.php');

/**
 * Class AmazonBulkMode
 * 
 * Possible query params:
 * - period: Extend the look back days to collect orders, default = DEFAULT_PERIOD_IN_DAYS
 */
class AmazonBulkMode extends Amazon
{
    const DEFAULT_PERIOD_IN_DAYS = 15;

    private static $logContent = '';

    protected $debug;
    protected $cr;
    
    protected $unknownCarriers = array();
    
    protected $period;

    public function __construct()
    {
        parent::__construct();

        AmazonContext::restore($this->context);

        if (Amazon::$debug_mode) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        $this->debug = Amazon::$debug_mode;
        $this->cr = Amazon::LF;

        $this->period = max(self::DEFAULT_PERIOD_IN_DAYS, (int)Tools::getValue('period', 1));
    }

    public function dispatch()
    {
        $this->bulkUpdate();

        require_once(dirname(__FILE__) . '/../classes/amazon.logger.class.php');
        $logger = new AmazonLogger(AmazonLogger::CHANNEL_ORDER_UPDATE_STATUS);
        $logger->debug(self::getLogContent());
    }

    public function bulkUpdate()
    {
        $timestart = time();
        $cr = Amazon::LF; // carriage return

        $ps_order_list = array();

        $tokens = Tools::getValue('cron_token');
        $amazon_lang = Tools::getValue('lang');
        $europe = (int)Tools::getValue('europe');
        $force = (int)Tools::getValue('force');

        if (!AmazonTools::checkToken($tokens)) {
            die('Wrong Token');
        }

        if (!$amazon_lang) {
            echo $this->l('No selected language, nothing to do...');
            die;
        }

        // Orders States
        //
        $sent_state = AmazonConfiguration::get('SENT_STATE');
        $actives = AmazonConfiguration::get('ACTIVE');

        // Regions
        //
        $marketPlaceRegion = AmazonConfiguration::get('REGION');
        $marketLang2Region = array_flip($marketPlaceRegion);

        $id_lang_list = '';

        // Making id_lang_list to select orders
        //
        if ($europe) {
            foreach (AmazonTools::languages() as $language) {
                if (!isset($actives[$language['id_lang']]) || !$actives[$language['id_lang']]) {
                    continue;
                }

                if (!AmazonTools::isUnifiedAccount($marketPlaceRegion[$language['id_lang']])) {
                    continue;
                }

                $id_lang_list .= $language['id_lang'].',';
            }
            $id_lang_list = rtrim($id_lang_list, ',');
        } else {
            if (!isset($marketLang2Region[$amazon_lang])) {
                die('No selected language, nothing to do...');
            }

            $id_lang_list = $marketLang2Region[$amazon_lang];
        }


        if (!$id_lang_list) {
            die('No selected language, nothing to do...');
        }

        $order_state = new OrderState($sent_state, $this->id_lang);

        if (!Validate::isLoadedObject($order_state)) {
            die(sprintf('%s(%d): Wrong id order state', basename(__FILE__), __LINE__));
        }

        $mkpRegionNames = array_map(function($idLang) use ($marketPlaceRegion) {
            return Tools::strtoupper($marketPlaceRegion[$idLang]);
        }, explode(',', $id_lang_list));
        $this->ed(
            sprintf('Updating order statuses for: Amazon %s', implode(', ', $mkpRegionNames)),
            sprintf('Current time zone: %s', date_default_timezone_get())
        );
        $this->ed(
            sprintf("- Period: %s days. ", $this->period),
            sprintf("- State: %s (%d). ", $order_state->name, $sent_state),
            sprintf("- Language: %s. ", $amazon_lang)
        );
        $this->pd(sprintf('Parameters: %s %s'.$cr, $sent_state, nl2br(print_r($id_lang_list, true))), true);
        $this->separate();

        $id_lang = Language::getIdByIso($amazon_lang);

        // Fetch Orders
        if (!($orders = AmazonOrder::getMarketplaceOrdersStatesByIdLang($id_lang_list, $sent_state, $this->period, $force, Amazon::$debug_mode))) {
            $this->ed(sprintf($this->l('No Orders - exiting normally')));
            $this->separate();
            return;
        }

        $this->ed('Order List:');

        foreach ($orders as $order) {
            $this->ed(sprintf('id_order: %d amazon order: %s id_lang: %d id_carrier: %d shipping_number: %s date: %s', $order['id_order'], $order['mp_order_id'], $order['id_lang'], $order['id_carrier'], $order['shipping_number'], $order['date_add']));
        }
        $this->separate();

        $this->ed('Preparing shipping list');

        $fulfillmentMessages = array();
        require_once dirname(__FILE__).'/../includes/amazon/amazon.message.fulfillment.php';

        foreach ($orders as $order) {
            $id_lang = $order['id_lang'];
            $psIdOrder = $order['id_order'];
            $amzIdOrder = $order['mp_order_id'];

            $amazonCarrier = AmazonCarrier::getAmazonCarrierByPsIdCarrier($order['id_carrier'], $id_lang);

            if (!Tools::strlen($amazonCarrier['carrier'])) {
                $this->addUnknownCarrier($id_lang, $order['id_carrier']);
                continue;
            }

            if (!$amazonCarrier['carrier']) {
                if (Amazon::$debug_mode) {
                    CommonTools::p(sprintf('%s:%d %s %d (%d)'.$cr, basename(__FILE__), __LINE__, $this->l('Skipping order - Empty carrier for order #'), $order['id_order'], $id_lang));
                }
                continue;
            }

            $shippingNumber = $this->checkShippingNumber($order['shipping_number'], $psIdOrder);
            $amzOrder = AmazonOrder::getByOrderId($psIdOrder);
            // Shipping service in order takes higher priority, then in carrier outgoing mapping
            $shippingMethod = $amzOrder['shipping_services'];
            if (!$shippingMethod) {
                $shippingMethod = $amazonCarrier['shipping_service'];
            }

            // Building array for Amazon API
            $ps_order_list[] = $psIdOrder;
            // todo: Maybe pass whole mapping is more elegant
            $fulfillmentMessage =
                new AmazonFulfillmentMessage($amzIdOrder, $psIdOrder, $amazonCarrier['carrier'], $shippingNumber, $shippingMethod, strtotime($order['date_add']));
            $fulfillmentMessages[$amzIdOrder] = $fulfillmentMessage;
            $this->ed($fulfillmentMessage->toString());
        }

        $this->separate();
        $this->reportUnknownCarriers();
        $this->separate();

        if (!count($fulfillmentMessages)) {
            $this->ed(sprintf($this->l('No Orders - exiting normally')));
            return;
        } else {
            $this->ed(sprintf($this->l('%s Orders'), count($fulfillmentMessages)));
        }

        // Init Amazon
        //
        $platform = AmazonTools::selectPlatform($id_lang, Amazon::$debug_mode);

        if (Amazon::$debug_mode) {
            CommonTools::p(print_r($platform['auth'], true).print_r($platform['params'], true).print_r($platform['platforms'], true));
        }

        $pass = true;

        if (!($amazonApi = new AmazonWebService($platform['auth'], $platform['params'], null, Amazon::$debug_mode))) {
            echo $this->l('Unable to login').$cr;
            $pass = false;
        }

        if ($pass) {
            echo "Sending Feed to Amazon:\n";

            // Submitting Orders
            //
//            pdt('Prepare to submit', $fulfillmentMessages);
            if (!($result = $amazonApi->confirmMultipleOrders($fulfillmentMessages))) {
                $this->ed($this->l('Unable to send data to Amazon'));
            }

            if (isset($result->SubmitFeedResult->FeedSubmissionInfo->FeedSubmissionId)) {
                foreach ($ps_order_list as $id_order) {
                    AmazonOrder::updateMarketplaceStatus($id_order, AmazonOrder::CHECKED);
                }
                $this->ed(sprintf('%s %s', $this->l('Data successfully submitted, FeedSubmissionId:'), $result->SubmitFeedResult->FeedSubmissionInfo->FeedSubmissionId));

                // Save Session
                $batches = new AmazonBatches('session_status');
                $batch = new AmazonBatch($timestart);
                $batch->id = uniqid();
                $batch->timestop = time();
                $batch->type = $this->l('Cron');
                $batch->region = $marketPlaceRegion[$id_lang];
                $batch->created = 0;
                $batch->updated = count($fulfillmentMessages);
                $batch->deleted = 0;
                $batches->add($batch);
                $batches->save();

                $batches = new AmazonBatches('batch_status');
                $batch = new AmazonBatch($timestart);
                $batch->id = (string)$result->SubmitFeedResult->FeedSubmissionInfo->FeedSubmissionId;
                $batch->timestop = time();
                $batch->type = 'Status';
                $batch->region = $marketPlaceRegion[$id_lang];
                $batch->created = 0;
                $batch->updated = count($fulfillmentMessages);
                $batch->deleted = 0;
                $batches->add($batch);
                $batches->save();
            } else {
                $this->ed('Amazon Returned', print_r($amazonApi->getLastRequestInfo(), true));
            }

            $this->separate();
        }
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        static $lang = null;

        if(!$lang)
            $lang = Amazon::availableLang(Language::getIsoById($this->id_lang));

        return (parent::l($string, basename(__FILE__, '.php'), $lang));
    }
    
    protected function addUnknownCarrier($idLang, $idCarrier)
    {
        if (!isset($this->unknownCarriers[$idLang])) {
            $this->unknownCarriers[$idLang] = array();
        }
        $this->unknownCarriers[$idLang][$idCarrier] = $idCarrier;
    }

    protected function reportUnknownCarriers()
    {
        if (count($this->unknownCarriers)) {
            $this->ed('Unknown carriers:');

            foreach ($this->unknownCarriers as $id_lang => $unknown_carrier) {
                foreach ($unknown_carrier as $id_carrier) {
                    $carrier = new Carrier($id_carrier);
                    $this->ed(sprintf(
                        '%s %s (%s) - %d',
                        $this->l('Carrier not found, please configure your carriers associations for:'),
                        isset($carrier->name) ? $carrier->name : $id_carrier,
                        Language::getIsoById($id_lang),
                        $id_carrier
                    ));
                }
            }
        }
    }

    /**
     * @param $queryShippingNumber
     * @param $psIdOrder
     * @return string|null
     */
    protected function checkShippingNumber($queryShippingNumber, $psIdOrder)
    {
        if (empty($queryShippingNumber)) {
            $psOrder = new Order($psIdOrder);
            $tracking = AmazonOrder::getShippingNumber($psOrder);
            $this->ed('Trying to resolve shipping number if not found. Order id: %d, shipping number: %s', $psIdOrder, $tracking);

            return $tracking;
        }

        return $queryShippingNumber;
    }

    /**
     * todo: Use ed() instead
     * Print debug message
     * @param $message
     * @param bool $debugModeOnly
     */
    protected function pd($message, $debugModeOnly = false)
    {
        if (!$debugModeOnly || $this->debug) {
            $debug = AmazonTools::pre(array($message), true) . $this->cr;
            $this->logContent($debug);
            echo $debug;
        }
    }

    private function ed()
    {
        $backTrace = debug_backtrace();
        $caller = array_shift($backTrace);
        $fileSegment = explode(DIRECTORY_SEPARATOR, $caller['file']);
        $file = array_pop($fileSegment);

        $debugs = array_map(function ($arg) use ($file, $caller) {
            return sprintf('%s(#%d): %s', $file, $caller['line'], $arg) . Amazon::LF;
        }, func_get_args());

        $debug = AmazonTools::pre($debugs, true);
        $this->logContent($debug);
        echo $debug;
    }

    protected function separate($debugModeOnly = false)
    {
        $this->pd(str_repeat('-', 160), $debugModeOnly);
    }

    private static function logContent($log)
    {
        self::$logContent .= $log . Amazon::LF;
    }

    private static function getLogContent()
    {
        $logContent = self::$logContent;
        self::$logContent = '';
        return $logContent;
    }
}

$amazonBulkMode = new AmazonBulkMode;
$amazonBulkMode->dispatch();
