<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
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
 * ...........................................................................
 *
 * @author    Alexandre D. & Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  contact@common-services.com
 */

require_once(dirname(__FILE__).'/../../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../../classes/fnac.context.class.php');

class AdminFNACProductsControllerExt extends FNAC
{
    public $id_lang;
    private $module = 'fnac';

    protected $url;
    protected $path;
    protected $images;
    protected $debug;

    public function __construct($id_lang)
    {
        $this->debug = Configuration::get('FNAC_DEBUG') ? true : false;

        parent::__construct();
    }

    public function content($smarty)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $smarty->assign('context_key', FNAC_Context::getKey($this->context->shop));
        } else {
            $smarty->assign('context_key', '0');
        }

        $smarty->assign('path', $this->url);
        $smarty->assign('images', $this->images);

        $smarty->assign('products_js', $this->url.'/js/products.js');
        $smarty->assign('image_loading', $this->url.'/img/loading.gif');

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';
        $smarty->assign('selected_tab', $selected_tab);

        $smarty->assign('tab_selected_update', $selected_tab == 'update' ? 'selected' : '');
        $smarty->assign('tab_selected_masscsv', $selected_tab == 'create' ? 'selected' : '');
        $smarty->assign('tab_selected_masscsv', $selected_tab == 'csv' ? 'selected' : '');
        $smarty->assign('tab_selected_debug', $selected_tab == 'debug' ? 'selected' : '');

        if ($this->debug) {
            $smarty->assign('debug', $this->debug);
        }

        if (method_exists($smarty, 'setTemplateDir')) {
            $currentTemplates = $smarty->getTemplateDir();
            $additionnalTemplates = array($this->path.'views/templates/admin/catalog/');
            $smarty->setTemplateDir(is_array($currentTemplates) ? array_merge($currentTemplates, $additionnalTemplates) : $additionnalTemplates);
        } else {
            $smarty->template_dir = $this->path.'views/templates/admin/catalog/';
        }

        $smarty->assign('fnac_update', $this->_update());
        $smarty->assign('fnac_masscsv', $this->_masscsv());
        $smarty->assign('fnac_token', Configuration::get('FNAC_INSTANT_TOKEN', null, 0, 0));

        if ($this->debug) {
            $smarty->assign('fnac_debug', $this->_debug());
        }

        $this->context->controller->addJS(array(
            $this->_path.'views/js/back.js',
            '//cdnjs.cloudflare.com/ajax/libs/riot/3.11.1/riot+compiler.min.js'
        ));
        
        return $this->display($this->path, 'views/templates/admin/catalog/AdminCatalogFNAC.tpl').
            $this->display($this->path, 'views/templates/admin/prestui/ps-tags.tpl');
    }

    private function _update()
    {
        $view_params = array();
        $name = 'fnac';
        $module = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$name.'/';

        $images_url = $module.'views/img/';
        $loader = $images_url.'loading.gif';
        $export = $module.'functions/products.php';
        $initialDate = FNAC_Product::oldest();
        $currentDate = date('Y-m-d');
        //$file = date('Y-m-d') . '_' . $name . '.csv' ;

        if (!$dateCSV = Configuration::get('FNAC_LAST_IMPORTED')) {
            $errorImport = '<div class="'.(version_compare(_PS_VERSION_, '1.6', '>=') ? 'alert alert-danger' : 'alert error').'">'.$this->l('You must have export at least one time the CSV File !').'</div>';
        } else {
            $errorImport = '';
        }

        if (!$dateWS = Configuration::get('FNAC_LAST_UPDATED') && $dateCSV) {
            Configuration::updateValue('FNAC_LAST_UPDATED', $dateCSV);
            $dateWS = $dateCSV;
        } else {
            $dateWS = Configuration::get('FNAC_LAST_UPDATED');
        }

        $default_categories = unserialize(FNAC_Tools::decode(Configuration::get('FNAC_CATEGORIES')));

        if ($default_categories) {
            $categories = Category::getCategories($this->id_lang, false);
        } else {
            $categories = false;
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $shop = $this->context->shop;

            if ($categories && count($categories) > 1) {
                $first = reset($categories[1]);
            } else {
                $first = null;
            }
            $default_category = $shop->id_category;
        } elseif ($categories && is_array($categories)) {
            foreach ($categories as $first1 => $categories_array) {
                foreach ($categories_array as $first2 => $categories_array2) {
                    $first = $categories[$first1][$first2];
                    break;
                }
                break;
            }
            $default_category = 1;
        }

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';
        $view_params['tab_selected_update'] = $selected_tab == 'update' ? 'selected' : '';
        $view_params['images_url'] = $images_url;
        $view_params['export'] = $export;
        $view_params['loader'] = $loader;
        $view_params['currentDate'] = $currentDate;
        $view_params['request_uri'] = $_SERVER['REQUEST_URI'];
        $view_params['categories'] = array();

        if (is_array($default_categories) && count($default_categories)) {
            foreach ($default_categories as $id_category) {
                if (!(int)$id_category) {
                    continue;
                }

                $cat_array = array();
                $cat_array['id_category'] = $id_category;
                $cat_array['desc_category'] = FNAC_Tools::cPath($id_category, $this->id_lang);
                $view_params['categories'][] = $cat_array;
            }
        }

        $view_params['dateCSV'] = $dateCSV;
        $view_params['initialDate'] = $dateWS ? preg_replace('/ .*/', '', $dateWS) : $initialDate;
        $view_params['currentDate'] = date('Y-m-d');
        $view_params['dateWS'] = $dateWS;
        $view_params['errorImport'] = $errorImport;

        return $view_params;
    }

    private function _masscsv()
    {
        $view_params = array();

        $images_url = $this->url.'views/img/';
        $loader = $images_url.'loading.gif';
        $export = $this->url.'functions/masscsv.php';
        $initialDate = FNAC_Product::oldest();
        $currentDate = date('Y-m-d');

        $vars = explode(' ', Configuration::get('FNAC_LAST_IMPORTED'));
        $dateCSV = $vars[0];

        if (!$dateWS = Configuration::get('FNAC_LAST_UPDATED') && $dateCSV) {
            Configuration::updateValue('FNAC_LAST_UPDATED', $dateCSV);
            $dateWS = $dateCSV;
        } else {
            $dateWS = Configuration::get('FNAC_LAST_UPDATED');
        }

        $default_categories = unserialize(FNAC_Tools::decode(Configuration::get('FNAC_CATEGORIES')));

        if ($default_categories) {
            $categories = Category::getCategories($this->id_lang, false);
        } else {
            $categories = false;
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            if (!$id_shop) {
                $id_shop = 1;
            }

            $shop = new Shop($id_shop);
            if ($categories && count($categories) > 1) {
                $first = reset($categories[1]);
            } else {
                $first = null;
            }
            $default_category = $shop->id_category;
        } elseif ($categories && is_array($categories)) {
            foreach ($categories as $first1 => $categories_array) {
                foreach ($categories_array as $first2 => $categories_array2) {
                    $first = $categories[$first1][$first2];
                    break;
                }
                break;
            }
            $default_category = 1;
        }

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';
        $view_params['tab_selected_update'] = $selected_tab == 'update' ? 'selected' : '';
        $view_params['images_url'] = $images_url;
        $view_params['export'] = $export;
        $view_params['loader'] = $loader;
        $view_params['current_date'] = $currentDate;
        $view_params['request_uri'] = $_SERVER['REQUEST_URI'];
        $view_params['categories'] = array();

        if (is_array($default_categories)) {
            foreach ($default_categories as $id_category) {
                $cat_array = array();
                $cat_array['id_category'] = $id_category;
                $cat_array['desc_category'] = FNAC_Tools::cPath($id_category, $this->id_lang);
                $view_params['categories'][] = $cat_array;
            }
        }
        //print("<pre>".print_r(array("default"=>$default_categories, "view"=>$view_params),true)."</pre>"); die;

        $view_params['dateCSV'] = $dateCSV;
        $view_params['initialDate'] = $initialDate;
        $view_params['dateWS'] = $dateWS;


        return $view_params;
    }

    private function _csv()
    {
        $view_params = array();

        $name = 'fnac';
        $module = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$name.'/';
        $images_url = $module.'views/img/';
        $loader = $images_url.'loading.gif';
        $export = $module.'functions/products_create.php';
        $initialDate = FNAC_Product::oldest();
        $currentDate = date('Y-m-d');
        $file = date('Y-m-d').'_'.$name.'.csv';

        $outputUrl = $module.'exports_csv/'.$file;
        //$outputFile = dirname(__FILE__) . '/exports' . '/' . $file;
        $outputFile = dirname(_PS_MODULE_DIR_).'/'.basename(_PS_MODULE_DIR_).'/'.$name.'/exports_csv'.'/'.$file;

        $vars = explode(' ', Configuration::get('FNAC_LAST_IMPORTED'));
        $dateCSV = $vars[0];

        $default_categories = unserialize(FNAC_Tools::decode(Configuration::get('FNAC_CATEGORIES')));

        if ($default_categories) {
            $categories = Category::getCategories($this->id_lang, false);
        } else {
            $categories = false;
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            if (!$id_shop) {
                $id_shop = 1;
            }

            $shop = new Shop($id_shop);
            if ($categories && count($categories) > 1) {
                $first = reset($categories[1]);
            } else {
                $first = null;
            }
            $default_category = $shop->id_category;
        } elseif ($categories && is_array($categories)) {
            foreach ($categories as $first1 => $categories_array) {
                foreach ($categories_array as $first2 => $categories_array2) {
                    $first = $categories[$first1][$first2];
                    break;
                }
                break;
            }
            $default_category = 1;
        }

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';
        $view_params['tab_selected_update'] = $selected_tab == 'update' ? 'selected' : '';
        $view_params['images_url'] = $images_url;
        $view_params['export'] = $export;
        $view_params['loader'] = $loader;
        $view_params['current_date'] = $currentDate;
        $view_params['request_uri'] = $_SERVER['REQUEST_URI'];
        $view_params['outputFile'] = $outputFile;
        $view_params['outputUrl'] = $outputUrl;
        $view_params['categories'] = array();

        if (is_array($default_categories)) {
            foreach ($default_categories as $id_category) {
                $cat_array = array();
                $cat_array['id_category'] = $id_category;
                $cat_array['desc_category'] = FNAC_Tools::cPath($id_category, $this->id_lang);
                $view_params['categories'][] = $cat_array;
            }
        }
        //print("<pre>".print_r(array("default"=>$default_categories, "view"=>$view_params),true)."</pre>"); die;

        $view_params['dateCSV'] = $dateCSV;
        $view_params['initialDate'] = $initialDate;


        return $view_params;
    }


    private function _debug()
    {
        //$selected_tab = ($tab =  Tools::getValue('selected_tab')) ? $tab : 'update'  ;
        $view_params = array();

        return $view_params;
    }
}
