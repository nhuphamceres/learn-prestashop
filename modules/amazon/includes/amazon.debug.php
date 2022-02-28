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

class AmazonDebugDetails
{
    protected static $webservice = array();
    protected static $configuration = array();
    protected static $adminOrder = array();

    public function getAll()
    {
        return array_filter(array(
            'configuration' => count(self::$configuration) ? CommonTools::pre(self::$configuration, true) : '',
            'admin_order' => count(self::$adminOrder) ? CommonTools::pre(self::$adminOrder, true) : '',
            'webservice' => count(self::$webservice) ? CommonTools::pre(self::$webservice, true) : '',
        ));
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

    public function adminOrder()
    {
        foreach (func_get_args() as $arg) {
            self::$adminOrder[] = $this->concatBackTrace($arg);
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
        $backTraces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);    // Get 4 back trace
        $callerStack = array();
        foreach ($backTraces as $backTrace) {
            $fileSegment = explode(DIRECTORY_SEPARATOR, $backTrace['file']);
            $file = array_pop($fileSegment);
            $callerStack[] = sprintf('%s(#%d)', $file, $backTrace['line']);
        }
        // Remove the first & second caller, self reference
        array_shift($callerStack);
        array_shift($callerStack);

        return implode(' - ', $callerStack) . ': ';
    }
}
