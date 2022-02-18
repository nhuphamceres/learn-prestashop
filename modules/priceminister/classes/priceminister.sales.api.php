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

class PM_Sales extends PriceministerWebservices
{

    const _VER_GETCURRENTSALES_ = '2017-08-07';
    const _VER_GETNEWSALES_ = '2017-08-07';
    const _VER_ACCEPTSALE_ = '2010-09-20';
    const _VER_REFUSESALE_ = '2010-09-20';
    const _VER_GETITEMTODOLIST_ = '2011-02-02';
    const _VER_GETITEMINFOS_ = '2017-08-07';
    const _VER_CANCELITEM_ = '2011-02-02';
    const _VER_CONTACTPRICEMINISTERABOUTITEM_ = '2011-02-02';
    const _VER_CONTACTUSERABOUTITEM_ = '2011-02-02';
    const _VER_GETCOMPENSATIONDETAILS_ = '2011-03-29';
    const _VER_GETBILLINGINFORMATION_ = '2011-03-29';
    const _VER_GETSHIPPINGINFORMATION_ = '2014-02-11';
    const _VER_SETTRACKINGPACKAGEINFOS_ = '2012-11-06';
    const _VER_IMPORTITEMSHIPPINGSTATUS_ = '2016-05-09';

    public function __construct($config = array())
    {
        $this->config = $config;
    }

    public function settrackingpackageinfos($params = array(), $array = true)
    {
        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=settrackingpackageinfos&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&channel=common-services&version='.self::_VER_SETTRACKINGPACKAGEINFOS_;

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function importitemshippingstatus($params = array(), $array = true)
    {
        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=importitemshippingstatus&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&channel=common-services&version='.self::_VER_IMPORTITEMSHIPPINGSTATUS_;

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function getcurrentsales($params = array(), $array = true)
    {
        if (!isset($params['field'])) {
            $params['field'] = '';
        }

        $opts = isset($params['purchasedate']) ? '&purchasedate='.$params['purchasedate'] : '';
        $nexttoken = isset($params['nexttoken']) ? '&nexttoken='.$params['nexttoken'] : '';

        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=getcurrentsales&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&channel=common-services&version='.self::_VER_GETCURRENTSALES_.$opts.$nexttoken;//.'&notshippeditemsonly=true';

        unset($params['purchasedate']);
        unset($params['nexttoken']);

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function getnewsales($params = array(), $array = true)
    {
        if (!isset($params['field'])) {
            $params['field'] = '';
        }

        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=getnewsales&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&channel=common-services&version='.self::_VER_GETNEWSALES_;

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function acceptsale($params = array(), $array = true)
    {
        $query = array(
            'action' => 'acceptsale',
            'login' => $this->config['login'],
            'pwd' => $this->config['pwd'],
            'version' => self::_VER_ACCEPTSALE_,
            'itemid' => isset($params['itemid']) ? $params['itemid'] : '',
            'shippingfromcountry' => isset($params['shippingfromcountry']) ? $params['shippingfromcountry'] : '',
        );

        //Service URL
        $url = $this->config['https_url'] . 'sales_ws?' . http_build_query($query);

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function refusesale($params = array(), $array = true)
    {
        if (!isset($params['itemid'])) {
            $params['itemid'] = '';
        }

        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=refusesale&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_REFUSESALE_.'&itemid='.$params['itemid']; //&itemid=104918937

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function getitemtodolist($params = array(), $array = true)
    {
        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=getitemtodolist&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_GETITEMTODOLIST_;

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function getiteminfos($params = array(), $array = true)
    {
        if (!isset($params['itemid'])) {
            $params['itemid'] = '';
        }

        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=getiteminfos&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_GETITEMINFOS_.'&itemid='.$params['itemid'];

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function cancelitem($params = array(), $array = true)
    {
        if (!isset($params['itemid'])) {
            $params['itemid'] = '';
        }
        if (!isset($params['comment'])) {
            $params['comment'] = '';
        }

        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=cancelitem&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_CANCELITEM_.'&itemid='.$params['itemid'];

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function contactpriceministeraboutitem($params = array(), $array = true)
    {
        if (!isset($params['itemid'])) {
            $params['itemid'] = '';
        }
        if (!isset($params['comment'])) {
            $params['comment'] = '';
        }
        if (!isset($params['mailparentid'])) {
            $params['mailparentid'] = '';
        }

        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=contactpriceministeraboutitem&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_CONTACTPRICEMINISTERABOUTITEM_.'&itemid='.$params['itemid'].'&itemid='.$params['itemid'].'&itemid='.$params['itemid'];

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function contactuseraboutitem($params = array(), $array = true)
    {
        if (!isset($params['itemid'])) {
            $params['itemid'] = '';
        }
        if (!isset($params['content'])) {
            $params['content'] = '';
        }

        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=contactuseraboutitem&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_CONTACTUSERABOUTITEM_.'&itemid='.$params['itemid'].'&content='.$params['content'];

        unset($params['content']);
        unset($params['itemid']);

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function getcompensationdetails($params = array(), $array = true)
    {
        if (!isset($params['compensationid'])) {
            $params['compensationid'] = '';
        }

        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=getcompensationdetails&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_GETCOMPENSATIONDETAILS_.'&compensationid='.$params['compensationid'];

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function getbillinginformation($params = array(), $array = true)
    {
        if (!isset($params['purchaseid'])) {
            $params['purchaseid'] = '';
        }

        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=getbillinginformation&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_GETBILLINGINFORMATION_.'&purchaseid='.$params['purchaseid'];

        //Request Response
        return $this->make_request($url, $params, $array);
    }

    public function getshippinginformation($purchaseid, $array = true)
    {
        //Service URL
        $url = $this->config['https_url'].'sales_ws?action=getshippinginformation&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_GETCOMPENSATIONDETAILS_.'&purchaseid='.$purchaseid;

        //Request Response
        return $this->make_request($url, array('field' => ''), $array);
    }
}
