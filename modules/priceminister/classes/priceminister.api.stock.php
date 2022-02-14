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

class PriceMinisterApiStock extends PriceministerWebservices
{

    const _VER_IMPORT_ = '2010-09-20';
    const _VER_IMPORTREPORT_ = '2011-06-21';
    const _VER_EXPORT_ = '2011-02-17';

    public function __construct($config = array())
    {
        $this->config = $config;
    }

    public function import($params = array())
    {
        //Service URL
        $url = $this->config['https_url'].'stock_ws?action=import&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&profileid='.$this->config['profileid'].'&version='.self::_VER_IMPORT_;
        //.'&mappingalias=categorie;identifiant_unique;titre;prix;URL_produit;URL_image;description;reference_modele;livraison;shipping_delay;D3E;disponibilite;marque;ean;garantie;prix_barre;Rapport';

        //Request Response
        return $this->make_request($url, $params);
    }

    public function synch($params = array(), $kindof = 'PRICEQUANTITY')
    {
        //Service URL
        $url = $this->config['https_url'].'stock_ws?action=import&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&mappingalias='.$kindof.'&version='.self::_VER_IMPORT_;

        //Request Response
        return $this->make_request($url, $params);
    }

    public function importreport($params = array())
    {
        if (!isset($params['fileid'])) {
            $params['fileid'] = '';
        }

        //Service URL
        $url = $this->config['https_url'].'stock_ws?action=importreport&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_IMPORTREPORT_.'&fileid='.$params['fileid'];

        //Request Response
        return $this->make_request($url, $params);
    }

    public function export($params = array())
    {
        if (!isset($params['nexttoken'])) {
            $params['nexttoken'] = '';
        }
        if (!isset($params['scope'])) {
            $params['scope'] = '';
        }

        //Service URL
        $url = $this->config['https_url'].'stock_ws?action=export&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&report_type=inventory'.'&version='.self::_VER_EXPORT_.'&nexttoken='.$params['nexttoken'].'&scope='.$params['scope'];

        //Request Response
        return $this->make_request($url, $params);
    }
}