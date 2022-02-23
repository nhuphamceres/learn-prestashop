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
 * Support by mail  :  support.sonice@common-services.com
 */

class SoNiceSuiviTools
{

    public $holidays;

    public static function displayDate($date, $id_lang = null, $full = false, $separator = '-')
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_lang = null;

            return (Tools::displayDate($date, $id_lang, $full));
        } else {
            return (Tools::displayDate($date, $id_lang, $full, $separator));
        }
    }

    public static function parcelIsLate($id_parcel = null)
    {
        if (!$id_parcel) {
            return (false);
        }

        $sql = '
            SELECT `coliposte_date`, `date_add`
            FROM `'._DB_PREFIX_.'sonice_suivicolis`
            WHERE `shipping_number` = "'.pSQL($id_parcel).'"';

        if ($row = Db::getInstance()->getRow($sql)) {
            $holidays = SoNiceSuiviTools::getFrenchHolidays();

            // @see : http://stackoverflow.com/questions/2891937/strtotime-doesnt-work-with-dd-mm-yyyy-format
            $date_start = date('Y-m-d', strtotime($row['date_add']));
            $date_end = date('Y-m-d', strtotime($row['coliposte_date']));
            $working_days = SoNiceSuiviTools::getWorkingDays($date_start, $date_end, $holidays);

            if ($working_days > 3) {
                return (true);
            }

            return (false);
        }

        return (false);
    }


    /**
     * The function returns the no. of business days between two dates and it skips the holidays
     *
     * @source : http://stackoverflow.com/questions/336127/calculate-business-days
     *
     * @param {date|string} $start_date
     * @param {date|string} $end_date
     * @param array $holidays
     * @return int
     */
    public static function getWorkingDays($start_date, $end_date, $holidays = array())
    {
        $end_date = strtotime($end_date);
        $start_date = strtotime($start_date);

        $days = ($end_date - $start_date) / 86400 + 1;

        $no_full_weeks = floor($days / 7);
        $no_remaining_days = fmod($days, 7);

        $the_first_day_of_week = date('N', $start_date);
        $the_last_day_of_week = date('N', $end_date);

        if ($the_first_day_of_week <= $the_last_day_of_week) {
            if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) {
                $no_remaining_days--;
            }
            if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) {
                $no_remaining_days--;
            }
        } else {
            if ($the_first_day_of_week == 7) {
                $no_remaining_days--;

                if ($the_last_day_of_week == 6) {
                    $no_remaining_days--;
                }
            } else {
                $no_remaining_days -= 2;
            }
        }

        $working_days = $no_full_weeks * 5;
        if ($no_remaining_days > 0) {
            $working_days += $no_remaining_days;
        }

        foreach ($holidays as $holiday) {
            $time_stamp = strtotime($holiday);

            if ($start_date <= $time_stamp && $time_stamp <= $end_date && date('N', $time_stamp) != 6 && date('N', $time_stamp) != 7) {
                $working_days--;
            }
        }

        return ($working_days);
    }


    /**
     * Return an array containing french holidays for the current year
     *
     * @return array
     */
    public static function getFrenchHolidays()
    {
        $holidays = array(
            // static date
            (date('Y') + 1).'-01-01',
            date('Y').'05-01',
            date('Y').'05-08',
            date('Y').'07-14',
            date('Y').'08-15',
            date('Y').'11-01',
            date('Y').'11-11',
            date('Y').'12-25',
        );

        if (function_exists('easter_date')) {
            // floating date
            $paque = new DateTime(date('Y-m-d', easter_date(date('Y'))));
            $ascension = new DateTime(date('Y-m-d', easter_date(date('Y'))));
            $pentecote = new DateTime(date('Y-m-d', easter_date(date('Y'))));

            $paque->modify('+1 day');
            $ascension->modify('+40 days');
            $pentecote->modify('+51 days');

            $holidays = array_merge($holidays, array(
                $paque->format('Y-m-d'),
                $pentecote->format('Y-m-d'),
                $ascension->format('Y-m-d')
            ));
        }

        return ($holidays);
    }
}
