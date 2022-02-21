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

class CDiscountEanCsvManager
{
    #fetch mode
    const FM_ARRAY = '1';
    const FM_OBJECT = '2';

    const CD_CSV_CHARSET_SRC = 'UTF-8';
    const CD_CSV_CHARSET_DST = 'UTF-8';
    const CD_CSV_HEADER_LINENUM = '3';
    const CD_COLUMN = 6;


    #Config
    public $headernum;
    public $charsetsrc;
    public $charsetdst;

    #Data
    public $csvfile  = null;
    public $loadflag = false;
    public $datalist = null;

    public function __construct($filepath, $headernum = null, $charset = null)
    {
        $this->charsetsrc = self::CD_CSV_CHARSET_SRC;
        $this->charsetdst = self::CD_CSV_CHARSET_DST;
        $this->headernum = self::CD_CSV_HEADER_LINENUM;

        $this->csvfile = $filepath;
        if ($headernum) {
            $this->headernum = $headernum;
        }
        if ($charset) {
            $this->charsetsrc = $charset;
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
        while ($data = fgetcsv($fp, $fileSize, $delimiter)) {
            #  ignore header data
            if (!$headerok) {
                if (Tools::strtolower($data[1]) == 'ean') {
                    $this->index_reference = 0;
                    $this->index_ean = 1;
                    $this->index_sku = 2;
                    $this->index_libelle = 3;
                    $this->index_status = 4;
                    $this->index_comment = 5;
                    $headerok = true;
                }
                continue;
            };
            if (!is_array($data)) {
                continue;
            }
            $szTemp = implode($data, '');
            if (!trim($szTemp)) {
                continue;
            }

            $eanentry = $this->parse($data);
            if ($eanentry && $eanentry->ean && $eanentry->reference) {
                $this->datalist[] = $eanentry;
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

        $eanentry = new CDiscountEANMatchingEntry();

        $item = isset($data[$this->index_reference]) ? $data[$this->index_reference] : '';
        $eanentry->setreference((string)$item);

        if (stristr($eanentry->reference, '_') !== false) {
            $split_combination = explode('_', $eanentry->reference);

            if (!(int)$split_combination[0] || !(int)$split_combination[1]) {
                return (false);
            }

            $eanentry->setid_product((int)$split_combination[0]);
            $eanentry->setid_product_attribute((int)$split_combination[1]);
        } else {
            $eanentry->setid_product((int)$eanentry->reference);
            $eanentry->setid_product_attribute(false);
        }

        $item = isset($data[$this->index_ean]) ? $data[$this->index_ean] : '';
        $eanentry->setean($item);

        $item = isset($data[$this->index_sku]) ? $data[$this->index_sku] : '';
        $eanentry->setsku($item);

        $item = isset($data[$this->index_libelle]) ? $data[$this->index_libelle] : '';
        $eanentry->setlibelle($item);

        $item = isset($data[$this->index_status]) ? $data[$this->index_status] : '';
        $eanentry->setstatus($item);

        $item = isset($data[$this->index_comment]) ? $data[$this->index_comment] : '';
        $eanentry->setcomment($item);

        if (!$eanentry->ean || !$eanentry->sku || !$eanentry->reference || stripos('OK', $eanentry->status) !== false) {
            return (false);
        }

        return $eanentry;
    }
}

class CDiscountEANMatchingEntry extends CDImportBase
{
    public $reference            = null;
    public $id_product           = null;
    public $id_product_attribute = null;
    public $ean                  = null;
    public $sku                  = null;
    public $libelle              = null;
    public $status               = null;
    public $comment              = null;
}
