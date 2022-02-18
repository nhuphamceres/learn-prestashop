<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

class PriceMinisterApiProducts extends PriceministerWebservices
{

    const _VER_LISTING_ = '2012-09-11';
    const _VER_PRODUCT_TYPES_ = '2011-11-29';
    const _VER_PRODUCT_GENERIC_REPORT = '2011-11-29';
    const _VER_PRODUCT_TYPE_TEMPLATES = '2017-10-04';
    const _VER_PRODUCT_GENERIC_IMPORT = '2012-09-11';
    const _VER_PRODUCT_ADVERT_EXPORT = '2018-06-29';
    const _PRODUCTTYPE_FILE_ = 'producttype';

    public function __construct($config = array())
    {
        $this->config = $config;
    }

    public function listing($params)
    {
        $url = $this->config['http_url'].'listing_ws?action=listing&version='.self::_VER_LISTING_.'&'.$params['scope'].'&nbproducts='.$params['nbproducts'].'&kw='.$params['kw'].'&nav='.$params['nav'].'&refs='.$params['refs'].'&productids='.$params['productids'];

        return $this->cache('listing_ws', $url, $params, true);
    }

    public function getProductTypes($params)
    {
        $url = $this->config['https_url'].'stock_ws?action=producttypes&version='.
            self::_VER_PRODUCT_TYPES_.'&login='.$this->config['login'].'&pwd='.$this->config['pwd'];

        return $this->cache(self::_PRODUCTTYPE_FILE_, $url, $params);
    }

    public function getProductModel($params)
    {
        $url = $this->config['https_url'].'stock_ws?action=producttypetemplate&version='.
            self::_VER_PRODUCT_TYPE_TEMPLATES.'&login='.$this->config['login'].'&pwd='.$this->config['pwd'].
            '&alias='.$params['alias'].(isset($params['scope']) ? '&scope='.$params['scope'] : '');

        return $this->cache($params['alias'], $url, $params, false, true);
    }

    public function getProductAdvert()
    {
        // Repricing
        $url = $this->config['https_url'].'stock_ws?action=export&version='.
            self::_VER_PRODUCT_ADVERT_EXPORT.'&login='.$this->config['login'].'&pwd='.$this->config['pwd'].
            '&scope=PRICING';

        return $this->make_request($url, null, false);
    }

    public function genericImportReport($params)
    {
        $url = $this->config['https_url'].'stock_ws?action=genericimportreport&version='.
            self::_VER_PRODUCT_GENERIC_REPORT.'&login='.$this->config['login'].'&pwd='.$this->config['pwd'].
            (isset($params['fileid']) ? '&fileid='.$params['fileid'] : '').(isset($params['nexttoken']) && (int)$params['nexttoken'] ? '&nexttoken='.$params['nexttoken'] : '');

        return $this->make_request($url, $params, false);
    }

    public function importProductsXML($params)
    {
        $url = $this->config['https_url'].'stock_ws?action=genericimportfile&version='.
            self::_VER_PRODUCT_GENERIC_IMPORT.'&login='.$this->config['login'].'&pwd='.$this->config['pwd'].
            (isset($params['alias']) ? '&alias='.$params['alias'] : '').(isset($params['scope']) ? '&scope='.$params['scope'] : '');

        return $this->make_request($url, $params);
    }
}
