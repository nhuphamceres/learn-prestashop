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

class CDiscountBatch
{
    public $id = null;

    public $timestart = 0;
    public $timestop  = 0;

    public $created = 0;
    public $updated = 0;
    public $deleted = 0;

    public $channel = 0;

    public function __construct($timestamp = null)
    {
        if ($timestamp == null) {
            $this->timestart = time();
        } else {
            $this->timestart = (int)$timestamp;
        }
    }

    public function format()
    {
        $result = array();

        $result['id'] = $this->id ? $this->id : '-';

        $result['hasid'] = $this->id ? true : false;

        $result['timestart'] = $this->timestart ? CommonTools::displayDate(date('Y-m-d H:i:s', $this->timestart), null, true) : '-';
        $result['timestop'] = $this->timestop ? CommonTools::displayDate(date('Y-m-d H:i:s', $this->timestop), null, true) : '-';
        $result['duration'] = $this->timestop - $this->timestart;

        if ($result['duration'] < 0) {
            $result['duration'] = 0;
        }

        $result['created'] = $this->created;
        $result['updated'] = $this->updated;
        $result['deleted'] = $this->deleted;

        $multitenant = CDiscount::multitenantGetList();

        if ((int)$this->channel && is_array($multitenant) && count($multitenant) && isset($multitenant[(int)$this->channel])) {
            $result['channel'] = $multitenant[(int)$this->channel]['Description'];
        } else {
            $result['channel'] = (int)$this->channel ? (int)$this->channel : '?';
        }

        return ($result);
    }
}

class CdiscountBatches extends CDiscount
{
    const MAX_BATCHES = 10;

    public $key     = null;
    public $current = 0;
    public $batches = array();

    public function __construct($key = null)
    {
        if ($key === null) {
            $key = parent::KEY.'_BATCH_UPDATE';
        }

        $this->key = $key;
        $this->load();
    }

    public function getLastForChannel($channel)
    {
        $batches = unserialize(Configuration::get($this->key));

        if ((int)$channel && is_array($batches) && count($batches)) {
            foreach ($batches as $batch) {
                if ($batch instanceof CDiscountBatch && $batch->id && $batch->timestart && (int)$channel == (int)$batch->channel) {
                    return date('Y-m-d H:i:s', $batch->timestart);
                }
            }
        }

        return null;
    }

    public function load()
    {
        $batches = unserialize(Configuration::get($this->key));

        if (is_array($batches) && count($batches)) {
            return ($this->batches = $batches);
        } else {
            return ($this->batches = array());
        }
    }

    public function add(CDiscountBatch $batch)
    {
        if (!$batch instanceof CDiscountBatch) {
            return (false);
        }

        if (!(is_array($this->batches) && count($this->batches) && reset($this->batches) instanceof CDiscountBatch)) {
            $this->batches = array();
        }

        $index = $this->current = $batch->timestart;

        $this->batches[$index] = $batch;

        krsort($this->batches);

        $this->batches = array_slice($this->batches, 0, self::MAX_BATCHES, true);

        return (true);
    }

    public function current()
    {
        if (!(is_array($this->batches) && isset($this->batches[$this->current]) && $this->batches[$this->current] instanceof CDiscountBatch)) {
            return (null);
        }

        return ($this->batches[$this->current]);
    }

    public function save()
    {
        return (Configuration::updateValue($this->key, serialize($this->batches)));
    }
}
