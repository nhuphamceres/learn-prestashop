<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Feed.biz
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Feed.biz is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Feed.biz.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class AmazonAddressState
{
    protected $countryId;
    protected $postcode;
    protected $isoCode;

    public function __construct($countryId, $postcode, $isoCode = '')
    {
        $this->countryId = $countryId;
        $this->postcode = trim($postcode);
        $this->isoCode = trim($isoCode);
    }

    /**
     * @return int
     */
    public function resolveStateId()
    {
        return $this->lookupStateIdByPostcode() ?: $this->lookupStateIdByIsoCode();
    }

    /**
     * @return int
     */
    private function lookupStateIdByPostcode()
    {
        $length = Tools::strlen($this->postcode);
        if ($length < 2) {
            return 0;   // Not a valid one
        }
        $postcodeStart = $this->postcode;
        if ($length >= 4) {
            $postcodeStart = Tools::substr($this->postcode, 0, 4);
        }

        do {
            $lookupSQL = "SELECT amzs.country_iso, amzs.zipcode_start, amzs.state_iso, c.id_country, s.id_state
                FROM " . _DB_PREFIX_ . AmazonConstant::TABLE_AMZ_STATES . " AS amzs
                JOIN " . _DB_PREFIX_ . "country AS c ON amzs.country_iso = c.iso_code
                JOIN " . _DB_PREFIX_ . "state AS s ON (c.id_country = s.id_country AND amzs.state_iso = s.iso_code)
                WHERE c.id_country = " . (int)$this->countryId . " AND amzs.zipcode_start = '" . pSQL($postcodeStart) . "'";
            $state = Db::getInstance()->getRow($lookupSQL);
            if ($state) {
                return (int)$state['id_state'];
            }
            $postcodeStart = Tools::substr($postcodeStart, 0, -1);  // Truncate last character
        } while (Tools::strlen($postcodeStart >= 2));

        return 0;
    }

    /**
     * @return int
     */
    private function lookupStateIdByIsoCode()
    {
        if (!$this->isoCode || empty($this->isoCode)) {
            return 0;
        }

        $statesList = State::getStatesByIdCountry($this->countryId);
        if (is_array($statesList) && count($statesList)) {
            foreach ($statesList as $state) {
                $state_or_region = AmazonTools::toKey($this->isoCode);

                if (AmazonTools::toKey($state['iso_code']) == $state_or_region
                    || AmazonTools::toKey($state['name']) == $state_or_region) {
                    return (int)$state['id_state'];
                }
            }
        }

        return 0;
    }
}
