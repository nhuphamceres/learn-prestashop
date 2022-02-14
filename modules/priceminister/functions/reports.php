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

$file = new SplFileInfo($_SERVER['SCRIPT_FILENAME']);

require_once dirname(dirname(dirname($file->getPath()))).'/config/config.inc.php';
require_once dirname(dirname(dirname($file->getPath()))).'/init.php';

require_once(dirname(__FILE__).'/../priceminister.php');
require_once(dirname(__FILE__).'/../classes/priceminister.tools.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.context.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.batch.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.webservices.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.products.class.php');

ini_set('max_execution_time', 900);

class PriceMinisterReports extends PriceMinister
{

    public static $errors = array();
    public static $warnings = array();
    public static $messages = array();
    public static $report = array();
    public static $id_warehouse = 0;
    public $export;
    public $timestart;
    public $translated_error_string;

    public function __construct()
    {
        $this->timestart = time();

        parent::__construct();

        PriceMinisterContext::restore($this->context);

        // Set the correct shop context in the global context
        // Usefull for function to get image or stock for exemple
        if ($this->context->shop && Validate::isLoadedObject($this->context->shop)) {
            Context::getContext()->shop = $this->context->shop;
        }

        parent::loadGeneralModuleConfig();

        $this->credentials = unserialize(Configuration::get(PriceMinister::CONFIG_PM_CREDENTIALS));

        if (Tools::getValue('debug')) {
            $this->debug = true;
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }
    }

    public static function JSON_Display_Exit()
    {
        $result = trim(ob_get_clean());

        if (!empty($result)) {
            PriceMinisterReports::$warnings[] = trim($result);
        }

        $json = Tools::jsonEncode(
            array(
                'count' => count(PriceMinisterReports::$report),
                'reports' => PriceMinisterReports::$report,
                'error' => (count(PriceMinisterReports::$errors) ? true : false),
                'errors' => PriceMinisterReports::$errors,
                'warning' => (count(PriceMinisterReports::$warnings) ? true : false),
                'warnings' => PriceMinisterReports::$warnings,
                'message' => count(PriceMinisterReports::$messages),
                'messages' => PriceMinisterReports::$messages
            )
        );

        if (($callback = Tools::getValue('callback'))) { // jquery
            echo (string)$callback.'('.$json.')';
        } else {
            echo "<pre>\n";
            echo PriceMinisterTools::jsonPrettyPrint($json);
            echo "<pre>\n";
        }
    }

    public function Dispatch()
    {
        ob_start();
        register_shutdown_function(array('PriceMinisterReports', 'JSON_Display_Exit'));

        //  Check Access Tokens
        //
        $pm_token = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);

        if ($pm_token != Tools::getValue('pm_token')) {
            self::$errors[] = $this->l('Wrong Token');
            die;
        }

        $this->export = $this->path.'export/';
        $this->export_url = $this->url.'export/';

        $cron = Tools::getValue('cron', 0);

        switch ($action = Tools::getValue('action')) {
            case 'list':
                $this->ReportsList();
                break;
            case 'report':
                $this->ReportsReport();
                break;
            default:
                self::$errors[] = 'Missing Parameter';
                die;
        }
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function ReportsList()
    {
        $batches_create = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_CREATE);
        $batches_create_list = $batches_create->load();

        $batches_update = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_UPDATE);
        $batches_update_list = $batches_update->load();

        if ((!is_array($batches_create_list) || !count($batches_create_list)) && (!is_array($batches_update_list) || !count($batches_update_list))) {
            self::$warnings[] = $this->l('No report available');
            die;
        }

        $i = 0;

        if (is_array($batches_create_list) && count($batches_create_list)) {
            foreach ($batches_create_list as $batch) {
                PriceMinisterReports::$report[$i] = $batch->format();
                PriceMinisterReports::$report[$i]['type'] = 'C';
                PriceMinisterReports::$report[$i]['records'] = $batch->created + $batch->updated + $batch->deleted;

                if (file_exists($this->export.$batch->file)) {
                    PriceMinisterReports::$report[$i]['link'] = $this->export_url.$batch->file;
                } else {
                    PriceMinisterReports::$report[$i]['link'] = null;
                }

                $i++;
            }
        }

        if (is_array($batches_update_list) && count($batches_update_list)) {
            foreach ($batches_update_list as $batch) {
                PriceMinisterReports::$report[$i] = $batch->format();
                PriceMinisterReports::$report[$i]['type'] = 'U';
                PriceMinisterReports::$report[$i]['records'] = $batch->created + $batch->updated + $batch->deleted;

                if (file_exists($this->export.$batch->file)) {
                    PriceMinisterReports::$report[$i]['link'] = $this->export_url.$batch->file;
                } else {
                    PriceMinisterReports::$report[$i]['link'] = null;
                }

                $i++;
            }
        }
    }

    public function ReportsReport()
    {
        $reportid = Tools::getValue('reportid');

        if (!(int)$reportid) {
            self::$warnings[] = $this->l('Report not available');
            die;
        }

        $p = new PriceMinisterApiProducts(PriceMinisterTools::Auth());

        $nexttoken = null;
        $summary = null;
        $details = null;

        while (1) {
            $params = array();
            $params['fileid'] = $reportid;
            $params['nexttoken'] = $nexttoken;

            if ($this->credentials['test']) {
                $xml = PriceMinisterTools::file_get_contents(dirname(__FILE__).'/demo_report.xml');
                if (!$xml) {
                    die($this->l('Inable to load test report.'));
                }
                $result = simplexml_load_string($xml);
            } else {
                $result = $p->genericImportReport($params);
            }

            if ($result instanceof SimpleXMLElement) {
                if (isset($result->error) && isset($result->error->code)) {
                    $message = sprintf('API Error: %s - %s', (string)$result->error->code, (string)$result->error->message);

                    if (isset($result->error->details->detail)) {
                        $message .= '- '.(string)$result->error->details->detail;
                    }

                    self::$errors[] = $message;
                    die;
                }
            } else {
                self::$errors[] = sprintf('API Error: Unexpected content - %s', nl2br(print_r($result, true)));
                die;
            }

            if (!$summary) {
                $summary .= sprintf('%-50s : %s<br>', $this->l('File'), $result->response->file->filename);
                $summary .= sprintf('%-50s : %s<br>', $this->l('Status'), $result->response->file->status);
                $summary .= sprintf('%-50s : %s<br>', $this->l('Upload Date'), $result->response->file->uploaddate);
                $summary .= sprintf('%-50s : %s<br>', $this->l('Process Date'), $result->response->file->processdate);
                $summary .= sprintf('%-50s : %s<br>', $this->l('Total Lines'), $result->response->file->totallines);
                $summary .= sprintf('%-50s : %s<br>', $this->l('Processed Lines'), $result->response->file->processedlines);
                $summary .= sprintf('%-50s : %s<br>', $this->l('Error Lines'), $result->response->file->errorlines);
                $summary .= sprintf('%-50s : %s<br>', $this->l('Success Rate'), $result->response->file->successrate);
            }
            if (((int)$result->response->file->processedlines || (int)$result->response->file->totallines) && count($result->response->product)) {
                foreach ($result->response->product as $product_log) {
                    if (isset($product_log->errors->error) && count($product_log->errors->error)) {
                        foreach ($product_log->errors->error as $error) {
                            $details .= sprintf('%-24s %s<br>', (string)$product_log->sku, (string)$this->getErrorText((string)$error->error_text));

                            if ($this->debug) {
                                var_dump($error);
                            }
                        }
                    } else {
                        // TODO: In operation : display only errors :)
                        //if ((int)$result->response->file->errorlines &&Tools::substr($product_log->status, 0, 5) == 'Trait' )
                        //	continue;

                        $details .= sprintf('%-24s %-12s %s<br>', $product_log->pid, $product_log->aid, $product_log->status);
                    }
                }
            } elseif ((int)$result->response->file->totallines && (int)$result->response->file->errorlines == (int)$result->response->file->totallines) {
                $details .= $this->l('Too much errors, file has not been processed');
            } elseif (!(int)$result->response->file->processedlines) {
                $details .= $this->l('Please wait while the file is being processed');
            }

            if ((!isset($result->response->nexttoken) || empty($result->response->nexttoken)) || ($nexttoken && $nexttoken == (string)$result->nexttoken)) {
                break;
            }

            $nexttoken = (string)$result->response->nexttoken;
        }
        self::$report['summary'] = $summary;
        self::$report['details'] = $details;
    }

    public function getErrorText($error_text)
    {
        $check_error_string = $this->getTranslatedErrorString();

        $key = md5(trim($error_text));

        if (array_key_exists($key, $check_error_string)) {
            return ($check_error_string[$key]);
        } else {
            return ($error_text);
        }
    }

    public function getTranslatedErrorString()
    {
        if (is_array($this->translated_error_string) && count($this->translated_error_string)) {
            return ($this->translated_error_string);
        }

        return (array(
            // L'alias est obligatoire pour les créations de produit.
            '081a2d4a27749802648a8d63830ffc9e' => $this->l('This product does not exist on RakutenFrance.')
        ));
    }
}

$ReportList = new PriceMinisterReports();
$ReportList->Dispatch();