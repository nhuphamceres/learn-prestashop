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
 * @author    Alexandre D. & Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  contact@common-services.com
 */

require_once(dirname(__FILE__).'/env.php');

$languages = Language::getLanguages();

$features_id = Configuration::get('FNAC_FEATURE_ID');
$features_map = Configuration::get('FNAC_FEATURE_MAP');

$features = array();
$features['en'] = array(
    11 => 'This product is a new product',
    1 => 'This product is a used product like as new',
    2 => 'This product is a used product in very good state',
    3 => 'This product is a used product in good state',
    4 => 'This product is a used product in correct state',
    5 => 'This product is a collection product like new',
    6 => 'This product is a collection product in very good state',
    7 => 'This product is a collection product in good state',
    8 => 'This product is a collection product in correct state'
);
$features['fr'] = array(
    11 => 'Neuf',
    1 => 'Occasion - Comme neuf',
    2 => 'Occasion - Très bon état',
    3 => 'Occasion - Bon état',
    4 => 'Occasion - Etat correct',
    5 => 'Collection - Comme neuf',
    6 => 'Collection - Trés bon état',
    7 => 'Collection - Bon état',
    8 => 'Collection - Etat correct'
);

if (!(int)$features_id) {
    $feature = new Feature();
    foreach ($languages as $language) {
        $feature->name[$language['id_lang']] = 'Condition';
    }
    $feature->add();
    $id = $feature->id;


    foreach ($features['en'] as $key => $feature) {
        $featureValue = new FeatureValue();
        $featureValue->id_feature = $id;
        $featureValue->custom = 1;

        $name = $feature;

        foreach ($languages as $language) {
            if (isset($features[$language['iso_code']])) {
                $featureValue->value[$language['id_lang']] = utf8_encode($features[$language['iso_code']][$key]);
            } else {
                $featureValue->value[$language['id_lang']] = utf8_encode($features['en'][$key]);
            }
        }
        $featureValue->add();

        /* on remplace le texte par l'id pour la feature map */
        $features[$key] = $featureValue->id;
    }

    /* Serialization des id afin d'etablir la correspondance entre la table FNAC et Presta */
    Configuration::updateValue('FNAC_FEATURE_ID', $id);
    Configuration::updateValue('FNAC_FEATURE_MAP', serialize($features));
} else {
    Configuration::deleteByName('FNAC_FEATURE_ID');
    Configuration::deleteByName('FNAC_FEATURE_MAP');

    $feature = new Feature($features_id);

    $featureValue = new FeatureValue();
    $featureValue->id_feature = $features_id;
    $featureValue->delete();
    $feature->delete();
}
