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
 * @author    Abdul Mohymen (info@kernelbd.com)
 * @copyright (c) Copyright 2014 Kernel BD Corporation. All Rights Reserved.
 * @license   Commercial license
 * Support by mail  :  support.mirakl@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/*
 * Filename:                Mirakl/Products.php
 * Descripton:                Get products for a list of product's references
 * Mirakl Marketplace:        v3.14.0 (2014)
 * Version:                    v1.0
 * Author:                    Abdul Mohymen (info@kernelbd.com)
 * Copyright:                (c) Copyright 2014 Kernel BD Corporation. All Rights Reserved.
 * Requirements:
 *                            - PHP with XML,CURL, JSON support
 *                            - Mirakl/Base.php
 */

require_once dirname(__FILE__).'/mirakl.webservice.class.php';
if (!class_exists('MiraklApiProducts')) {
    class MiraklApiProducts extends MiraklWebservice
    {
        public function __construct($marketplace_params)
        {
            parent::__construct($marketplace_params);
            $this->service = "products";
        }

        public function products($filters = array())
        {
            /*******************************************************************************************
             * Descripton:    Get products for a list of product's references
             * Params:        @products - [required] List of the product's identifiants with type
             *                    (structure : "products=<productId>|<productIdType>,<productId>|<productIdType>, ...").
             *                    Example: /api/products?products=3120201243238|EAN
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = null;
            $this->service_code = 'P31';

            if (!isset($filters['products']) || empty($filters['products'])) {
                return $this->errors(10, 'PRODUCT_ID_MISSING');
            }

            $params = '';
            if (isset($filters['products']) && !empty($filters['products'])) {
                $params .= "&products={$filters['products']}";
            }
            if (isset($filters['order']) && !empty($filters['order'])) {
                $params .= "&order={$filters['order']}";
            }
            if (isset($filters['max']) && !empty($filters['max'])) {
                $params .= "&max={$filters['max']}";
            }
            if (isset($filters['offset']) && !empty($filters['offset'])) {
                $params .= "&offset={$filters['offset']}";
            }

            $this->service_url = $this->endpoint.$this->service.'/?api_key='.$this->api_key.$params;

            return $this->parse($this->get());
        }

        public function offers($filters = array())
        {
            /*******************************************************************************************
             * Descripton:    Get products for a list of product's references
             * Params:    @product_ids - [required] List of product's skus separated with comma. Limited to 100 values.
             *
             * @offer_state_codes - [required] List of offer state codes
             * @premium -  [optional] Boolean : "ALL" (default), "FALSE" or "TRUE".
             *                        If TRUE (FALSE) returns only offers of premium (not premium) shop.
             * @all_offers - [optional] Boolean : "FALSE" (default) or "TRUE". If FALSE (TRUE) returns only active (all) offers.
             * @channel_codes - [optional] List of the channel codes to filter with, using a comma (,) as a separator.
             * @all_channels - [optional] Boolean: "FALSE" (default) or "TRUE".
             *                            If FALSE, filter the offers with the given channelCodes or the default channel if no channelCodes are given.
             *                            If "TRUE", does not filter the offers on channels and ignore the channelCodes.
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'offers';
            $this->service_code = 'P11';

            if (!isset($filters['product_ids']) || empty($filters['product_ids'])) {
                return $this->errors(10, 'PRODUCT_ID_MISSING');
            }
            if (!isset($filters['offer_state_codes']) || empty($filters['offer_state_codes'])) {
                return $this->errors(10, 'OFFER_STATECODE_MISSING');
            }
            $params = '';
            if (isset($filters['product_ids']) && !empty($filters['product_ids'])) {
                $params .= "&product_ids={$filters['product_ids']}";
            }
            if (isset($filters['offer_state_codes']) && !empty($filters['offer_state_codes'])) {
                $params .= "&offer_state_codes={$filters['offer_state_codes']}";
            }
            if (isset($filters['premium']) && !empty($filters['premium'])) {
                $params .= "&premium={$filters['premium']}";
            }
            if (isset($filters['all_offers']) && !empty($filters['all_offers'])) {
                $params .= "&all_offers={$filters['all_offers']}";
            }
            if (isset($filters['channel_codes']) && !empty($filters['channel_codes'])) {
                $params .= "&channel_codes={$filters['channel_codes']}";
            }
            if (isset($filters['all_channels']) && !empty($filters['all_channels'])) {
                $params .= "&all_channels={$filters['all_channels']}";
            }
            if (isset($filters['order']) && !empty($filters['order'])) {
                $params .= "&order=".MiraklHelperQuery::order($filters['order']);
            }
            if (isset($filters['max']) && !empty($filters['max'])) {
                $params .= "&max=".MiraklHelperQuery::pageMax($filters['max']);
            }
            if (isset($filters['offset']) && !empty($filters['offset'])) {
                $params .= "&offset=".MiraklHelperQuery::pageOffset($filters['offset']);
            }

            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/?api_key=".$this->api_key.$params;

            return $this->parse($this->get());
        }

        public function imports($import_id = 0)
        {
            /*******************************************************************************************
             * Descripton:    Get import status
             * Params:    @import - [required] The identifier of the import
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'imports';
            $this->service_code = 'P42';

            if (empty($import_id)) {
                return $this->errors(10, 'IMPORT_ID_MISSING');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/{$import_id}/?api_key=".$this->api_key;

            return $this->parse($this->get());
        }

        public function importsUpload($file = '')
        {
            /*******************************************************************************************
             * Descripton:    Import products to Operator Information System
             *******************************************************************************************/
            $params = array();

            $this->service_method = 'POST';
            $this->service_child = 'imports';
            $this->service_code = 'P41';

            if (empty($file)) {
                return $this->errors(10, 'SOURCE_FILE_MISSING');
            }


            $uploaddir = realpath(dirname($file)).DIRECTORY_SEPARATOR;
            $uploadfile = $uploaddir.basename($file);

            if (class_exists('CURLFile')) {
                $type = 'text/csv';
                $params['file'] = new CurlFile($uploadfile, $type, basename($file));
            } else {
                $type = ';type=text/csv';
                $params['file'] = '@'.$uploadfile.$type;
            }

            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/?api_key=".$this->api_key;

            return $this->parse($this->post('csv', $params));
        }

        public function errorReport($import_id = 0, $filename = '')
        {
            /*******************************************************************************************
             * Descripton:    Get errors report file for an import
             * Params:    @import - [required] The identifier of the import
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'imports';
            $this->service_code = 'P44';

            if (!$import_id) {
                return $this->errors(10, 'IMPORT_ID_MISSING');
            } elseif (empty($filename)) {
                return $this->errors(10, 'FILE_MISSING');
            }
            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/{$import_id}/errorReport/?api_key=".$this->api_key;

            return $this->parse($this->get($filename));
        }

        public function newProductReport($import_id = 0, $filename = '')
        {
            /*******************************************************************************************
             * Descripton:    Get products report file for an import
             * Params:    @import - [required] The identifier of the import
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'imports';
            $this->service_code = 'P45';

            if (!$import_id) {
                return $this->errors(10, 'IMPORT_ID_MISSING');
            } elseif (empty($filename)) {
                return $this->errors(10, 'FILE_MISSING');
            }
            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/{$import_id}/newProductReport/?api_key=".$this->api_key;

            return $this->parse($this->get($filename));
        }

        public function transformedFile($import_id = 0, $filename = '')
        {
            /*******************************************************************************************
             * Descripton:    Get transformed file for an import
             * Params:    @import - [required] The identifier of the import
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'imports';
            $this->service_code = 'P46';

            if (!$import_id) {
                return $this->errors(10, 'IMPORT_ID_MISSING');
            } elseif (empty($filename)) {
                return $this->errors(10, 'FILE_MISSING');
            }
            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/{$import_id}/transformedFile/?api_key=".$this->api_key;

            return $this->parse($this->get($filename));
        }

        public function transformationErrorReport($import_id = 0, $filename = '')
        {
            /*******************************************************************************************
             * Descripton:    Get transformation errors report file for an import
             * Params:    @import - [required] The identifier of the import
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'imports';
            $this->service_code = 'P47';

            if (!$import_id) {
                return $this->errors(10, 'IMPORT_ID_MISSING');
            } elseif (empty($filename)) {
                return $this->errors(10, 'FILE_MISSING');
            }
            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/{$import_id}/transformationErrorReport/?api_key=".$this->api_key;

            return $this->parse($this->get($filename));
        }

        public function attributes($filters = array())
        {
            /*******************************************************************************************
             * Descripton:    Get attributes configuration
             * Params:    @hierarchy - [optional] Code of the hierarchy. If not specified, all attributes are retrieved
             *
             * @max_level - [optional] Number of children hierarchy levels to retrieve.
             *                         If not specified, attributes of all child hierarchies are retrieved
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'attributes';
            $this->service_code = 'PM11';

            $params = '';
            if (isset($filters['hierarchy']) && !empty($filters['hierarchy'])) {
                $params .= "&hierarchy={$filters['hierarchy']}";
            }
            if (isset($filters['max_level']) && !empty($filters['max_level'])) {
                $params .= "&max_level={$filters['max_level']}";
            }

            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/?api_key=".$this->api_key.$params;

            return $this->parse($this->get());
        }

        public function attributesValuesList($values_list = '')
        {
            /*******************************************************************************************
             * Descripton:    Returns values for given values list
             * Params:    @values_list - [required] The value list's code
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'attributes';
            $this->service_code = 'PM31';

            if (!$values_list) {
                return $this->errors(10, 'VALUE_LIST_MISSING');
            }
            //ERTC - Added to fix 400 HTTP response, when values_list include "white space"
            $values_list = str_replace(" ", "%20", $values_list);
            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/values_list/{$values_list}/?api_key=".$this->api_key;

            return $this->parse($this->get());
        }
    }
}
