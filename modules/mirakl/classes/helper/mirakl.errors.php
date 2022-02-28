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
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!class_exists('MiraklHelperErrors')) {
    class MiraklHelperErrors
    {
        public static function explain($info = array())
        {
            $error_key = str_replace('/', '_', $info['error']);
            $valid_http_codes = array('200', '201', '204');
            $invalid_http_codes = array('400', '404', '415', '500');
            if (isset($info['error_code']) && $info['error_code'] == 10) {
                $info['serverity_level'] = 1;
                $info['error_end'] = "Client Side";
            } else {
                if (in_array($info['error_code'], $invalid_http_codes)) {
                    $err = Tools::jsonDecode($info['error'], true);
                    $info['error'] = 'SERVER_RESPONSE_WITH_ERROR';
                }
                $info['serverity_level'] = 3;
                $info['error_end'] = "Server Side";
            }

            $reasons = array();
            $reasons['SOURCE_FILE_MISSING'] = 'File Missing! You have tried to upload file which is not exist or wrong file path for Service: '.$info['service'];
            $reasons['IMPORT_ID_MISSING'] = 'Import ID Missing. Faile to process your request for Service: '.$info['service'];
            $reasons['FILE_MISSING'] = 'File Name Missing! No destination file name is provided to save data for Service: '.$info['service'];
            $reasons['OFFER_ID_MISSING'] = 'Mirakl Offer ID Missing for Service: '.$info['service'];
            $reasons['ORDER_ID_MISSING'] = 'Mirakl Order ID Missing for Service: '.$info['service'];
            $reasons['ORDER_LINES_MISSING'] = 'Order Line is missing and/or its child elements are missing for Service: '.$info['service'];
            $reasons['ORDER_LINES_MISSING_accepted'] = 'Order Line (/accepted) child elements are missing for Service: '.$info['service'];
            $reasons['ORDER_LINES_MISSING_id'] = 'Order Line (id) child elements are missing for Service: '.$info['service'];
            $reasons['ORDER_LINES_MISSING_reason_code'] = 'Order Line (/reason_code) child elements are missing for Service: '.$info['service'];
            $reasons['ORDER_DOCUMENT_MISSING'] = 'The file_name is missing while processing Order documents for Service: '.$info['service'];
            $reasons['DOCUMENT_ID_MISSING'] = 'Import ID Missing. Failed to process your request for Service: '.$info['service'];
            $reasons['TRACKING_INFO_MISSING'] = 'Tracking Information is missing for Service: '.$info['service'];
            $reasons['TRACKING_NUMBER_MISSING'] = 'Tracking Number is missing for Service: '.$info['service'];
            $reasons['MESSAGES_body'] = 'The body part is missing for Service: '.$info['service'];
            $reasons['MESSAGES_subject'] = 'The subject part is missing for Service: '.$info['service'];
            $reasons['MESSAGES_to_customer'] = 'The to_customer part is missing for Service: '.$info['service'];
            $reasons['MESSAGES_to_operator'] = 'The to_operator part is missing for Service: '.$info['service'];
            $reasons['MESSAGES_to_shop'] = 'The to_shop part is missing for Service: '.$info['service'];
            $reasons['VALUE_LIST_MISSING'] = 'The value_list part is missing for Messages for Service: '.$info['service'];
            $reasons['PRODUCT_ID_MISSING'] = 'Product Type is Missing. Failed to process your request for Service: '.$info['service'];
            $reasons['SERVER_RESPONSE_WTIH_ERROR'] = 'Server returns Error for Service: '.$info['service'];
            $reasons['OFFER_STATECODE_MISSING'] = 'Offer State Code is missing for Service: '.$info['service'];
            $reasons['REQUEST_SUCCESS'] = 'Successfully request completed for Service: '.$info['service'];
            $reasons['MESSAGE_ID_MISSING'] = 'Message ID Missing. Failed to process your request for Service: '.$info['service'];
            if (in_array($error_key, $reasons)) {
                $info['error_details'] = $reasons["{$error_key}"];
            } else {
                $info['error_details'] = (isset($err['message']) && Tools::strlen($err['message']) > 1) ? $err['message'] : "Error encountered, please check Service_Code to track down the problem.";
            }

            if (in_array($info['error_code'], $valid_http_codes)) {
                $info['http_code'] = $info['error_code'];
                $info['response'] = $info['error'];
                $info['message'] = $reasons["{$info['error']}"];

                unset($info['error']);
                unset($info['error_code']);
                unset($info['error_end']);
                unset($info['error_details']);
                unset($info['serverity_level']);
            }

            return $info;
        }
    }
}
