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

class PriceMinisterBatch
{

    public $id = null;
    public $timestart = 0;
    public $timestop = 0;
    public $file = null;
    public $created = 0;
    public $updated = 0;
    public $deleted = 0;

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

        $result['timestart'] = $this->timestart ? PriceMinisterTools::displayDate(date('Y-m-d H:i:s', $this->timestart), null, true) : '-';
        $result['timestop'] = $this->timestop ? PriceMinisterTools::displayDate(date('Y-m-d H:i:s', $this->timestop), null, true) : '-';
        $result['duration'] = $this->timestop - $this->timestart;

        if ($result['duration'] < 0) {
            $result['duration'] = 0;
        }

        $result['file'] = $this->file;

        $result['created'] = $this->created;
        $result['updated'] = $this->updated;
        $result['deleted'] = $this->deleted;

        return ($result);
    }
}

class PriceMinisterBatches extends PriceMinister
{

    const MAX_BATCHES = 10;
    public $key = null;
    public $current = 0;
    public $batches = array();

    public function __construct($key = null)
    {
        if ($key === null) {
            $key = PriceMinister::CONFIG_BATCH_UPDATE;
        }

        $this->key = $key;
        $this->load();
    }

    public function load()
    {
        if (Tools::getValue('all_shop')) {
            $batches = array_reduce(
                array_map(
                    array($this, 'unserializePad'),
                    Configuration::getMultiShopValues($this->key)
                ),
                'array_merge',
                array()
            );
        } else {
            $batches = unserialize(Configuration::get($this->key));
        }

        if (is_array($batches) && count($batches)) {
            return ($this->batches = $batches);
        } else {
            return ($this->batches = array());
        }
    }

    /**
     * Unserialize value from Configuration::getMultiShopValues() to avoid errors with array_merge.
     *
     * If the result is not an array then return array() instead of false.
     *
     * @param array $item
     * @return array
     */
    private function unserializePad($item)
    {
        $item = unserialize($item);

        return $item ?: array();
    }

    public function getLast()
    {
        $batches = unserialize(Configuration::get($this->key));

        if (is_array($batches) && count($batches)) {
            foreach ($batches as $batch) {
                if ($batch instanceof PriceMinisterBatch && $batch->id && $batch->timestart) {
                    return date('Y-m-d H:i:s', $batch->timestart);
                }
            }
        }

        return null;
    }

    public function add(PriceMinisterBatch $batch)
    {
        if (!$batch instanceof PriceMinisterBatch) {
            return (false);
        }

        if (!(is_array($this->batches) && count($this->batches) && reset($this->batches) instanceof PriceMinisterBatch)) {
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
        if (!(is_array($this->batches) && isset($this->batches[$this->current]) && $this->batches[$this->current] instanceof PriceMinisterBatch)) {
            return (null);
        }

        return ($this->batches[$this->current]);
    }

    public function save()
    {
        return (Configuration::updateValue($this->key, serialize($this->batches)));
    }
}