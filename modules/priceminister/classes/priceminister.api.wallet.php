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

class PM_Wallet extends PriceministerWebservices
{

    const _VER_GETOPERATIONS_ = '2011-03-29';

    public function __construct($config = array())
    {
        $this->config = $config;
    }

    public function getoperations($params)
    {
        if (!isset($params['operationcause'])) {
            $params['operationcause'] = 'salestransfer';
        }
        if (!isset($params['lastoperationdate'])) {
            $params['lastoperationdate'] = '';
        }

        $url = $this->config['https_url'].'wallet_ws?action=getoperations&login='.$this->config['login'].'&pwd='.$this->config['pwd'].'&version='.self::_VER_GETOPERATIONS_.'&operationcause='.$params['operationcause'].'&lastoperationdate='.$params['lastoperationdate'];

        return $this->make_request($url, $params);
    }
}