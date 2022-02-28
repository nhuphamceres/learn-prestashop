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

if (!class_exists('MiraklSupport')) {
    class MiraklSupport extends Mirakl
    {
        const MIRAKL_DOCUMENTATION_URL = 'http://documentation.common-services.com/mirakl';

        /* Export Products and offers */
        const FUNCTION_EXPORT_EMPTY_REFERENCE = 804;
        const FUNCTION_EXPORT_DUPLICATE = 812;
        const FUNCTION_EXPORT_WRONG_EAN = 820;
        const FUNCTION_EXPORT_DUPLICATE_EAN = 828;
        const FUNCTION_EXPORT_MISSING_EAN = 841;
        const FUNCTION_EXPORT_MISSING_EAN_MATCHING = null;


        /* Tutorials */
        const TUTORIAL_API_KEYPAIRS = 615;
        const TUTORIAL_PROFILES = 644;
        const TUTORIAL_CATEGORIES = 791;
        const TUTORIAL_TRANSPORT = 704;
        const TUTORIAL_ORDERS = 693;
        const TUTORIAL_PARAMETERS = 744;
        const TUTORIAL_CRON = 96;
        const TUTORIAL_FILTERS = 1166;

        public $displayName = 'Mirakl';

        public function gethreflink($id = null)
        {
            $url = $this->getUrl($id);
            $this->context->smarty->assign(array(
                'url'           => $url,
                'title'         => $this->l('Mirakl for Prestashop Online Documentation', basename(__FILE__, '.php')),
                'url_display'   => $url
            ));

            $link = $this->context->smarty->fetch($this->path.'views/templates/admin/support/support_link.tpl');

            return ($link);
        }

        public function message($msg, $id = null)
        {
            if ($id) {
                $url = $this->getUrl($id);

                $this->context->smarty->assign(array(
                    'msg'           => $msg,
                    'support_found' => $this->l('An online support has been found on this topic', basename(__FILE__, '.php')),
                    'click'         => $this->l('Click on this link to obtain support', basename(__FILE__, '.php')),
                    'url'           => $url,
                    'title'         => $this->l('Mirakl for Prestashop Online Documentation', basename(__FILE__, '.php')),
                    'url_display'   => $url
                ));

                $help_msg = $this->context->smarty->fetch($this->path.'views/templates/admin/support/document_found.tpl');

                return ($help_msg);
            }

            return ($msg);
        }

        /**
         * Get support URL
         * @param $id
         * @return string
         */
        protected function getUrl($id)
        {
            $lang = Language::getIsoById($this->id_lang);
            $url = sprintf('%s?p=%s&lang=%s&module=%s', self::MIRAKL_DOCUMENTATION_URL, $id, $lang, urlencode($this->displayName));

            return $url;
        }
    }
}
