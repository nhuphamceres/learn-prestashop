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
 * Filename:                Mirakl/Orders.php
 * Descripton:                List orders
 * Mirakl Marketplace:        v3.14.0 (2014)
 * Version:                    v1.0
 * Requirements:
 *                            - PHP with XML,CURL, JSON support
 *                            - Mirakl/Base.php
 */
require_once dirname(__FILE__).'/mirakl.webservice.class.php';

if (!class_exists('MiraklApiOrders')) {
    class MiraklApiOrders extends MiraklWebservice
    {
        const STATUS_STAGING = 'STAGING';
        const STATUS_WAITING_ACCEPTANCE = 'WAITING_ACCEPTANCE';
        const STATUS_WAITING_DEBIT = 'WAITING_DEBIT';
        const STATUS_WAITING_DEBIT_PAYMENT = 'WAITING_DEBIT_PAYMENT';
        const STATUS_SHIPPING = 'SHIPPING';
        const STATUS_SHIPPED = 'SHIPPED';
        const STATUS_RECEIVED = 'RECEIVED';
        const STATUS_CLOSED = 'CLOSED';
        const STATUS_REFUSED = 'REFUSED';
        const STATUS_CANCELED = 'CANCELED';
        const STATUS_INCIDENT_OPEN = 'INCIDENT_OPEN';
        const STATUS_INCIDENT_CLOSED = 'INCIDENT_CLOSED';
        const STATUS_WAITING_REFUND = 'WAITING_REFUND';
        const STATUS_WAITING_REFUND_PAYMENT = 'WAITING_REFUND_PAYMENT';
        const STATUS_REFUNDED = 'REFUNDED';

        public function __construct($marketplace_params)
        {
            parent::__construct($marketplace_params);
            $this->service = "orders";
        }

        public function orders($filters = array())
        {
            /*******************************************************************************************
             * Descripton:    List orders
             *==========================================================================================
             * Params:    @order_state_codes - [optional] List of order state code.
             *
             * /api/orders/?api_key=xxx&order_state_codes=WAITING_ACCEPTANCE...
             *
             * @order_ids - [optional] list of IDs. Format: ID1,ID2,ID3
             * @start_date - [optional] creation date for filtering. Format : "yyyy-MM-dd'T'HH:mm:ss"
             * @end_date - [optional] creation date for filtering. Format : "yyyy-MM-dd'T'HH:mm:ss"
             * @start_update_date - [optional] last update date for filtering. Format : "yyyy-MM-dd'T'HH:mm:ss"
             * @end_update_date - [optional] last update date for filtering. Format : "yyyy-MM-dd'T'HH:mm:ss"
             * @paginate - [optional] Control the pagination usage. Default : true
             * @sort: dateCreated - (default) Sort by creation date, order identifier, shop name and then by index of the order line
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = null;
            $this->service_code = 'OR11';

            $params = '';
            if (isset($filters['order_ids'])) {
                $params .= "&order_ids=" . implode(',', $filters['order_ids']);
            }
            if (isset($filters['order_state_codes']) && !empty($filters['order_state_codes'])) {
                $params .= "&order_state_codes={$filters['order_state_codes']}";
            }
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $params .= "&start_date={$filters['start_date']}";
            }
            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $params .= "&end_date={$filters['end_date']}";
            }
            if (isset($filters['start_update_date']) && !empty($filters['start_update_date'])) {
                $params .= "&start_update_date={$filters['start_update_date']}";
            }
            if (isset($filters['end_update_date']) && !empty($filters['end_date'])) {
                $params .= "&end_update_date={$filters['end_update_date']}";
            }
            if (isset($filters['paginate']) && !empty($filters['paginate'])) {
                $params .= "&paginate={$filters['paginate']}";
            }
            if (isset($filters['sort']) && !empty($filters['sort'])) {
                $params .= "&sort=".urlencode(MiraklHelperQuery::params($this->service.'/'.$this->service_child, $filters['sort']));
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

            $this->service_url = $this->endpoint."{$this->service}/?api_key=".$this->api_key.$params;

            return $this->parse($this->get());
        }

        public function order($order_id = 0)
        {
            /*******************************************************************************************
             * Descripton: Get information about an order
             *==========================================================================================
             * Params:    @order - [required] The identifier of the order
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = null;
            $this->service_code = 'OR12';

            if (!$order_id) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/?api_key=".$this->api_key;

            return $this->parse($this->get());
        }

        public function accept($order_id = 0, $order_info = array())
        {
            /*******************************************************************************************
             * Descripton: Accept or refuse order lines of an order which are in "WAITING_ACCEPTANCE" state
             *==========================================================================================
             * Params:    @order - [required] The identifier of the order
             *
             * @order_lines - List of representations of order line for acceptance
             * @accepted - Boolean indicates if the order line is accepted or refused
             * @id -    Identifier of the order line
             *             {
             *              "order_lines": [{
             *                "accepted": Boolean,
             *                "id": String
             *              }]
             *             }
             *******************************************************************************************/
            $this->service_method = 'PUT';
            $this->service_child = 'accept';
            $this->service_code = 'OR21';

            if (!$order_id) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            } elseif (!isset($order_info['order_lines'][0])) {
                return $this->errors(10, 'ORDER_LINES_MISSING');
            } elseif (!isset($order_info['order_lines'][0]['accepted'])) {
                return $this->errors(10, 'ORDER_LINES_MISSING/accepted');
            } elseif (!isset($order_info['order_lines'][0]['id'])) {
                return $this->errors(10, 'ORDER_LINES_MISSING/id');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key;

            return $this->parse($this->put('json', $order_info));
        }

        public function tracking($order_id = 0, $tracking_info = array())
        {
            /*******************************************************************************************
             * Descripton: Update carrier tracking information of a given order
             *==========================================================================================
             * Params:    @order - [required] The identifier of the order
             *             {
             *              "carrier_code": String,
             *              "carrier_name": String,
             *              "carrier_url": String,
             *              "tracking_number": String
             *             }
             *******************************************************************************************/
            $this->service_method = 'PUT';
            $this->service_child = 'tracking';
            $this->service_code = 'OR23';

            if (!$order_id) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            } elseif (count($tracking_info) < 2) {
                return $this->errors(10, 'TRACKING_INFO_MISSING');
            } elseif (!isset($tracking_info['tracking_number'])) {
                return $this->errors(10, 'TRACKING_NUMBER_MISSING');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key;

            if ($this->error_debug) {
                printf('%s(#%d): service url: %s', basename(__FILE__), __LINE__, $this->service_url);
            }

            return $this->put('json', $tracking_info);
        }

        public function ship($order_id = 0)
        {
            /*******************************************************************************************
             * Descripton: Valid the shipment of the order which is in "SHIPPING" state
             *==========================================================================================
             * Params:    @order - [required] The identifier of the order
             *******************************************************************************************/
            $this->service_method = 'PUT';
            $this->service_child = 'ship';
            $this->service_code = 'OR24';

            if (!$order_id) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key;

            if ($this->error_debug) {
                printf('%s(#%d): service url: %s', basename(__FILE__), __LINE__, $this->service_url);
            }

            $synced_action_result = $this->put();
            // update `mp_status` if the order was synced successfully
            if (!isset($synced_action_result['error_code'])) {
                Db::getInstance()->update(
                    bqSQL(MiraklDBManager::TABLE_MKP_ORDERS),
                    array('mp_status' => (int)MiraklOrder::CHECKED),
                    'mp_order_id = ' . pSQL($order_id),
                    1
                );
            }

            return $synced_action_result;
        }

        public function refund($order_id = 0, $order_info = array())
        {
            /*******************************************************************************************
             * Descripton:    Demand a refund of order lines of an order
             *==========================================================================================
             * Params:    @order_lines - List of order line representations
             * @id [required] - The identifier of the order line that must be refunded
             * @reason_code [required]- The reason code of the refund
             *
             * @order_info - JSON
             *            {
             *              "order_lines": [{
             *                "id": String,
             *                "reason_code": String
             *              }]
             *             }
             *******************************************************************************************/
            $this->service_method = 'PUT';
            $this->service_child = 'refund';
            $this->service_code = 'OR26';

            if (!$order_id) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            } elseif (!isset($order_info['order_lines'][0])) {
                return $this->errors(10, 'ORDER_LINES_MISSING');
            } elseif (!isset($order_info['order_lines'][0]['id'])) {
                return $this->errors(10, 'ORDER_LINES_MISSING/id');
            } elseif (!isset($order_info['order_lines'][0]['reason_code'])) {
                return $this->errors(10, 'ORDER_LINES_MISSING/reason_code');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key;

            return $this->parse($this->put('json', $order_info));
        }

        public function messages($order_id, $filters = array())
        {
            /*******************************************************************************************
             * Descripton:    Demand a refund of order lines of an order
             *==========================================================================================
             * Params:    @archived - [optional] "ALL", "FALSE" (default) or "TRUE".
             *                        If TRUE (FALSE) returns only archived (not archived) messages of the messages received.
             *
             * @received - [optional] Boolean : "ALL" (default), "FALSE" or "TRUE".
             *                        If "TRUE" ("FALSE") returns only messages received by (sent to) shop.
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'messages';
            $this->service_code = 'OR41';

            if (!$order_id) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            }

            $params = '';
            if (isset($filters['archived']) && !empty($filters['archived'])) {
                $params .= "&archived=".urlencode(MiraklHelperQuery::params($this->service.'/'.$this->service_child, $filters['archived']));
            }
            if (isset($filters['received']) && !empty($filters['received'])) {
                $params .= "&received=".urlencode(MiraklHelperQuery::params($this->service.'/'.$this->service_child, $filters['received']));
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

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key.$params;

            return $this->parse($this->get());
        }

        public function messagesPost($order_id = 0, $data = array())
        {
            /*******************************************************************************************
             * Descripton: Post a new message on an order
             *==========================================================================================
             * Params:    @body - [required] Body of the message
             *
             * @customer_email - Email of the customer who sent the message Required only if the customer sent the message. Otherwise, do not define.
             * @customer_firstname - Firstname of the customer who sent the message Required only if the customer sent the message. Otherwise, do not define.
             * @customer_id - Id of the customer who sent the message Required only if the customer sent the message. Otherwise, do not define.
             * @customer_lastname - Lastname of the customer who sent the message Required only if the customer sent the message. Otherwise, do not define.
             * @subject - [required] Subject of the message.
             * @to_customer - [required] Boolean indicates the message sent to the customer associated with the order.
             * @to_operator - [required] Boolean indicates the message sent to the operator.
             * @to_shop - [required] Boolean indicates the message sent to the shop associated with the order
             *******************************************************************************************/
            $this->service_method = 'POST';
            $this->service_child = 'messages';
            $this->service_code = 'OR42';

            if (!$order_id) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            } elseif (!isset($data['body']) || empty($data['body'])) {
                return $this->errors(10, 'MESSAGES/body');
            } elseif (!isset($data['subject']) || empty($data['subject'])) {
                return $this->errors(10, 'MESSAGES/subject');
            } elseif (!isset($data['to_customer']) || empty($data['to_customer'])) {
                return $this->errors(10, 'MESSAGES/to_customer');
            } elseif (!isset($data['to_operator']) || empty($data['to_operator'])) {
                return $this->errors(10, 'MESSAGES/to_operator');
            } elseif (!isset($data['to_shop']) || empty($data['to_shop'])) {
                return $this->errors(10, 'MESSAGES/to_shop');
            }

            $params = array();
            if (isset($data['body']) && !empty($data['body'])) {
                $params['body'] = $data['body'];
            }
            if (isset($data['customer_email']) && !empty($data['customer_email'])) {
                $params['customer_email'] = $data['customer_email'];
            }
            if (isset($data['customer_id']) && !empty($data['customer_id'])) {
                $params['customer_id'] = $data['customer_id'];
            }
            if (isset($data['customer_lastname']) && !empty($data['customer_lastname'])) {
                $params['customer_lastname'] = $data['customer_lastname'];
            }
            if (isset($data['subject']) && !empty($data['subject'])) {
                $params['subject'] = $data['subject'];
            }
            if (isset($data['to_customer']) && !empty($data['to_customer'])) {
                $params['to_customer'] = $data['to_customer'];
            }
            if (isset($data['to_operator']) && !empty($data['to_operator'])) {
                $params['to_operator'] = $data['to_operator'];
            }
            if (isset($data['to_shop']) && !empty($data['to_shop'])) {
                $params['to_shop'] = $data['to_shop'];
            }

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key;

            return $this->parse($this->post('json', $params));
        }

        public function evaluation($order_id = 0)
        {
            /*******************************************************************************************
             * Descripton: Get the evaluation of an order
             *==========================================================================================
             * Params:    @order - [required] The identifier of the order
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'evaluation';
            $this->service_code = 'OR51';

            if (!$order_id) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key;

            return $this->parse($this->get());
        }

        public function documents($order_ids = array())
        {
            /*******************************************************************************************
             * Descripton: List order's documents
             *==========================================================================================
             * Params:    @order_ids - [required] the orders' identifiers, using comma as separator
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = 'documents';
            $this->service_code = 'OR72';

            if (count($order_ids) < 1) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            }

            $params = "&order_ids=".urlencode(implode(',', $order_ids));

            $order_id = 'xxx';//TODO:read the API documentation and fix the issue.

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key.$params;

            return $this->parse($this->get());
        }

        public function documentsDownload($filename = '', $order_ids = array(), $document_ids = array(), $document_codes = array())
        {
            /*******************************************************************************************
             * Descripton: Download one or multiple documents associated to one or multiple orders
             *==========================================================================================
             * Params:    @order_ids - A list of identifiers of the orders (separated by a comma)
             *
             * @document_ids - A list of document identifiers (separated by a comma)
             * @document_codes - [optional] A list of document types (separated by a comma)
             *******************************************************************************************/
            $this->service_child = 'documents/download';
            $this->service_method = 'GET';
            $this->service_code = 'OR73';

            if (empty($filename)) {
                return $this->errors(10, 'FILE_MISSING');
            } elseif (count($order_ids) < 1) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            } elseif (count($document_ids) < 1) {
                return $this->errors(10, 'DOCUMENT_ID_MISSING');
            }
            $params = "&order_ids=".implode(',', $order_ids);
            $params .= "&document_ids=".implode(',', $document_ids);
            if (count($document_codes) > 0) {
                $params .= "&document_codes=".implode(',', $document_codes);
            }

            $order_id = 'xxx';//TODO:read the API documentation and fix the issue.

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key.$params;

            return $this->parse($this->get($filename));
        }

        public function documentsUpload($order_id = 0, $filename = '', $documents_info = array())
        {
            /*******************************************************************************************
             * Descripton: Download one or multiple documents associated to one or multiple orders
             *==========================================================================================
             * Params:    @files - [required] The files to be uploaded. Use multipart/form-data with name 'files'
             *
             * @order_documents - [required] The list of documents to be uploaded details
             *                -{
             *                  "order_document": [{
             *                    "file_name": String,
             *                    "type_code": String
             *                  }]
             *                 }
             *******************************************************************************************/
            $this->service_method = 'POST';
            $this->service_child = 'documents';
            $this->service_code = 'OR74';

            if (!$order_id) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            } elseif (!$filename || !file_exists($filename)) {
                return $this->errors(10, 'SOURCE_FILE_MISSING');
            } elseif (!isset($documents_info['order_document'][0]['file_name']) && empty($documents_info['order_document'][0]['file_name'])) {
                return $this->errors(10, 'ORDER_DOCUMENT_MISSING');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key;
            $params = array();
            $params['files'] = '@'.$filename;
            $params['order_documents'] = Tools::jsonEncode($documents_info);

            return $this->parse($this->post('csv', $params));
        }

        public function additionalFields($order_id = 0, $fields = array())
        {
            $this->service_method = 'PUT';
            $this->service_child = 'additional_fields';
            $this->service_code = 'OR31';

            if (!$order_id) {
                return $this->errors(10, 'ORDER_ID_MISSING');
            } elseif (!is_array($fields) || !count($fields)) {
                return $this->errors(10, 'FIELDS_MISSING');
            }

            $this->service_url = $this->endpoint."{$this->service}/{$order_id}/{$this->service_child}/?api_key=".$this->api_key;
            $params = array('order_additional_fields' => $fields);

            return $this->parse($this->put('json', $params));
        }
    }
}
