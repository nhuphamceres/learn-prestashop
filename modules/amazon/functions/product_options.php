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

require_once(dirname(__FILE__).'/../classes/amazon.context.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.tools.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order_info.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.product.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.strategies.class.php');


class ProductOptions extends Amazon
{
    public $id_product;
    private $_languages = array();

    public function __construct()
    {
        parent::__construct();

        AmazonContext::restore($this->context);
    }

    public function doIt()
    {
        $token = Configuration::get('AMAZON_INSTANT_TOKEN', null, 0, 0);

        if (!Tools::strlen($token) || Tools::getValue('amazon_token') != $token) {
            die('Unauthorized access');
        }

        $amazonTokens = AmazonConfiguration::get('CRON_TOKEN');
        $this->id_product = (int)Tools::getValue('id_product');

        $selected_tab = $this->selectedTab();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $context_key = AmazonContext::getKey($this->context->shop);
            $context_param = '?context_key='.$context_key;
        } else {
            $context_param = null;
        }

        $html = '
            <tr>
                <td colspan="2"><span id="amazon-options"><img src="'.$this->images.'a32.png" alt="" />&nbsp;&nbsp;<b>Amazon Market Place</b>&nbsp;&nbsp;&nbsp;<span style="color:grey">[</span><img src="../modules/amazon/views/img/plus.png" rel="../modules/amazon/views/img/minus.png" alt="" style="position:relative;top:-1px;" id="amz-toggle-img" /><span style="color:grey;margin-left:-1px;">]</span></span></td>
            </tr>
            <tr class="amazon-details">
                <td class="col-left" style="padding:15px 0 5px 5px;">'.$this->l('Amazon Platform').'<br /></td><td>'.'<span style="color:brown;">('.$this->l('Please note that all these options are optional').')</span> </td>
                <td style="padding-bottom:5px;">
                <input type="hidden" name="ps15" value="'.(version_compare(_PS_VERSION_, '1.5', '>=') ? 1 : 0).'" />
                <input type="hidden" name="amazon_logo" value="'.$this->images.'a16.png" />
                <input type="hidden" name="amazon_comb_loader" value="'.$this->images.'/green-loader.gif" />

                <input type="hidden" name="selected_tab" value="'.$selected_tab.'" />
                <input type="hidden" name="id_product" value="'.Tools::getValue('id_product').'" />
                <input type="hidden" id="amazon-product-options-url" value="'.$this->url.'functions/product_options.php" />
                <input type="hidden" id="amazon-product-options-json-url" value="'.$this->url.'functions/product_options_action.php'.$context_param.'" />
                <input type="hidden" id="amazon-product-bid-url" value="'.$this->url.'functions/product_bid.php'.$context_param.'" />
                <input type="hidden" id="amazon-product-bid-upd-url" value="'.$this->url.'functions/product_bid_update.php'.$context_param.'" />
                <input type="hidden" id="amz-text-strategy-reinit" value="'.$this->l('Be careful ! Are you sure that you want to re-initialize all strategies ?').'" />
                <input type="hidden" id="amz-text-propagate-shop" value="'.$this->l('Be careful ! Are you sure that you want to propagate this value to the entire shop ?').'" />
                <input type="hidden" id="amz-text-propagate-cat" value="'.$this->l('Be careful ! Are you sure that you want to propagate this value to all the products of this categories ?').'" />
                <input type="hidden" id="amz-text-propagate-manufacturer" value="'.$this->l('Be careful ! Are you sure that you want to change this value for all the products having the same Manufacturer ?').'" />
                <input type="hidden" id="amz-text-propagate-supplier" value="'.$this->l('Be careful ! Are you sure that you want to change this value for all the products having the same Supplier ?').'" />
                </td>
            </tr>
            ';


        $html .= '
            <tr class="amazon-details">
                <td style="padding-bottom:5px;" colspan="2">';


        $html .= $this->_tabs();

        $html .= '

      <div id="tabList">';

        foreach ($this->_languages as $language) {
            $id_lang = $language['id_lang'];

            if (!$language['region']) {
                continue;
            }

            $defaults = AmazonProduct::getProductOptions($this->id_product, $id_lang);

            $nextAction = AmazonProduct::marketplaceActionGet($this->id_product, $id_lang);

            $html .= '
            <div style="'.($selected_tab == $language['region'] ? '' : 'display:none;').'" id="menudiv-'.$language['region'].'"  class="tabItem '.($selected_tab == $language['region'] ? 'selected' : '').' ">

            <fieldset style="border:1px solid #B8B8B8;background-color:#F8F7F7;">
            <input type="hidden" name="id_lang" value="'.$id_lang.'" />
            <input type="hidden" id="lang-'.$id_lang.'" value="'.$language['iso_code'].'" />
            <input type="hidden" name="amazon_token['.$id_lang.']" value="'.$amazonTokens[$id_lang].'" />
            <input type="hidden" name="nopexport" value="0" />
            <input type="hidden" name="noqexport" value="0" />
            <table width="100%">
            <tr class="amazon-details">
                <td></td><td>
                ';


            $html .= '
            <tr class="amazon-details amazon-section-title">
                <td class="col-left">'.$this->l('Actions').'</td>
                <td></td>
             <tr>';


            $html .= $this->_action($id_lang, $nextAction);

            $html .= '
            <tr class="amazon-details amazon-section-title">
                <td class="col-left">'.$this->l('Data').'</td>
                <td></td>
             <tr>';

            $html .= $this->_bulletPoints($id_lang, $defaults);

            $html .= $this->_extraText($id_lang, $defaults['text']);


            $html .= '
            <tr class="amazon-details amazon-section-title">
                <td class="col-left">'.$this->l('Options').'</td>
                <td></td>
             <tr>';

            $html .= $this->_masterASIN($id_lang, $defaults['asin1']);

            $html .= $this->_extraPrice($id_lang, $defaults['price']);

            $html .= $this->_forceUnavailable($id_lang, $defaults['disable']);

            $html .= $this->_forceInStock($id_lang, $defaults['force']);

            $html .= $this->_nopexport($id_lang, $defaults['nopexport']);

            $html .= $this->_noqexport($id_lang, $defaults['noqexport']);

            $html .= $this->fbaOptions($id_lang, $defaults['fba']);

            $html .= $this->fbaPrice($id_lang, $defaults['fba_value'], $defaults['fba']);

            $html .= $this->_latency($id_lang, $defaults['latency']);

            $html .= $this->_gift($id_lang, $defaults['gift_wrap'], $defaults['gift_message']);

            if ($this->amazon_features['expert_mode']) {
                $html .= $this->shippingOverrides($id_lang, $defaults['shipping'], $defaults['shipping_type']);
            }

            if ($defaults['asin1']) {
                $html .= $this->_lookup($id_lang, $defaults['asin1']);
            }

            $html .= '
      <input type="hidden" name="amazon_option_lang[]" value="'.$id_lang.'" />';

            $image = $this->images.'geo_flags/'.$language['iso_code'].'.gif';

            $html .= '<span class="amazon-tab-lang"><img src="'.$image.'" style="position:relative;top:-2px;" alt="'.$language['name'].'" />&nbsp;&nbsp;'.preg_replace('/\(.*/', '', $language['name']).'</span>';


            $html .= '

            </td>
            </tr>
         </table>
         </fieldset>
         ';


            $html .= '</div>';
        }

        $html .= '
         </div>
        </td>
       </tr>';


        $conf = (version_compare(_PS_VERSION_, '1.6', '>=')) ? 'alert alert-success' : 'conf';

        $html .= '
        <tr class="amazon-details">
            <td colspan="2" style="padding-bottom:5px;">
                <hr style="margin-left:25%;width:50%" />
                <span style="margin-left:25%;color:brown;font-weight:bold;font-size:0.8em">'.$this->l('Don\'t forget to click on the save button linked to this sub-tab if you modify this configuration !').'</span>
            </td>
        </tr>
        <tr class="amazon-details">
            <td class="col-left"></td>
            <td style="padding:0 30px 5px 0;float:right;">
                <div class="'.$conf.'" style="display:none" id="result-amz"></div>
                <span id="amazon-save-loader" style="display:none"><img src="'.$this->images.'green-loader.gif" style="float:right;margin-left:5px;position:relative;top:+3px" alt="" /></span>
                <input type="button" style="float:right" id="amazon-save-options" class="button" value="'.$this->l('Save Amazon Parameters').'" . />

             </td>
        </tr>';


        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $html .= '<tr>
                <td colspan="2" style="padding-bottom:5px;"><hr style="width:100%" /></td>
            </tr>
       ';
        }

        echo $html;
    }

    private function selectedTab()
    {
        return (($selected_tab = Tools::getValue('selected_tab')) ? $selected_tab : ($this->id_lang ? Tools::strtolower(Language::getIsoById($this->id_lang)) : Tools::strtolower(Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT')))));
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        static $lang = null;

        if(!$lang)
            $lang = Amazon::availableLang(Language::getIsoById($this->id_lang));

        return (parent::l($string, basename(__FILE__, '.php'), $lang));
    }

    private function _tabs()
    {
        $html = '';

        $amazonEurope = $this->amazon_features['amazon_europe'];

        $actives = AmazonConfiguration::get('ACTIVE');
        $marketPlaceIds = AmazonConfiguration::get('MARKETPLACE_ID');

        // Regions
        //
        $marketPlaceRegion = AmazonConfiguration::get('REGION');

        if (is_array($marketPlaceRegion)) {
            $marketLang2Region = array_flip($marketPlaceRegion);
        } else {
            $marketLang2Region = array();
        }

        foreach (AmazonTools::languages() as $language) {
            $id_lang = $language['id_lang'];

            if (!(isset($actives[$id_lang]) && (int)$actives[$id_lang])) {
                continue;
            }

            if ($amazonEurope) {
                if (isset($marketPlaceIds[$id_lang]) && AmazonTools::isEuropeMarketplaceId($marketPlaceIds[$id_lang])) {
                    $language['europe'] = true;
                }
            }

            $language['region'] = $marketPlaceRegion[$id_lang];

            $this->_languages[$id_lang] = $language;
        }


        $selected_tab = $this->selectedTab();

        $html .= '
            <div style="clear:both"></div>
            <ul id="menuTab">';
        $i = 0;

        foreach ($this->_languages as $language) {
            $image = $this->images.'geo_flags/'.$language['iso_code'].'.gif';

            if (!$language['region']) {
                continue;
            }

            $html .= '<li id="menu-'.$language['region'].'" class="menuTabButton '.($selected_tab == $language['region'] ? 'selected' : '').'"><span>&nbsp;<img src="'.$image.'" alt="'.preg_replace('/ .*/', '', $language['name']).'" />&nbsp;'.preg_replace('/ .*/', '', $language['name']).'</span>  </li>';
            $i++;
        }

        $html .= '
          </ul>
    ';

        return ($html);
    }

    private function _action($id_lang, $default)
    {
        if (AmazonProduct::marketplaceInCategories($this->id_product)) {
            if (!$default) {
                $default = Amazon::UPDATE;
            }

            $html = '
            <tr class="amazon-details">
                <td class="col-left"></td>
                <td style="padding-bottom:5px;">
                    <span class="amz-action-container"><span class="amz-action"><input type="radio" name="amz-action-'.$id_lang.'" id="amz-action-'.$id_lang.'" value="'.Amazon::UPDATE.'" '.($default == Amazon::UPDATE ? 'checked="checked"' : '').' /><span class="amz-action-label">'.$this->l('Update').'</span></span><img src="'.$this->images.'/check.png" alt="'.$this->l('Update').'" class="amz-action-img" /></span>&nbsp;
                    <span class="amz-action-container"><span class="amz-action"><input type="radio" name="amz-action-'.$id_lang.'" id="amz-action-'.$id_lang.'" value="'.Amazon::ADD.'" '.($default == Amazon::ADD ? 'checked="checked"' : '').' /><span class="amz-action-label">'.$this->l('Create').'</span></span><img src="'.$this->images.'/add.png" alt="'.$this->l('Create').'" class="amz-action-img" /></span>&nbsp;
                    <span class="amz-action-container"><span class="amz-action"><input type="radio" name="amz-action-'.$id_lang.'" id="amz-action-'.$id_lang.'" value="'.Amazon::REMOVE.'" '.($default == Amazon::REMOVE ? 'checked="checked"' : '').' /><span class="amz-action-label">'.$this->l('Delete').'</span></span><img src="'.$this->images.'/delete.gif" alt="'.$this->l('Delete').'" class="amz-action-img" /></span>
                        <br />
                <span class="amz-small-line">'.$this->l('Action which will be applied to this item on next synchronization').'</span><br />
                <span class="amz-small-line">'.$this->l('Propagate this value to all products in this').' :
                                <a href="javascript:void(0)" id="amz-propagate-action-category-'.$id_lang.'" class="amz-propagate-action-cat amz-link">[ '.$this->l('Category').' ]</a>&nbsp;&nbsp;
                                <a href="javascript:void(0)" id="amz-propagate-action-shop-'.$id_lang.'" class="amz-propagate-action-shop amz-link">[ '.$this->l('Shop').' ]</a>&nbsp;&nbsp;
                                <a href="javascript:void(0)" id="amz-propagate-action-manufacturer-'.$id_lang.'" class="amz-propagate-action-manufacturer amz-link">[ '.$this->l('Manufacturer').' ]</a>&nbsp;&nbsp;
                                <a href="javascript:void(0)" id="amz-propagate-action-supplier-'.$id_lang.'" class="amz-propagate-action-supplier amz-link">[ '.$this->l('Supplier').' ]</a>&nbsp;&nbsp;
                </span>
                <span id="action-loader-'.$id_lang.'" style="display:none"><img src="'.$this->images.'green-loader.gif" style="margin-left:5px;" alt="" /></span>
                </td>
            </tr>
            ';
        } else {
            $html = '
            <tr class="amazon-details">
                <td class="col-left"></td>
                <td style="padding-bottom:5px;"><span class="not-amazon">'.$this->l('This product is not within the categories selected for export on Amazon').'</span>
                </td>
            </tr>';
        }

        return ($html);
    }

    private function _bulletPoints($id_lang, $default = null)
    {
        $inputs = null;
        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('Key Product Features').'</td>
            <td style="padding-bottom:5px;">';

        $first = 1;
        $item = 1;
        foreach (array('bullet_point1', 'bullet_point2', 'bullet_point3', 'bullet_point4', 'bullet_point5') as $key) {
            $bullet = isset($default[$key]) ? trim($default[$key]) : null;

            if (empty($bullet) && $first != 1) {
                continue;
            }

            if ($first) {
                $action_line = '
                        <img src="'.$this->images.'plus.png" alt="'.$this->l('Add').'" class="amazon-bullet-point-add" id="amazon-bullet-point-add-'.$item.'-'.$id_lang.'" />
                        <img src="'.$this->images.'minus.png" alt="'.$this->l('Remove').'" style="display:none" class="amazon-bullet-point-del" id="amazon-bullet-point-del-'.$item.'-'.$id_lang.'" />';
            } else {
                $action_line = '
                        <img src="'.$this->images.'minus.png" alt="'.$this->l('Remove').'" class="amazon-bullet-point-del" id="amazon-bullet-point-del-'.$item.'-'.$id_lang.'" />
                        <img src="'.$this->images.'plus.png" alt="'.$this->l('Add').'" style="display:none" class="amazon-bullet-point-add" id="amazon-bullet-point-add-'.$item.'-'.$id_lang.'" />';
            }
            $first = false;

            $inputs .= '
                <span id="amazon-bullet-container-'.$item.'-'.$id_lang.'" class="amazon-bullet-container-'.$id_lang.'"><!-- input container -->
                <input type="text" name="amz-bulletpoint-'.$id_lang.'[]" value="'.$bullet.'" class="amazon-bullet-point" />
                    <span class="bulletpoint-action">'.$action_line.'
                    </span><br />
                </span>
                    ';
            $item++;
        }
        $html .= $inputs.'<span class="amz-small-line">'.$this->l('Also called "Bullet Points". Text used to describe your product (up to 2000 characters per line).').'</span><br />
            <span class="amz-small-line">'.$this->l('You can add up to 5 bullets points. This is highly recommended by Amazon.').'</span><br />
            <span class="amz-small-line">'.$this->l('Propagate this value to all products in this').' :
                <a href="javascript:void(0)" id="amz-propagate-bulletpoint-cat-'.$id_lang.'" class="amz-propagate-bulletpoint-cat amz-link">[ '.$this->l('Category').' ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" id="amz-propagate-bulletpoint-shop-'.$id_lang.'" class="amz-propagate-bulletpoint-shop amz-link">[ '.$this->l('Shop').' ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" id="amz-propagate-bulletpoint-manufacturer-'.$id_lang.'" class="amz-propagate-bulletpoint-manufacturer amz-link">[ '.$this->l('Manufacturer').' ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" id="amz-propagate-bulletpoint-supplier-'.$id_lang.'" class="amz-propagate-bulletpoint-supplier amz-link">[ '.$this->l('Supplier').' ]</a>&nbsp;&nbsp;
            </span>
            <input type="hidden" value="'.$this->l('You can\'t add more than 5 bullet points !').'" name="amz-text-max-bullet" />
            <span id="extra-bulletpoint-loader-'.$id_lang.'" style="display:none"><img src="'.$this->images.'green-loader.gif" style="margin-left:5px;position:relative;top:-8px" alt="" /></span>
            </td>
        </tr>
        ';

        return ($html);
    }

    private function _extraText($id_lang, $default)
    {
        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('Condition Text').'</td>
            <td style="padding-bottom:5px;">
            <input type="text" name="amz-text-'.$id_lang.'" value="'.$default.'" style="width:500px" /><br />
            <span class="amz-small-line">'.$this->l('Short text about product condition/state which will appear on the product sheet on Amazon').'</span><br />
            <span class="amz-small-line">'.$this->l('Propagate this value to all products in this').' :
                <a href="javascript:void(0)" id="amz-propagate-text-cat-'.$id_lang.'" class="amz-propagate-text-cat amz-link">[ '.$this->l('Category').' ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" id="amz-propagate-text-shop-'.$id_lang.'" class="amz-propagate-text-shop amz-link">[ '.$this->l('Shop').' ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" id="amz-propagate-text-manufacturer-'.$id_lang.'" class="amz-propagate-text-manufacturer amz-link">[ '.$this->l('Manufacturer').' ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" id="amz-propagate-text-supplier-'.$id_lang.'" class="amz-propagate-text-supplier amz-link">[ '.$this->l('Supplier').' ]</a>&nbsp;&nbsp;
            </span>

            <span id="extra-text-loader-'.$id_lang.'" style="display:none"><img src="'.$this->images.'green-loader.gif" style="margin-left:5px;position:relative;top:-8px" alt="" /></span>
            </td>
        </tr>
        ';

        return ($html);
    }

    private function _masterASIN($id_lang, $default)
    {
        $checked = $default ? 'checked="checked"' : '';
        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('ASIN').'</td>
            <td style="padding-bottom:5px;">
            <input type="text" id="amz-asin-'.$id_lang.'" name="amz-asin-'.$id_lang.'" value="'.$default.'" style="width:120px" />&nbsp;&nbsp;
                <input type="hidden" id="amz-asin-mustbeset" value="'.$this->l('EAN13 or UPC must be set !').'" />
                <input type="button" class="button" id="amz-ean-asin-'.$id_lang.'" value="'.$this->l('UPC/EAN > ASIN').'" />&nbsp;&nbsp;';

        $html .= '
                <img src="'.$this->images.'green-loader.gif" alt="" id="asin-loader-'.$id_lang.'" style="display:none;position:relative;top:-4px;" /><br />
                <div class="asin-response" id="asin-response-'.$id_lang.'" style="display:none;" >&nbsp;</div>
            <span class="amz-small-line">'.$this->l('Master ASIN - Usefull only for products <b><u>without combinations</u></b>').'</span>

            </td>
        </tr>
        ';

        return ($html);
    }

    private function _extraPrice($id_lang, $default)
    {
        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('Price Override').'</td>
            <td style="padding-bottom:5px;">
            <input type="text" name="amz-price-'.$id_lang.'" value="'.((float)$default ? sprintf('%.02f', $default) : '').'" style="width:95px" /><br />
            <span class="amz-small-line">'.$this->l('Net Price for Amazon. This value will override your Shop Price').'</span><br />
            </td>
        </tr>
        ';

        return ($html);
    }

    private function _forceUnavailable($id_lang, $default)
    {
        $checked = $default ? 'checked="checked"' : '';
        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('Disabled').'</td>
            <td style="padding-bottom:5px;">
            <input type="checkbox" name="amz-disable-'.$id_lang.'" value="1" '.$checked.' />
            <span style="margin-left:10px">'.$this->l('Check this box to make this product unavailable on Amazon').'</span><br />
            <span class="amz-small-line">'.$this->l('Propagate this value to all products in this').' :
                        <a href="javascript:void(0)" id="amz-propagate-disable-category-'.$id_lang.'" class="amz-propagate-disable-cat amz-link">[ '.$this->l('Category').' ]</a>&nbsp;&nbsp;
                        <a href="javascript:void(0)" id="amz-propagate-disable-manufacturer-'.$id_lang.'" class="amz-propagate-disable-manufacturer amz-link">[ '.$this->l('Manufacturer').' ]</a>&nbsp;&nbsp;
                        <a href="javascript:void(0)" id="amz-propagate-disable-supplier-'.$id_lang.'" class="amz-propagate-disable-supplier amz-link">[ '.$this->l('Supplier').' ]</a>&nbsp;&nbsp;
            </span>
            <span id="force-loader-'.$id_lang.'" style="display:none"><img src="'.$this->images.'green-loader.gif" style="margin-left:5px;" alt="" /></span>

            </td>
        </tr>
        ';

        return ($html);
    }

    private function _forceInStock($id_lang, $default)
    {
        $checked = $default ? 'checked="checked"' : '';
        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('Force in Stock').'</td>
            <td style="padding-bottom:5px;">
            <input type="checkbox" name="amz-force-'.$id_lang.'" value="1" '.$checked.' />
            <span style="margin-left:10px">'.$this->l('The product will always appear on Amazon, even if it\'s out of Stock').'</span><br />
            <span class="amz-small-line">'.$this->l('Propagate this value to all products in this').' :
                            <a href="javascript:void(0)" id="amz-propagate-force-category-'.$id_lang.'" class="amz-propagate-force-cat amz-link">[ '.$this->l('Category').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-force-shop-'.$id_lang.'" class="amz-propagate-force-shop amz-link">[ '.$this->l('Shop').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-force-manufacturer-'.$id_lang.'" class="amz-propagate-force-manufacturer amz-link">[ '.$this->l('Manufacturer').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-force-supplier-'.$id_lang.'" class="amz-propagate-force-supplier amz-link">[ '.$this->l('Supplier').' ]</a>&nbsp;&nbsp;
                            </span>
            <span id="force-loader-'.$id_lang.'" style="display:none"><img src="'.$this->images.'green-loader.gif" style="margin-left:5px;position:relative;top:-8px" alt="" /></span>
            </td>
        </tr>
        ';

        return ($html);
    }

    private function _nopexport($id_lang, $default)
    {
        $checked = $default ? 'checked="checked"' : '';
        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('Price').'</td>
            <td style="padding-bottom:5px;">
            <input type="checkbox" name="amz-nopexport-'.$id_lang.'" value="1" '.$checked.' />
            <span style="margin-left:10px">'.$this->l('Do not synchronize the price').'</span>
            </td>
        </tr>
        ';

        return ($html);
    }

    private function _noqexport($id_lang, $default)
    {
        $checked = $default ? 'checked="checked"' : '';
        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('Quantity').'</td>
            <td style="padding-bottom:5px;">
            <input type="checkbox" name="amz-noqexport-'.$id_lang.'" value="1" '.$checked.' />
            <span style="margin-left:10px">'.$this->l('Do not synchronize the quantity').'</span>
            </td>
        </tr>
        ';

        return ($html);
    }

    private function fbaOptions($id_lang, $default)
    {
        if (isset($this->_languages[$id_lang]['europe'])) {
            $europe = 'rel="europe"';
        } else {
            $europe = '';
        }

        $checked = $default ? 'checked="checked"' : '';
        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('FBA').' </td>
            <td style="padding-bottom:5px;">
            <input type="checkbox" name="amz-fba-'.$id_lang.'" value="1" '.$checked.' '.$europe.' />
            <span style="margin-left:10px">'.$this->l('Fulfillment by Amazon (FBA)').'</span><br />
            <span class="amz-small-line">'.$this->l('Propagate this value to all products in this').' :
                            <a href="javascript:void(0)" id="amz-propagate-fba-category-'.$id_lang.'" class="amz-propagate-fba-cat amz-link">[ '.$this->l('Category').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-fba-shop-'.$id_lang.'" class="amz-propagate-fba-shop amz-link">[ '.$this->l('Shop').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-fba-manufacturer-'.$id_lang.'" class="amz-propagate-fba-manufacturer amz-link">[ '.$this->l('Manufacturer').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-fba-supplier-'.$id_lang.'" class="amz-propagate-fba-supplier amz-link">[ '.$this->l('Supplier').' ]</a>&nbsp;&nbsp;
            </span>
            <span id="fba-loader-'.$id_lang.'" style="display:none"><img src="'.$this->images.'green-loader.gif" style="margin-left:5px;" alt="" /></span>
            </td>
        </tr>
        ';

        return ($html);
    }

    private function fbaPrice($id_lang, $default, $isFBA)
    {
        $checked = $default ? 'checked="checked"' : '';

        $html = '
        <tr class="amazon-details amazon-item-title fba" rel="'.$isFBA.'" >
            <td class="col-left">'.$this->l('FBA - Value Added').' </td>
            <td style="padding-bottom:5px;">
            <input type="text" name="amz-fbavalue-'.$id_lang.'" style="width:95px" value="'.((float)$default ? sprintf('%.02f', $default) : '').'" />
            <span style="margin-left:10px">'.$this->l('Additionnal value for FBA handled items').'</span><br />
            <span class="amz-small-line">'.$this->l('This value will be added to the product price. It overrides FBA formula.').'</span><br />
            <span class="amz-small-line">'.$this->l('Propagate this value to all products in this').' :
                            <a href="javascript:void(0)" id="amz-propagate-fbavalue-category-'.$id_lang.'" class="amz-propagate-fbavalue-cat amz-link">[ '.$this->l('Category').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-fbavalue-shop-'.$id_lang.'" class="amz-propagate-fbavalue-shop amz-link">[ '.$this->l('Shop').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-fbavalue-manufacturer-'.$id_lang.'" class="amz-propagate-fbavalue-manufacturer amz-link">[ '.$this->l('Manufacturer').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-fbavalue-supplier-'.$id_lang.'" class="amz-propagate-fbavalue-supplier amz-link">[ '.$this->l('Supplier').' ]</a>&nbsp;&nbsp;
            </span>
            <span id="fbavalue-loader-'.$id_lang.'" style="display:none"><img src="'.$this->images.'green-loader.gif" style="margin-left:5px;" alt="" /></span>
            </td>
        </tr>
        ';

        return ($html);
    }

    private function _latency($id_lang, $default)
    {
        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('Latency').'</td>
            <td style="padding-bottom:5px;">
            <input type="text" name="amz-latency-'.$id_lang.'" value="'.$default.'" style="width:40px" /><br />
            <span class="amz-small-line">'.$this->l('Latency delay in days before shipping this product').'</span><br />
            <span class="amz-small-line">'.$this->l('Propagate this value to all products in this').' :
                            <a href="javascript:void(0)" id="amz-propagate-latency-category-'.$id_lang.'" class="amz-propagate-latency-cat amz-link">[ '.$this->l('Category').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-latency-shop-'.$id_lang.'" class="amz-propagate-latency-shop amz-link">[ '.$this->l('Shop').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-latency-manufacturer-'.$id_lang.'" class="amz-propagate-latency-manufacturer amz-link">[ '.$this->l('Manufacturer').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-latency-supplier-'.$id_lang.'" class="amz-propagate-latency-supplier amz-link">[ '.$this->l('Supplier').' ]</a>&nbsp;&nbsp;
            </span>
            <span id="latency-loader-'.$id_lang.'" style="display:none"><img src="'.$this->images.'green-loader.gif" style="margin-left:5px;position:relative;top:-8px" alt="" /></span>
            </td>
        </tr>
        ';

        return ($html);
    }

    private function _gift($id_lang, $gift_wrap, $gift_message)
    {
        $gift_wrap_checked = $gift_wrap ? 'checked="checked"' : '';
        $gift_message_checked = $gift_message ? 'checked="checked"' : '';

        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('Gift Option').'</td>
            <td style="padding-bottom:5px;">
            <input type="checkbox" name="amz-giftwrap-'.$id_lang.'" value="1" '.$gift_wrap_checked.' /><span style="margin-left:10px">'.$this->l('Gift Wrap').'</span>
            <input type="checkbox" name="amz-giftmessage-'.$id_lang.'" value="1" '.$gift_message_checked.' /><span style="margin-left:10px">'.$this->l('Gift Message').'</span><br />
            <span class="amz-small-line">'.$this->l('Allow the buyer to check the giftwrap option').'</span><br />
            <span class="amz-small-line">'.$this->l('Propagate this value to all products in this').' :
                        <a href="javascript:void(0)" id="amz-propagate-gift-category-'.$id_lang.'" class="amz-propagate-gift-cat amz-link">[ '.$this->l('Category').' ]</a>&nbsp;&nbsp;
                        <a href="javascript:void(0)" id="amz-propagate-gift-shop-'.$id_lang.'" class="amz-propagate-gift-shop amz-link">[ '.$this->l('Shop').' ]</a>&nbsp;&nbsp;
                        <a href="javascript:void(0)" id="amz-propagate-gift-manufacturer-'.$id_lang.'" class="amz-propagate-gift-manufacturer amz-link">[ '.$this->l('Manufacturer').' ]</a>&nbsp;&nbsp;
                        <a href="javascript:void(0)" id="amz-propagate-gift-supplier-'.$id_lang.'" class="amz-propagate-gift-supplier amz-link">[ '.$this->l('Supplier').' ]</a>&nbsp;&nbsp;
            </span>
            <span id="gift-loader-'.$id_lang.'" style="display:none"><img src="'.$this->images.'green-loader.gif" style="margin-left:5px;" alt="" /></span>

            </td>
        </tr>
        ';

        return ($html);
    }

    private function shippingOverrides($id_lang, $default1, $default2)
    {
        $checked1 = $default2 == 1 || !$default1 ? ' checked="checked"' : '';
        $checked2 = $default2 == 2 ? ' checked="checked"' : '';

        $default1 = ($default1 == null) ? '' : sprintf('%.02f', $default1);

        $html = '
        <tr class="amazon-details amazon-item-title">
            <td class="col-left">'.$this->l('Shipping Override').'</td>
            <td style="padding-bottom:5px;">
            <input type="text" name="amz-shipping-'.$id_lang.'" value="'.$default1.'" style="width:60px" />&nbsp;&nbsp;&nbsp;
            <input type="radio" name="amz-overridetype-'.$id_lang.'" value="1" '.$checked1.'/><span style="position:relative;top:+3px;margin-left:5px;">'.$this->l('Standard').'</span>&nbsp;&nbsp;&nbsp;
            <input type="radio" name="amz-overridetype-'.$id_lang.'" value="2" '.$checked2.'/><span style="position:relative;top:+3px;margin-left:5px;">'.$this->l('Express').'</span>
            <br />
            <span class="amz-small-line">'.$this->l('Shipping charges that override the default ones fixed in your Amazon Backoffice').'</span><br />
            <span class="amz-small-line">'.$this->l('Propagate this shipping charges to all products in this').' :
                            <a href="javascript:void(0)" id="amz-propagate-shipping-cat-'.$id_lang.'" class="amz-propagate-shipping-cat amz-link">[ '.$this->l('Category').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-shipping-shop-'.$id_lang.'" class="amz-propagate-shipping-shop amz-link">[ '.$this->l('Shop').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-shipping-manufacturer-'.$id_lang.'" class="amz-propagate-shipping-manufacturer amz-link">[ '.$this->l('Manufacturer').' ]</a>&nbsp;&nbsp;
                            <a href="javascript:void(0)" id="amz-propagate-shipping-supplier-'.$id_lang.'" class="amz-propagate-shipping-supplier amz-link">[ '.$this->l('Supplier').' ]</a>&nbsp;&nbsp;
            </span>

            <span id="shipping-loader-'.$id_lang.'" style="display:none"><img src="'.$this->images.'green-loader.gif" style="margin-left:5px;" alt="" /></span>
            </td>
        </tr>
        ';

        return ($html);
    }

    private function _strategies($id_lang)
    {
        if (!$this->id_product) {
            return (null);
        }

        $product = new Product($this->id_product, false, $id_lang);

        if ($product->hasAttributes()) {
            $html = '<input type="hidden" id="amazon-strategies-'.$id_lang.'" />';
        } else {
            $html = '
            <tr class="amazon-details amazon-item-title">
                <td class="col-left">'.$this->l('Strategies').'</td>
                <td style="padding-bottom:10px;">
                <span>'.$this->l('Advanced strategies for this Product').':&nbsp;&nbsp;</span>
                <input type="button" id="amazon-strategies-'.$id_lang.'" class="button" value="'.$this->l('Configure').'" />
                <img src="'.$this->images.'/green-loader.gif" class="amz-small-loader" style="display:none" />
                </td>
            </tr>
            ';
        }

        return ($html);
    }

    private function _lookup($id_lang, $asin)
    {
        $url = AmazonTools::goToProductPage($id_lang, $asin);

        $html = '
        <tr class="amazon-details">
            <td class="col-left">&nbsp;</td>
            <td style="padding-bottom:10px;">
            <input type="button" id="amazon-goto-'.$asin.'" class="button" rel="'.$url.'" value="'.$this->l('Go to the Amazon Product Page').'" />

            </td>
        </tr>
        ';

        return ($html);
    }
}

$amazonProductOptions = new ProductOptions();
$amazonProductOptions->doIt();
