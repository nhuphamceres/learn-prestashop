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
 * Filename:                Mirakl/Account.php
 * Descripton:                Get shop information
 * Mirakl Marketplace:        v3.14.0 (2014)
 * Version:                    v1.0
 * Author:                    Abdul Mohymen (info@kernelbd.com)
 * Copyright:                (c) Copyright 2014 Kernel BD Corporation. All Rights Reserved.
 * Requirements:
 *                            - PHP with XML,CURL, JSON support
 *                            - Mirakl/Base.php
 */

require_once dirname(__FILE__).'/mirakl.webservice.class.php';

if (!class_exists('MiraklApiAccount')) {
    class MiraklApiAccount extends MiraklWebservice
    {
        public function __construct($marketplace_params)
        {
            parent::__construct($marketplace_params);
            $this->service = "account";
        }

        // end func

        public function account()
        {
            /*******************************************************************************************
             * Descripton: Get shop information
             *******************************************************************************************/
            $this->service_method = 'GET';
            $this->service_child = null;
            $this->service_code = 'A01';
            $this->service_url = $this->endpoint.$this->service.'/?api_key='.$this->api_key;

            return $this->get(null, 'xml');
        }
        // end func
    }
}
