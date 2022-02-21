<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 * @copyright Copyright (c) 2011-2017 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
  * Support by mail:  support.cdiscount@common-services.com
 */

require_once(dirname(__FILE__).'/cdiscount.webservice.class.php');
require_once(dirname(__FILE__).'/../common/configuration.class.php');

class CDiscountCategories extends CDiscountWebservice
{
    const LF = "\n";

    const CATEGORY_FILENAME = 'categories.xml.gz';
    const ALLOWED_CATEGORY_FILENAME = 'allowed_categories.xml.gz';
    const UNIVERSES_FILENAME = 'universes.txt';

    public static $pathCategory          = null;
    public static $category_file         = null;
    public static $category_xml          = null;
    public static $allowed_category_file = null;
    public static $universes_file        = null;

    public static $universes = array();

    public static $universes_template
        = array(
            'ADULTE - EROTIQUE',
            'AMENAGEMENT URBAIN - VOIRIE',
            'ANIMALERIE',
            'ARME DE COMBAT - ARME DE SPORT',
            'ART DE LA TABLE - ARTICLES CULINAIRES',
            'ARTICLES POUR FUMEUR',
            'AUTO - MOTO (NEW)',
            'BAGAGERIE',
            'BIJOUX -  LUNETTES - MONTRES',
            'BRICOLAGE - OUTILLAGE - QUINCAILLERIE',
            'CHAUSSURES - ACCESSOIRES',
            'CONDITIONNEMENT',
            'CULTURE / JEUX',
            'DECO - LINGE - LUMINAIRE',
            'DROGUERIE (NEW)',
            'ELECTROMENAGER',
            'EPICERIE',
            'HYGIENE - BEAUTE - PARFUM',
            'INFORMATIQUE',
            'JARDIN - PISCINE',
            'JOUET (NEW)',
            'LOISIRS CREATIFS - BEAUX ARTS (NEW)',
            'MANUTENTION',
            'MATERIEL MEDICAL',
            'MERCERIE',
            'MEUBLE',
            'OFFRES PARTENAIRES',
            'PAPETERIE - MATERIEL DE BUREAU',
            'PARAPHARMACIE',
            'PHOTO - OPTIQUE',
            'POINT DE VENTE - COMMERCE - ADMINISTRATION',
            'PRODUITS FRAIS',
            'PRODUITS SURGELES',
            'PUERICULTURE',
            'SONO - DJ - INSTRUMENT',
            'SPORT (NEW)',
            'TELEPHONIE - GPS',
            'TV - VIDEO - SON',
            'VETEMENTS - LINGERIE',
            'VIN - ALCOOL - LIQUIDES'
        );

    public static function countAllowed()
    {
        self::loadXml();

        if (!self::$category_xml instanceof SimpleXMLElement) {
            return (false);
        }

        $categoryTreeResult = self::$category_xml->xpath('/GetAllowedCategoryTreeResult/CategoryTree/ChildrenCategoryList/CategoryTree/ChildrenCategoryList/CategoryTree/ChildrenCategoryList/CategoryTree/ChildrenCategoryList/CategoryTree[AllowOfferIntegration="true"]');

        return (count($categoryTreeResult));
    }

    public static function loadXml()
    {
        self::setPath();

        if (Configuration::get(parent::KEY.'_MININUM')) {
            return (false);
        }

        // Return Cache
        if (self::$category_xml !== null) {
            return (self::$category_xml);
        }

        if (!file_exists(self::$allowed_category_file)) {
            return (false);
        }

        if (!($result = simplexml_load_file('compress.zlib://'.self::$allowed_category_file))) {
            return (false);
        }

        if (!$result instanceof SimpleXMLElement) {
            return (false);
        }

        self::$category_xml = $result;

        return (true);
    }

    public static function isLoaded()
    {
        return is_array(self::$universes) && count(self::$universes);
    }

    public static function setPath()
    {
        self::$pathCategory = dirname(__FILE__).DS.'..'.DS.Cdiscount::XML_DIRECTORY;

        self::$category_file = self::$pathCategory.DS.self::CATEGORY_FILENAME;
        self::$allowed_category_file = self::$pathCategory.DS.self::ALLOWED_CATEGORY_FILENAME;
        self::$universes_file = self::$pathCategory.DS.self::UNIVERSES_FILENAME;
    }

    /**
     * @param $universe
     * @return array|false|mixed
     */
    public static function universeToCategories($universe)
    {
        static $cached = array();

        if (Configuration::get(parent::KEY.'_MININUM')) {
            return (false);
        }

        if (isset($cached[$universe])) {
            return ($cached[$universe]);
        }

        self::setPath();

        if (!file_exists(self::$allowed_category_file)) {
            return (false);
        }

        if (!($result = simplexml_load_file('compress.zlib://'.self::$allowed_category_file))) {
            return (false);
        }

        if (!$result instanceof SimpleXMLElement) {
            return (false);
        }

        $categoryTreeResult = $result->xpath('/GetAllowedCategoryTreeResult/CategoryTree/ChildrenCategoryList/*[Name="'.$universe.'"]/ChildrenCategoryList/CategoryTree');
        $categories = array();

        if (is_array($categoryTreeResult) && count($categoryTreeResult)) {
            foreach ($categoryTreeResult as $categoryTree) {
                if (!$categoryTree instanceof SimpleXMLElement) {
                    continue;
                }

                $SubCategories = $categoryTree->xpath('ChildrenCategoryList/CategoryTree');
                $CategoryName = $categoryTree->Name;

                self::getSubCategoriesTree($CategoryName, $SubCategories, $categories);
            }
            $cached[$universe] = $categories;
        }

        return ($categories);
    }

    private static function getSubCategoriesTree($CategoryName, $SubCategories, &$categories)
    {
        foreach ($SubCategories as $childCategory) {
            if (!$childCategory instanceof SimpleXMLElement) {
                continue;
            }

            if (!isset($childCategory->Code)) {
                continue;
            }

            if (!isset($childCategory->Name)) {
                continue;
            }
            $name = trim((string)$childCategory->Name);
            $code = trim((string)$childCategory->Code);

            $current_level_name = sprintf('%s / %s', $CategoryName, $name);

            if (count($childCategory->ChildrenCategoryList->CategoryTree) > 0) {
                self::getSubCategoriesTree($current_level_name, $childCategory->ChildrenCategoryList->CategoryTree, $categories);
                continue;
            }
            if (isset($childCategory->AllowOfferIntegration) && trim((string)$childCategory->AllowOfferIntegration) == 'false') {
                continue;
            }

            $categories[$code] = sprintf('%s / %s', $CategoryName, $name);
        }
    }

    public static function loadUniverses()
    {
        self::setPath();

        if (Configuration::get(parent::KEY.'_MININUM')) {
            return array();
        }

        if (!file_exists(self::$universes_file)) {
            return array();
        }

        if ($result = file(self::$universes_file)) {
            self::$universes = array();

            foreach ($result as $universe) {
                self::$universes[] = trim($universe);
            }
        } else {
            self::$universes = self::$universes_template;
        }
        sort(self::$universes);

        return (self::$universes);
    }


    public static function getAllowedCategoriesTimestamp()
    {
        self::setPath();

        if (!file_exists(self::$allowed_category_file)) {
            return (false);
        }

        return (filemtime(self::$allowed_category_file));
    }

    public static function getCategoriesTimestamp()
    {
        self::setPath();

        if (!file_exists(self::$category_file)) {
            return (false);
        }

        return (filemtime(self::$category_file));
    }

    public static function getUniversesTimestamp()
    {
        self::setPath();

        if (!file_exists(self::$universes_file)) {
            return (false);
        }

        return (filemtime(self::$universes_file));
    }

    // Function name is important
    public function GetAllAllowedCategoryTree()
    {
        $cr = $this->_cr;

        if ($this->debug) {
            printf('%s/%s: GetAllAllowedCategoryTree'.$cr, basename(__FILE__), __LINE__);
        }

        $response = $this->_call(__FUNCTION__, null);

        if ($response) {
            ;
            $result = $this->response(__FUNCTION__, $response);
        } else {
            if ($this->debug) {
                printf('%s/%s: _call() failed'.$cr, basename(__FILE__), __LINE__);
            }

            return (false);
        }

        return ($result);
    }

    /**
     * Function name is important
     * @return false|SimpleXMLElement|string|null
     */
    public function GetAllowedCategoryTree()
    {
        $this->edd('GetAllowedCategoryTree');

        $response = $this->_call(__FUNCTION__, null);
        if ($response) {
            return $this->response(__FUNCTION__, $response);
        }

        $this->edd('Failed to GetAllowedCategoryTree');

        return false;
    }
}
