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
 *
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.mirakl@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MiraklHookManager
{
    /** @var Mirakl */
    protected $module;

    /** @var Context */
    protected $context;

    protected $failedHooks = array();

    public function __construct($module, $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function getFailedHooks()
    {
        return $this->failedHooks;
    }

    public function registerHooks()
    {
        $expected_hooks = $this->getHookList();
        $result = true;

        foreach ($expected_hooks as $expected_hook) {
            if (!$this->module->isRegisteredInHook($expected_hook)) {
                if (!$this->module->registerHook($expected_hook)) {
                    $this->failedHooks[] = $expected_hook;
                    $result = false;
                }
            }
        }

        return $result;
    }

    public function unregisterHooks()
    {
        $expected_hooks = $this->getHookList();
        $result = true;

        foreach ($expected_hooks as $expected_hook) {
            if ($this->module->isRegisteredInHook($expected_hook)) {
                if (!$this->module->unregisterHook($expected_hook)) {
                    $this->failedHooks[] = $expected_hook;
                    $result = false;
                }
            }
        }

        return $result;
    }

    protected function getHookList()
    {
        // Normal hooks
        // 2021-06-01: Remove updateOrderStatus (1.4) / actionOrderStatusUpdate, use cronjob instead
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $expectedHooks = array('backOfficeHeader', 'adminOrder', 'updateCarrier');
        } else {
            $expectedHooks = array(
                'displayBackOfficeHeader',
                'actionCarrierUpdate',
                'displayAdminProductsExtra',
                'displayPDFInvoice',
            );

            // Order list hooks
            if (version_compare(_PS_VERSION_, '1.7.7', '<')) {
                $expectedHooks = array_merge($expectedHooks, array(
                    'actionAdminOrdersListingFieldsModifier',   // Shop mp_order_id in order list
                    'displayAdminOrder',
                ));
            } else {
                $expectedHooks = array_merge($expectedHooks, array(
                    'actionOrderGridDefinitionModifier',
                    'actionOrderGridQueryBuilderModifier',  // Shop mp_order_id in order list
                    'displayAdminOrderMain',
                ));
            }
        }

        // GDPR compliance. Module does not store any customer data, these hooks are symbolic only
        $expectedHooks[] = 'registerGDPRConsent';
        $expectedHooks[] = 'actionDeleteGDPRCustomer';
        $expectedHooks[] = 'actionExportGDPRData';

        return $expectedHooks;
    }

    /*********************************** Admin Order listing custom column ********************************************/
    // > PS1.7.7: https://devdocs.prestashop.com/1.7/development/components/grid/tutorials/modify-grid-in-module/
    /**
     * PS1.5 <= version < PS1.7.7
     * Same code for all our modules. Also modify others if change (amazon, cdiscount, mirakl)
     * This hook is called 2 times for each module, 1 for filter then 1 for listing
     * @param $params
     */
    public function actionAdminOrdersListingFieldsModifier($params)
    {
        $moduleTbl = _DB_PREFIX_ . 'marketplace_orders';
        $moduleTblAlias = "cs_mp_order_alias";
        $moduleField = 'mp_order_id';

        if (isset($params['fields']) && !isset($params['select']) && !isset($params['join'])) {
            // Filter injection
            if (!isset($params['fields'][$moduleField])) {
                $params['fields'][$moduleField] = array(
                    // Although identical to listing injection, but cannot omit. Otherwise, sort / search not working
                    'filter_key' => "$moduleTblAlias!$moduleField",    // Adjust filter key in search form
                    'cs_integrated' => false,
                );
                // todo: Cannot search?
                $params['fields']['latest_ship_date'] = array(
                    'filter_key' => "$moduleTblAlias!latest_ship_date",
                    'cs_integrated' => false,
                );
            }
        } elseif (isset($params['fields'], $params['select'], $params['join'])) {
            // Listing injection
            if (!isset($params['fields'][$moduleField])
                || !isset($params['fields'][$moduleField]['cs_integrated']) || !$params['fields'][$moduleField]['cs_integrated']) {
                $params['join'] .= " LEFT JOIN `$moduleTbl` AS `$moduleTblAlias` ON (a.`id_order` = $moduleTblAlias.`id_order`)";
                $params['select'] .= ", `$moduleTblAlias`.`$moduleField` AS `$moduleField`";
                $params['fields'][$moduleField] = array(
                    'title' => 'Marketplace Order ID',
                    'align' => 'text-center',
                    'class' => 'fixed-width-xs',
                    'filter_key' => "$moduleTblAlias!$moduleField",    // Adjust filter key in search form
                    'cs_integrated' => true,
                );
            }
            if (!isset($params['fields']['latest_ship_date'])
                || !isset($params['fields']['latest_ship_date']['cs_integrated']) || !$params['fields']['latest_ship_date']['cs_integrated']) {
                // No need to join anymore
                $params['select'] .= ", `$moduleTblAlias`.`latest_ship_date` AS `latest_ship_date`";
                $params['fields']['latest_ship_date'] = array(
                    'title' => 'Shipping deadline',
                    'align' => 'text-right',
                    'class' => 'fixed-width-xs',
                    'filter_key' => "$moduleTblAlias!latest_ship_date",    // Adjust filter key in search form
                    'cs_integrated' => true,
                );
            }
        }
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

        $mpShippingDeadline = new \PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn('latest_ship_date');
        $mpShippingDeadline->setName('Shipping deadline');
        $mpShippingDeadline->setOptions(array('field' => 'latest_ship_date'));
        $columns->addBefore('actions', $mpShippingDeadline);
    }

    public function actionOrderGridQueryBuilderModifier($params)
    {
        /** @var Doctrine\DBAL\Query\QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        $moduleTbl = _DB_PREFIX_ . 'marketplace_orders';
        $moduleTblAlias = 'cs_tbl_alias_mirakl';
        $moduleField = 'mp_order_id';

        $searchQueryBuilder->leftJoin('o', $moduleTbl, $moduleTblAlias, "o.id_order = $moduleTblAlias.id_order")
            ->addSelect("$moduleTblAlias.$moduleField AS $moduleField")
            ->addSelect("$moduleTblAlias.latest_ship_date AS latest_ship_date");
    }

    /******************************** End: Admin Order listing custom column ******************************************/

    public function displayPDFInvoice($object)
    {
        require_once dirname(__FILE__) . '/../classes/order.class.php';

        /** @var OrderInvoice $order_invoice */
        $order_invoice = $object['object'];
        $id_order = $order_invoice->id_order;
        $mpOrder = MiraklOrder::getByOrderId($id_order);
        if (!$mpOrder) {
            return '';
        }

        // Change variable names to avoid duplicate with other modules.
        // Eg. Amazon also has this hook, and it already assigned 'mp_order_id' to current smarty object
        $viewParams = array(
            'mirakl_order_id' => $mpOrder['mp_order_id'],
            'mirakl_shipping_deadline' => Tools::displayDate($mpOrder['latest_ship_date']),
        );

        if (Module::isEnabled('amazon')) {
            $amazonModule = Module::getInstanceByName('amazon');
            if ($amazonModule) {
                if ($amazonModule->isRegisteredInHook('displayPDFInvoice')) {
                    unset($viewParams['mirakl_order_id']);  // Amazon has already showed order ID, ignore
                }
            }
        }

        return $this->context->smarty->assign($viewParams)
            ->fetch($this->module->path . 'views/templates/admin/orders/admin_order_invoice_additional_info.tpl');
    }
}
