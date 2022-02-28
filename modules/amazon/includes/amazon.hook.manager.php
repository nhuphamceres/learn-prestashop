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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Suppose to manage all Amazon module hooks
 * Class AmazonHook
 */
class AmazonHookManager
{

    /**
     * @var Amazon
     */
    public $module;

    /**
     * @var Context
     */
    public $context;

    public function __construct($module, $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * Display Available at Amazon
     * @param $params
     *
     * @return string
     */
    public function displayProductButtons($params)
    {
        $associate_ids = AmazonConfiguration::get(Amazon::CONFIG_ASSOCIATE_ID);
        if (!is_array($associate_ids) || !count(array_filter($associate_ids))) {
            return '';
        }

        // Check input params
        if (!isset($params['product'])) {
            return '';
        }
        $product = $params['product'];
        if (is_object($product) && isset($product->id)) {
            $id_product = $product->id;
        } elseif (is_array($product) && isset($product['id'])) {
            $id_product = $product['id'];
        } else {
            return '';
        }

        if (isset($params['cart'])) {
            /** @var Cart $cart */
            $cart = $params['cart'];
            $id_lang = $cart->id_lang;
        } else {
            // Get lang on front end, not $this->context
            $id_lang = Context::getContext()->language->id;
        }

        $asin = $this->getProductAsin($id_product, $id_lang);

        if (is_string($asin) && !empty($asin)) {
            $product_link = $this->buildAmazonLink($asin, $id_lang);
            if ($product_link) {
                $this->context->smarty->assign(array(
                    'images_url' => $this->module->images,
                    'product_link' => $product_link,
                    'ps17x' => $this->module->ps17x,
                    'ps16x' => $this->module->ps16x,
                    'ps15x' => $this->module->ps15x,
                    'ps14x' => $this->module->ps14x,
                ));

                return $this->context->smarty->fetch($this->module->path . 'views/templates/front/available.tpl');
            }
        }

        return '';
    }

    /**
     * Get product asin, prioritize main product
     * @param $id_product
     * @param $id_lang
     *
     * @return bool
     */
    private function getProductAsin($id_product, $id_lang)
    {
        // Get product options by id_product_attribute priority (0 is the highest, to get main product)
        $product_options = AmazonProduct::getProductOptionsV4($id_product, null, $id_lang);
        if (is_array($product_options)) {
            usort($product_options, array($this, 'sortProductOptions'));
            foreach ($product_options as $product_option) {
                if (isset($product_option['asin1'])) {
                    return $product_option['asin1'];
                }
            }
        }

        return false;
    }

    /**
     * Sort product options by id_product_attribute
     * @param $option1
     * @param $option2
     *
     * @return int
     */
    protected function sortProductOptions($option1, $option2)
    {
        if (!isset($option1['id_product_attribute'], $option2['id_product_attribute'])) {
            return 0;
        }

        $id_attribute1 = (int)$option1['id_product_attribute'];
        $id_attribute2 = (int)$option2['id_product_attribute'];
        if ($id_attribute1 == $id_attribute2) {
            return 0;
        }

        return ($id_attribute1 < $id_attribute2) ? -1 : 1;
    }

    /**
     * Try to build an Amazon product link: https://www.amazon.{platform}/gp/product/asin/?[m=merchantID]&[tag=associateID]
     * @param $asin
     * @param $id_lang
     *
     * @return string
     */
    private function buildAmazonLink($asin, $id_lang)
    {
        $regions = AmazonConfiguration::get(Amazon::CONFIG_REGION);
        if (isset($regions[$id_lang]) && !empty($regions[$id_lang])) {
            $merchant_ids = AmazonConfiguration::get(Amazon::CONFIG_MERCHANT_ID);
            $associates = AmazonConfiguration::get(Amazon::CONFIG_ASSOCIATE_ID);

            $query_params = array();
            if (isset($merchant_ids[$id_lang]) && !empty($merchant_ids[$id_lang])) {
                $query_params['m'] = $merchant_ids[$id_lang];
            }
            if (isset($associates[$id_lang]) && !empty($associates[$id_lang])) {
                $query_params['tag'] = $associates[$id_lang];
            }
            $query_string = http_build_query($query_params);

            $domain = sprintf('https://www.amazon.%s', AmazonTools::idToDomain($id_lang));
            $product_link = "$domain/gp/product/$asin/" . ($query_string ? "?$query_string" : '');

            return $product_link;
        }

        return '';
    }

    public function displayAdminOrder($params)
    {
        require_once(_PS_MODULE_DIR_ . $this->module->name . '/classes/amazon.admin_order.class.php');
        $adminOrder = new AmazonAdminOrder();

        return $adminOrder->marketplaceOrderDisplay($params);
    }

    public function displayPDFInvoice($object)
    {
        require_once(_PS_MODULE_DIR_ . $this->module->name . '/classes/amazon.admin_order.class.php');
        require_once(_PS_MODULE_DIR_ . $this->module->name . '/classes/amazon.order.class.php');
        require_once(_PS_MODULE_DIR_ . $this->module->name . '/classes/amazon.order_item.class.php');

        /** @var OrderInvoice $order_invoice */
        $order_invoice = $object['object'];
        $id_order = $order_invoice->id_order;
        $customizationsByItems = array();

        $marketplace_order = AmazonOrder::getByOrderId($id_order);
        if (!$marketplace_order) {
            return '';
        }

        // Show product customization
        $order_detail = AmazonAdminOrder::getOrderDetailList($id_order);
        if (is_array($order_detail) && count($order_detail)) {
            foreach ($order_detail as $detail) {
                $item = AmazonOrderItem::getItem($detail['id_order_detail'], $id_order, $detail['product_id'], $detail['product_attribute_id']);
                if ($item && is_array($item) && isset($item['sku'], $item['customization']) && is_array($item['customization'])
                    && isset($item['customization']['type'], $item['customization']['data'])
                    && is_array($item['customization']['data']) && count($item['customization']['data'])) {

                    $customizationType = $item['customization']['type'];
                    $customizationData = $item['customization']['data'];

                    // Todo: Legacy is compatible only, remove in future
                    if ($customizationType == 'legacy') {
                        if (count($customizationData) === 1) {
                            $customizationsByItems[] = array(
                                'type' => 'legacy',
                                'item_name' => $item['sku'],
                                'data' => $customizationData[0],
                            );
                        } else {
                            $i = 1;
                            foreach ($customizationData as $customization_sku) {
                                $customizationsByItems[] = array(
                                    'type' => 'legacy',
                                    'item_name' => $item['sku'] . '[' . $i . ']',
                                    'data' => $customization_sku,
                                );
                                $i++;
                            }
                        }
                    } elseif ($customizationType == 'complete') {
                        $customizationsByItems[] = array(
                            'type' => 'complete',
                            'item_name' => $item['sku'],
                            'item_qty' => $item['quantity'],
                            'data' => $customizationData,
                        );
                    }
                }
            }
        }

        return $this->context->smarty->assign(array(
            'amazon_order_id' => $marketplace_order['mp_order_id'],
            'customization_by_items' => $customizationsByItems,
        ))->fetch($this->module->path . 'views/templates/admin/admin_order/invoice_additional_info.tpl');
    }

    /*********************************** Admin Order listing custom column ********************************************/
    // > PS1.7.7: https://devdocs.prestashop.com/1.7/development/components/grid/tutorials/modify-grid-in-module/
    /**
     * PS1.5 <= version < PS1.7.7
     * Same code for all our modules. Also modify others if change (amazon, cdiscount)
     * This hook is called 2 time for each module, 1 for filter then 1 for listing
     * @param $params
     */
    public function actionAdminOrdersListingFieldsModifier($params)
    {
        $module = $this->module->name;
        $moduleTbl = _DB_PREFIX_ . 'marketplace_orders';
        $moduleTblAlias = "cs_mp_order_alias";
        $moduleField = 'mp_order_id';

        if (isset($params['fields']) && !isset($params['select']) && !isset($params['join'])) {
            // Filter injection
            $this->pd("$module filter")->pd($params['fields']);

            if (!isset($params['fields']['mp_order_id'])) {
                $params['fields']['mp_order_id'] = array(
                    // Although identical to listing injection, but cannot omit. Otherwise, sort / search not working
                    'title' => 'Marketplace Order ID',  // Old PS shows breadcrumb: `filter by ...`
                    'filter_key' => "$moduleTblAlias!mp_order_id",    // Adjust filter key in search form
                    'cs_integrated' => false,
                );
            }
        } elseif (isset($params['fields'], $params['select'], $params['join'])) {
            // Listing injection
            $this->pd("$module listing")->pd($params['fields']);

            if (!isset($params['fields']['mp_order_id'])
                || !isset($params['fields']['mp_order_id']['cs_integrated']) || !$params['fields']['mp_order_id']['cs_integrated']) {
                $params['join'] .= " LEFT JOIN `$moduleTbl` AS `$moduleTblAlias` ON (a.`id_order` = $moduleTblAlias.`id_order`)";
                // Don't inject `select` statement to prevent duplicated selection,
                // it'll be managed automatically based on `fields`
                $params['fields']['mp_order_id'] = array(
                    'title' => 'Marketplace Order ID',
                    'align' => 'text-center',
                    'class' => 'fixed-width-xs',
                    'filter_key' => "$moduleTblAlias!mp_order_id",    // Adjust filter key in search form
                    'cs_integrated' => true,
                );
            }
        }

        $this->pd("$module params after resolve:")->pd($params);
    }

    public function actionOrderGridDefinitionModifier($params)
    {
        /** @var PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinition $definition */
        $definition = $params['definition'];

        /** @var PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection */
        $columns = $definition->getColumns();

        // Not possible to sort / search custom field at this moment. Waiting for updating from PS
        /** @var PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection $filters */
//        $filters = $definition->getFilters();

        $mpOrderIdColumn = new \PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn('mp_order_id');
        $mpOrderIdColumn->setName('Marketplace Order ID');
        $mpOrderIdColumn->setOptions(array('field' => 'mp_order_id'));
//        $mpOrderIdFilter = new PrestaShop\PrestaShop\Core\Grid\Filter\Filter(
//            'mp_order_id',
//            Symfony\Component\Form\Extension\Core\Type\TextType::class
//        );
//        $mpOrderIdFilter->setTypeOptions(array(
//            'required' => false,
//            'attr' => array(
//                'placeholder' => 'Marketplace Order ID',
//            )
//        ))->setAssociatedColumn('mp_order_id');

        $columns->addBefore('actions', $mpOrderIdColumn);
//        $filters->add($mpOrderIdFilter);
    }

    public function actionOrderGridQueryBuilderModifier($params)
    {
        /** @var Doctrine\DBAL\Query\QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        $moduleTbl = _DB_PREFIX_ . 'marketplace_orders';
        $moduleTblAlias = 'cs_tbl_alias_amazon';
        $moduleField = 'mp_order_id';

        $searchQueryBuilder->leftJoin('o', $moduleTbl, $moduleTblAlias, "o.id_order = $moduleTblAlias.id_order")
            ->addSelect("$moduleTblAlias.$moduleField AS $moduleField");
    }
    /******************************** End: Admin Order listing custom column ******************************************/

    /**
     * @param $debug
     * @return AmazonHookManager
     */
    private function pd($debug)
    {
        if (Amazon::$debug_mode) {
            AmazonTools::p($debug);
        }

        return $this;
    }
}
