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

/*
 * Filename:                Mirakl/Offers.php
 * Descripton:                List messages for orders and offers
 * Mirakl Marketplace:        v3.14.0 (2014)
 * Version:                    v1.0
 * Author:                    Abdul Mohymen (info@kernelbd.com)
 * Copyright:                (c) Copyright 2014 Kernel BD Corporation. All Rights Reserved.
 * Requirements:
 *                            - PHP with XML,CURL, JSON support
 *                            - Mirakl/Base.php
 */
require_once dirname(__FILE__).'/mirakl.webservice.class.php';

if (!class_exists('MiraklApiOffers')) {
    class MiraklApiOffers extends MiraklWebservice
    {
        public function __construct($marketplace_params)
        {
            parent::__construct($marketplace_params);
            $this->service = "offers";
        }

        // end func

        public function imports($params = array())
        {
            /*******************************************************************************************
             * Descripton:    List messages for orders and offers
             *==========================================================================================
             * Params:    @file - [required] Import file to upload. Use multipart/form-data with name 'file'.
             *
             * @import_mode - [required] [NORMAL, PARTIAL_UPDATE, REPLACE]
             * @with_products - [optional] This file also contains product information. Default : false
             *******************************************************************************************/
            $this->service_method = 'POST';
            $this->service_child = 'imports';
            $this->service_code = 'OF01';

            if (!$params['file'] || !file_exists($params['file'])) {
                return $this->errors(10, 'SOURCE_FILE_MISSING');
            }

            if (!isset($params['import_mode'])) {
                $params['import_mode'] = '';
            }

            $upload_params = array();
            $file = $params['file'];

            $uploaddir = realpath(dirname($file)).DIRECTORY_SEPARATOR;
            $uploadfile = $uploaddir.basename($file);

            if (class_exists('CURLFile')) {
                $type = 'text/csv';
                $upload_params['file'] = new CurlFile($uploadfile, $type, basename($file));
            } else {
                $type = ';type=text/csv';
                $upload_params['file'] = '@'.$uploadfile.$type;
            }


            $upload_params['import_mode'] = urlencode(MiraklHelperQuery::params($this->service.'/imports', 'import_mode', $params['import_mode']));
            if (isset($params['with_products']) && !empty($params['with_products'])) {
                $upload_params['with_products'] = $params['with_products'];
            }

            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/?api_key=".$this->api_key;

            return $this->parse($this->post('csv', $upload_params));
        }

        public function importsInfo($import_id = 0)
        {
            /*******************************************************************************************
             * Descripton:    Get import information and stats
             *==========================================================================================
             * Params:    @import - [required] The identifier of the import
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'imports';
            $this->service_code = 'OF02';

            if (!$import_id) {
                return $this->errors(10, 'IMPORT_ID_MISSING');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/{$import_id}?api_key=".$this->api_key;

            return $this->parse($this->get());
        }


        public function errorReport($import_id = 0, $filename = '')
        {
            /*******************************************************************************************
             * Descripton:    Get error report file for an import
             *==========================================================================================
             * Params:    @import - [required] The identifier of the import
             *            @$filename - destination file to save error report
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'imports';
            $this->service_code = 'OF03';

            if (!$import_id) {
                return $this->errors(10, 'IMPORT_ID_MISSING');
            }

            if (empty($filename)) {
                return $this->errors(10, 'FILE_MISSING');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/{$import_id}/errorReport/?api_key=".$this->api_key;

            return $this->parse($this->get($filename));
        }

        public function offers($filters = array())
        {
            /*******************************************************************************************
             * Descripton:    List messages for orders and offers
             *==========================================================================================
             * Params:    @offer_state_codes - [optional] List of offer state codes.
             *
             * @sku - [optional] Offer's sku.
             * @product_id - [optional] Product's sku.
             * @favorite - [optional] filter only the favorite offers. (default false)
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = null;
            $this->service_code = 'OF21';

            $params = '';
            if (isset($filters['offer_state_codes']) && !empty($filters['offer_state_codes'])) {
                $params .= "&offer_state_codes={$filters['offer_state_codes']}";
            }
            if (isset($filters['sku']) && !empty($filters['sku'])) {
                $params .= "&sku={$filters['sku']}";
            }
            if (isset($filters['product_id']) && !empty($filters['product_id'])) {
                $params .= "&product_id={$filters['product_id']}";
            }
            if (isset($filters['favorite']) && !empty($filters['favorite'])) {
                $params .= "&favorite={$filters['favorite']}";
            }

            $this->service_url = $this->endpoint.$this->service."/?api_key=".$this->api_key.$params;

            return $this->parse($this->get());
        }


        public function offer($offer_id = 0)
        {
            /*******************************************************************************************
             * Descripton:    Get information of an offer
             *==========================================================================================
             * Params:    @offer - [required] The identifier of the offer
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = null;
            $this->service_code = 'OF22';

            if (!$offer_id) {
                return $this->errors(10, 'OFFER_ID_MISSING');
            }

            $this->service_url = $this->endpoint.$this->service."/{$offer_id}/?api_key=".$this->api_key;

            return $this->parse($this->get());
        }

        public function offerPost($params = array())
        {
            /*******************************************************************************************
             * Descripton:    Update offers
             *==========================================================================================
             * Params:     {
             * "offers": [{
             * "available_ended": Date,
             * "available_started": Date,
             * "description": String,
             * "discount": {
             * "end_date": Date,
             * "price": Number,
             * "start_date": Date
             * },
             * "internal_description": String,
             * "logistic_class": String,
             * "min_quantity_alert": Number,
             * "offer_additional_fields": [{
             * "code": String,
             * "value": String
             * }],
             * "price": Number,
             * "price_additional_info": String,
             * "product_id": String,
             * "product_id_type": String,
             * "quantity": Number,
             * "shop_sku": String,
             * "state_code": String,
             * "update_delete": String
             * }]
             * }
             *******************************************************************************************/
            $this->service_method = 'POST';
            $this->service_child = null;
            $this->service_code = 'OF24';

            $this->service_url = $this->endpoint."{$this->service}/?api_key=".$this->api_key;

            return $this->parse($this->post('json', $params));
        }

        public function messages($offer_id = 0, $filters = array())
        {
            /*******************************************************************************************
             * Descripton:    List messages of an offer
             *==========================================================================================
             * Params:    @customer_id - [optional] customer identifier.
             *
             * @archived - [optional] "ALL", "FALSE" (default) or "TRUE". If TRUE (FALSE) returns only archived (not archived) messages of the messages received.
             * @received - [optional] "ALL" (default), "FALSE" or "TRUE". If TRUE (FALSE) returns only messages received by (sent to) shop.
             * @visible - [optional] "ALL", "TRUE" (default) or "FALSE". If "TRUE" ("FALSE") returns only the visible (not visible) messages.
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = "messages";
            $this->service_code = 'OF41';

            if (!$offer_id) {
                return $this->errors(10, 'OFFER_ID_MISSING');
            }

            $params = '';
            if (isset($filters['customer_id']) && !empty($filters['customer_id'])) {
                $params .= "&customer_id={$filters['customer_id']}";
            }
            if (isset($filters['archived']) && !empty($filters['archived'])) {
                $params .= "&archived=".urlencode(MiraklHelperQuery::params($this->service.'/messages', 'archived', $filters['archived']));
            }
            if (isset($filters['received']) && !empty($filters['received'])) {
                $params .= "&received=".urlencode(MiraklHelperQuery::params($this->service.'/messages', 'received', $filters['received']));
            }
            if (isset($filters['visible']) && !empty($filters['visible'])) {
                $params .= "&visible=".urlencode(MiraklHelperQuery::params($this->service.'/messages', 'visible', $filters['visible']));
            }
            if (isset($filters['sort']) && !empty($filters['sort'])) {
                $params .= "&sort=".(MiraklHelperQuery::params($this->service, $filters['sort']));
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


            $this->service_url = $this->endpoint."{$this->service}/{$offer_id}/{$this->service_child}/?api_key=".$this->api_key.$params;

            return $this->parse($this->get());
        }

        public function messagePost($offer_id = 0, $message_id = 0, $params = array())
        {
            /*******************************************************************************************
             * Descripton:    List messages of an offer
             *==========================================================================================
             * Params:    @offer_id - [required] The identifier of the offer
             *
             * @message_id - [required] The identifier of the message
             * @body        - [required] Body of the message
             * @visible    - [optional] boolean - Publicity of the message
             *             {
             * "body": String,
             * "visible": Boolean
             * }
             *******************************************************************************************/
            $this->service_method = 'POST';
            $this->service_child = "messages";
            $this->service_code = 'OF43';

            if (empty($offer_id)) {
                return $this->errors(10, 'OFFER_ID_MISSING');
            }

            if (!$message_id) {
                return $this->errors(10, 'MESSAGE_ID_MISSING');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$offer_id}/{$this->service_child}/{$message_id}/?api_key=".$this->api_key;

            return $this->parse($this->post('json', $params));
        }

        public function states()
        {
            /*******************************************************************************************
             * Descripton:    Get the list of offer states
             *                List of offer states representations without pagination
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = "states";
            $this->service_code = 'OF61';
            $this->service_url = $this->endpoint."{$this->service}/{$this->service_child}/?api_key=".$this->api_key;

            return $this->parse($this->get());
        }
    }
}
