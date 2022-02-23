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
 * ...........................................................................
 *
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice_suivicolis@common-services.com
 */

class SoNiceSuiviEvent
{

    const PICTO_HOLD = 0;
    const PICTO_DELIVERY = 1;
    const PICTO_INCOVENIENCE = 2;
    const PICTO_HOME = 3;
    const PICTO_RELAY = 4;
    const PICTO_INTERNATIONAL = 5;
    const COLIPOSTE_STATSUS = 'https://dl.dropboxusercontent.com/u/60698220/coliposte_status.json';

    protected $lang;

    protected $codes = array();

    protected $codes_mapping = array(
        self::PICTO_HOLD => array('COMCFM', 'PCHMQT'),
        self::PICTO_DELIVERY => array(
            'PCHCFM', 'AARDVY', 'AARCFM', 'LIVCFM', 'LIVREO', 'RENNRV',
            'RENAIN', 'RENAVA', 'RENCAD', 'RENDIA', 'RENDIV', 'PCHCEX',
            'RENLNA', 'RENSNC', 'RENSRB', 'RENTAR', 'RSTBRT', 'RENACP',
            'RSTNCG', 'SOLREO', 'CHGCFM', 'DCHCFM', 'DCHDDT', 'DOUAGV',
            'DOUCOI', 'DOUCOM', 'DOUCRR', 'DOUDDI', 'DOUDDM', 'DOUDOU',
            'DOUDVR', 'DOUEXI', 'DOUFCI', 'DOUFCM', 'DOUIDD', 'DOUIIR',
            'DOUNIR', 'DOUPRO', 'DOURES', 'EXPCFM'
        ),
        self::PICTO_INCOVENIENCE => array(
            'LIVREO', 'RENAIN', 'RENAVA', 'RENCAD', 'RENDIA', 'RENDIV',
            'RENSNC', 'RENSRB', 'RENTAR', 'DOUAGV', 'DOUCOI', 'DOUCOM',
            'DOUCRR', 'DOUDDI', 'DOUDDM', 'DOUDOU', 'DOUDVR', 'DOUEXI',
            'DOUFCI', 'DOUFCM', 'DOUIDD', 'DOUIIR', 'DOUNIR', 'DOUPRO',
            'DOURES', 'SOLREI'
        ),
        self::PICTO_RELAY => array(
            'RENAVI', 'RSTBRT'
        ),
        self::PICTO_HOME => array('LIVCFM', 'LIVGAR', 'LIVRTI'),
        self::PICTO_INTERNATIONAL=> array(
            'DCHDDT', 'EXPCFM', 'PCHCEX'
        )
    );

    public function __construct($lang = 'fr')
    {
        $this->lang = Tools::strtolower($lang);

        $coliposte_statuses = Tools::file_get_contents(dirname(__FILE__).'/../settings/coliposte_status.json');

        $this->codes = Tools::jsonDecode($coliposte_statuses, true);
    }

    public function getColiposteStateTranslation($inovert, $lang = 'fr')
    {
        $lang = Tools::strtolower($lang);

        if (isset($this->codes[$lang][$inovert])) {
            return ($this->codes[$lang][$inovert]);
        }

        return false;
    }

    public function getCodes($inovert = null)
    {
        if ($inovert && array_key_exists($inovert, $this->codes[$this->lang])) {
            return $this->codes[$this->lang][$inovert];
        } elseif ($inovert && array_key_exists($inovert, $this->codes['en'])) {
            return $this->codes['en'][$inovert];
        } elseif ($inovert) {
            return '';
        }

        return array_key_exists($this->lang, $this->codes) ?
            $this->codes[$this->lang] : array();
    }

    public function getPictoID($inovert)
    {
        foreach ($this->codes_mapping as $id_picto => $inoverts) {
            if (in_array($inovert, $inoverts)) {
                return (int)$id_picto;
            }
        }

        return 0;
    }
}
