<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Common-Services Co., Ltd.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
 * Support by mail:  support.cdiscount@common-services.com
 */

class CDiscountDebugDetails
{
    protected static $webservice = array();
    protected static $configuration = array();
    protected static $productExport = array();

    public function getAll()
    {
        return array(
            'configuration' => count(self::$configuration) ? CommonTools::pre(self::$configuration, true) : '',
            'product_export' => count(self::$productExport) ? CommonTools::pre(self::$productExport, true) : '',
            'webservice' => count(self::$webservice) ? CommonTools::pre(self::$webservice, true) : '',
        );
    }

    public function webservice()
    {
        foreach (func_get_args() as $arg) {
            self::$webservice[] = $this->concatBackTrace($arg);
        }
    }

    public function configuration()
    {
        foreach (func_get_args() as $arg) {
            self::$configuration[] = $this->concatBackTrace($arg);
        }
    }

    public function productExport()
    {
        foreach (func_get_args() as $arg) {
            self::$productExport[] = $this->concatBackTrace($arg);
        }
    }

    protected function concatBackTrace($output)
    {
        return $this->backTrace() . (is_string($output) ? $output : print_r($output, true)) . "\n";
    }

    /**
     * @return string
     */
    protected function backTrace()
    {
        $backTraces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 4);    // Get 4 back trace
        $callerStack = array();
        foreach ($backTraces as $backTrace) {
            $fileSegment = explode(DIRECTORY_SEPARATOR, $backTrace['file']);
            $file = array_pop($fileSegment);
            $callerStack[] = sprintf('%s(#%d)', $file, $backTrace['line']);
        }
        array_shift($callerStack);    // Remove the first caller, self reference

        return implode(' - ', $callerStack) . ': ';
    }
}
