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
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2017 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
  * Support by mail:  support.cdiscount@common-services.com
 */

if (isset($_SERVER['DropBox']) && $_SERVER['DropBox']) {
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config/config.inc.php'));
} else {
    @require_once(dirname(__FILE__).'/../../../config/config.inc.php');
}

require_once(dirname(__FILE__).'/../cdiscount.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.tools.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.categories.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');

class CDiscountCategoriesLoad extends CDiscount
{
    const LF = "\n";

    private $force;

    public function __construct()
    {
        $this->debug = (int)Configuration::get(parent::KEY.'_DEBUG') ? true : false;

        parent::__construct();
        CDiscountContext::restore($this->context);
        
        $this->force = Tools::getValue('force');
    }

    public function dispatch()
    {
        $this->debug = Tools::getValue('debug', false);

        switch (Tools::getValue('action')) {
            case 'universes':
                ob_start(); // Grab the print content
                $universe = $this->universes();
                $output = ob_get_clean();
                die(json_encode(array('universe' => $universe, 'output' => $output)));
            case 'categories':
                $this->categories();
                break;
        }
    }

    /**
     * @return array root category list (universe)
     */
    public function universes()
    {
        CDiscountCategories::setPath();

        if (!($result = $this->loadAllCategoryTree())) {
            if (Cdiscount::$debug_mode) {
                printf('%s/%s: loadAllCategoryTree() failed'.self::LF, basename(__FILE__), __LINE__);
            }

            return array();
        }
        $rootCategories = $result->xpath('/*/CategoryTree/ChildrenCategoryList/*/Name');

        if (is_array($rootCategories) && count($rootCategories)) {
            if (file_put_contents(CDiscountCategories::$universes_file, null) === false) {
                // clear file

                if (Cdiscount::$debug_mode) {
                    printf('%s/%s: Unable to write to output file'.self::LF, basename(__FILE__), __LINE__);
                }

                return array();
            }

            foreach ($rootCategories as $category) {
                file_put_contents(CDiscountCategories::$universes_file, $category.self::LF, FILE_APPEND);
            }
        } else {
            if (file_exists(CDiscountCategories::$universes_file)) {
                unlink(CDiscountCategories::$universes_file);
            }
        }

        $this->checkAllCategory();

        return $this->getUniverseOptions();
    }

    public function loadAllCategoryTree()
    {
        $production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);
        $marketplace = new CDiscountCategories('AllData', 'pa$$word', $production, $this->debug);
        $marketplace->token = CDiscountTools::auth('AllData', 'pa$$word', true);

        $filecheck = CDiscountCategories::getCategoriesTimestamp();

        if ($this->debug) {
            printf('%s/%s: Timestamp: %d - %s'.self::LF, basename(__FILE__), __LINE__, $filecheck, CommonTools::displayDate(date('Y-m-d H:i:s', $filecheck), $this->id_lang, true));
        }

        if (!$filecheck || $filecheck < (time() - 86400 * 15) || (bool)Tools::getValue('force')) {
            if ($this->ps16x) {
                $class_error = 'alert alert-danger';
            } else {
                $class_error = parent::MODULE.'-error';
            }

            if (!$marketplace->token) {
                die('<div class="'.$class_error.'">'.$this->l('Authentication process has failed').' '.parent::NAME.'</div>');
            }

            if ($this->debug) {
                printf('%s/%s: Load through Web/Service'.self::LF, basename(__FILE__), __LINE__);
            }

            $result = $marketplace->GetAllAllowedCategoryTree();

            if (isset($result->ErrorList->Error)) {
                if ($this->debug) {
                    printf('%s/%s: GetAllAllowedCategoryTree() failed'.self::LF, basename(__FILE__), __LINE__);
                }

                return (false);
            }
            if ($result instanceof SimpleXMLElement && isset($result->CategoryTree)) {
                if (!$result->saveXML('compress.zlib://'.CDiscountCategories::$category_file)) {
                    if ($this->debug) {
                        printf('%s/%s: GetAllAllowedCategoryTree() failed'.self::LF, basename(__FILE__), __LINE__);
                    }

                    return (false);
                }
            }
        } else {
            if ($this->debug) {
                printf('%s/%s: Load from File: %s'.self::LF, basename(__FILE__), __LINE__, CDiscountCategories::$category_file);
            }

            if (!file_exists(CDiscountCategories::$category_file)) {
                if ($this->debug) {
                    printf('%s/%s: Missing file: %s'.self::LF, basename(__FILE__), __LINE__, CDiscountCategories::$category_file);
                }

                return (false);
            }

            if (!($file_contents = CDiscountTools::file_get_contents('compress.zlib://'.CDiscountCategories::$category_file))) {
                if ($this->debug) {
                    printf('%s/%s: Unable to read file: %s'.self::LF, basename(__FILE__), __LINE__, CDiscountCategories::$category_file);
                }

                return (false);
            }

            if (!($result = simplexml_load_string($file_contents))) {
                if ($this->debug) {
                    printf('%s/%s: GetAllAllowedCategoryTree() failed'.self::LF, basename(__FILE__), __LINE__);
                }

                return (false);
            }
        }

        return ($result);
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    protected function checkAllCategory()
    {
        return $this->_checkFileInfo(CDiscountCategories::getCategoriesTimestamp());
    }

    public function categories()
    {
        CDiscountCategories::setPath();

        if (!($result = $this->loadAllowedCategoryTree())) {
            if ($this->debug) {
                printf('%s/%s: loadAllCategoryTree() failed'.self::LF, basename(__FILE__), __LINE__);
            }

            return (false);
        }
        if (!$result instanceof SimpleXMLElement) {
            unlink(CDiscountCategories::$allowed_category_file);
        } else {
            $Categories = $result->xpath('//ChildrenCategoryList/CategoryTree/Code');

            if (!is_array($Categories) || !count($Categories)) {
                unlink(CDiscountCategories::$allowed_category_file);
            }
        }

        $this->checkAllowedCategory();
    }

    /**
     * @return false|mixed|SimpleXMLElement|string|null
     */
    protected function loadAllowedCategoryTree()
    {
        $force_load = false;
        $username = Configuration::get(parent::KEY.'_USERNAME');
        $password = Configuration::get(parent::KEY.'_PASSWORD');
        $production = !(bool)Configuration::get(parent::KEY . '_PREPRODUCTION');

        $marketplace = new CDiscountCategories($username, $password, $production, Cdiscount::$debug_mode);
        $marketplace->token = CDiscountTools::auth();

        $filecheck = CDiscountCategories::getAllowedCategoriesTimestamp();

        if (Cdiscount::$debug_mode) {
            printf('%s/%s: Timestamp: %d - %s'.self::LF, basename(__FILE__), __LINE__, $filecheck, CommonTools::displayDate(date('Y-m-d H:i:s', $filecheck), $this->id_lang));
        }

        if (!$filecheck || $filecheck < (time() - 86400 * 15) || (bool)Tools::getValue('force')) {
            if ($this->ps16x) {
                $class_error = 'alert alert-danger';
            } else {
                $class_error = parent::MODULE.'-error';
            }

            if (!$marketplace->token) {
                die('<div class="'.$class_error.'">'.$this->l('Authentication process has failed').' '.parent::NAME.'</div>');
            }

            if (Cdiscount::$debug_mode) {
                printf('%s/%s: Load through Web/Service'.self::LF, basename(__FILE__), __LINE__);
            }

            $result = $marketplace->GetAllowedCategoryTree();

            if ($result instanceof SimpleXMLElement && isset($result->CategoryTree)) {
                // Fix trailing whitespace issue
                $output = str_replace(' </Name>', '</Name>', $result->asXML());

                if (!file_put_contents('compress.zlib://'.CDiscountCategories::$allowed_category_file, $output)) {
                    if (Cdiscount::$debug_mode) {
                        printf('%s/%s: LoadAllowedCategoryTree() failed'.self::LF, basename(__FILE__), __LINE__);
                    }

                    $force_load = true;
                }
            } else {
                $force_load = true;
            }
        } else {
            $force_load = true;
        }

        if ($force_load) {
            if (Cdiscount::$debug_mode) {
                printf('%s/%s: Load from File: %s'.self::LF, basename(__FILE__), __LINE__, CDiscountCategories::$allowed_category_file);
            }

            // todo: May produce warning, catch it
            if (!($result = simplexml_load_file('compress.zlib://'.CDiscountCategories::$allowed_category_file))) {
                if (Cdiscount::$debug_mode) {
                    printf('%s/%s: LoadAlowedlCategoryTree() failed'.self::LF, basename(__FILE__), __LINE__);
                }

                return (false);
            }
        }

        return ($result);
    }

    protected function checkAllowedCategory()
    {
        $this->_checkFileInfo(CDiscountCategories::getAllowedCategoriesTimestamp());
    }

    private function _checkFileInfo($fileInfo)
    {
        if (!$fileInfo) {
            $printInfo = '<div class="' . parent::MODULE . '-error">' .
                $this->l('The categories file is wrong or missing, please contact us') .
                '</div>';
        } else {
            $date = $this->force ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $fileInfo);
            $class = $this->ps16x ? 'alert alert-info' : 'cd-info-level-info';

            $printInfo = '<div class="' . $class . '" style="display:block">' .
                $this->l('Categories File, last updated on') . ' : ' . CommonTools::displayDate($date, $this->id_lang, true) .
                '</div>';
        }

        // todo: return instead of print
        echo $printInfo;

        return $printInfo;
    }
}

$mpCategoriesLoad = new CDiscountCategoriesLoad();
$mpCategoriesLoad->dispatch();
