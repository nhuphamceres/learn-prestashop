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

class CDiscountCSVManager
{
    #fetch mode
    const FM_ARRAY = '1';
    const FM_OBJECT = '2';

    const CD_CSV_CHARSET_SRC = 'UTF-8';
    const CD_CSV_CHARSET_DST = 'UTF-8';
    const CD_CSV_HEADER_LINENUM = '1';
    const CD_COLUMN = 5;


    #Config
    public $headernum;
    public $limit;
    public $charsetsrc;
    public $charsetdst;

    #Data
    public $csvfile  = null;
    public $loadflag = false;
    public $datalist = null;

    public function __construct($filepath, $limit = 1000)
    {
        $this->charsetsrc = self::CD_CSV_CHARSET_SRC;
        $this->charsetdst = self::CD_CSV_CHARSET_DST;
        $this->headernum = self::CD_CSV_HEADER_LINENUM;

        $this->csvfile = $filepath;
        if ($limit) {
            $this->limit = $limit;
        }
    }

    public function getTotalData()
    {
        if (!$this->csvfile) {
            return array();
        }
        if ($this->loadflag) {
            return $this->datalist;
        }

        if ($this->loadData()) {
            return $this->datalist;
        }

        return array();
    }

    public function loadData($filepath = '')
    {
        if ($filepath) {
            $this->csvfile = $filepath;
        }

        if (!file_exists($this->csvfile)) {
            throw new Exception('Invalid file');
        }

        $this->datalist = array();
        $fileSize = 10000;

        $fp = fopen($this->csvfile, 'r');
        $buf = fread($fp, 1000);
        fclose($fp);

        $count1 = count(explode(',', $buf));
        $count2 = count(explode(';', $buf));
        if ($count1 > $count2) {
            $delimiter = ',';
        } else {
            $delimiter = ';';
        }
        $headerok = false;
        $fp = fopen($this->csvfile, 'r');
        $count = 1;
        while ($data = fgetcsv($fp, $fileSize, $delimiter)) {
            #  ignore header data
            if (!$headerok) {
                if (Tools::strtolower($data[4]) == 'code') {
                    $this->index_code = 4;
                    $this->index_name = 3;
                    $this->index_sub3 = 2;
                    $this->index_sub2 = 1;
                    $this->index_sub1 = 0;
                    $headerok = true;
                }
                if (Tools::strtolower($data[0]) == 'code') {
                    $this->index_code = 0;
                    $this->index_name = 4;
                    $this->index_sub3 = 3;
                    $this->index_sub2 = 2;
                    $this->index_sub1 = 1;
                    $headerok = true;
                }
                continue;
            }
            if ($count++ > $this->limit) {
                break;
            }
            if (!is_array($data)) {
                continue;
            }
            $szTemp = implode($data, '');
            if (!trim($szTemp)) {
                continue;
            }

            $category = $this->parse($data);
            if ($category && $category->code) {
                $this->datalist[] = $category;
            }
        }
        fclose($fp);

        $this->loadflag = true;

        return true;
    }

    public function parse($data)
    {
        if (!is_array($data)) {
            return null;
        }
        $data = array_map('trim', $data);
        $category = new CDiscountCSVCategory();


        $item = isset($data[$this->index_code]) ? $data[$this->index_code] : '';
        $category->setcode((string)$item);

        $item = isset($data[$this->index_sub1]) ? $data[$this->index_sub1] : '';
        //$item = mb_convert_encoding($item, $this->charsetdst, $this->charsetsrc);
        $category->setname_sub1($item);

        $item = isset($data[$this->index_sub2]) ? $data[$this->index_sub2] : '';
        //$item = mb_convert_encoding($item, $this->charsetdst, $this->charsetsrc);
        $category->setname_sub2($item);

        $item = isset($data[$this->index_sub3]) ? $data[$this->index_sub3] : '';
        //$item = mb_convert_encoding($item, $this->charsetdst, $this->charsetsrc);
        $category->setname_sub3($item);

        $item = isset($data[$this->index_name]) ? $data[$this->index_name] : '';
        //$item = mb_convert_encoding($item, $this->charsetdst, $this->charsetsrc);
        $category->setname($item);

        if (!$category->code || !$category->name_sub1 || !$category->name_sub2 || !$category->name_sub3) {
            return (false);
        }

        return $category;
    }
}

class CDiscountCSVCategory extends CDImportBase
{
    public $name_sub1 = null;
    public $name_sub2 = null;
    public $name_sub3 = null;
    public $name      = null;
    public $code      = null;
}
