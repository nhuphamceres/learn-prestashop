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

require_once(dirname(__FILE__).'/../classes/cdiscount.tools.class.php');

class CdiscountSettings extends Cdiscount
{
    const MANDATORY = 1;
    const RECOMMENDED = 2;

    const SOURCE_URL = 'https://dl.dropboxusercontent.com/u/53469716/cdiscount/'; // Public directory on DropBox

    public static function cache($file, $local_dir, $remote_dir, $url)
    {
        if (!is_dir($local_dir)) {
            return (false);
        }

        $local_file = $local_dir.$file;
        $remote_file = $url.$remote_dir.$file;

        if (!is_writable($local_dir)) {
            chmod($local_dir, 0775);
        }

        if (file_exists($local_file) && filesize($local_file) > 128) {
            if ((time() - filectime($local_file)) < (60 * 60 * 24 * 15)) {
                return (false);
            }
        } // Local file is not expired

        $contents = CDiscountTools::file_get_contents($remote_file.'?dl=1'); //TODO: VALIDATION - Malfunctions with CDiscountTools::file_get_contents.

        if (Tools::strlen($contents) > 128) {
            if (file_exists($local_file)) {
                @unlink($local_file);
            }

            file_put_contents($local_file, $contents);

            return (true);
        }

        return (false);
    }


    public static function getGlossary($lang, $section)
    {
        $sections = array();
        $datadir = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'settings'.DIRECTORY_SEPARATOR.'glossary'.DIRECTORY_SEPARATOR.$section.DIRECTORY_SEPARATOR;

        if (!is_dir($datadir)) {
            return (false);
        }

        $glossary = array();
        $files = glob($datadir.'*.txt');

        if ($files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    $shortfilename = preg_replace('/\.[a-z]{2}/', '', basename($file, '.txt'));
                    $sections[] = $shortfilename;
                }

                if (count($sections)) {
                    foreach (array_unique($sections) as $term) {
                        $target_file = $datadir.sprintf('%s.%s.txt', $term, $lang);
                        $alt_file = $datadir.sprintf('%s.en.txt', $term);

                        if (file_exists($target_file)) {
                            $file = $target_file;
                        } elseif (file_exists($alt_file)) {
                            $file = $alt_file;
                        } else {
                            $file = null;
                        }

                        if ($file) {
                            $glossary_content = CDiscountTools::file_get_contents($file); //TODO: VALIDATION - Malfunctions with CDiscountTools::file_get_contents.

                            // https://css-tricks.com/snippets/php/find-urls-in-text-make-links/
                            $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";

                            if (preg_match($reg_exUrl, $glossary_content, $url) && isset($url[0])) {
                                $glossary_content = preg_replace($reg_exUrl, '<a href="'.$url[0].'" target="_blank">'.$url[0].'</a>', $glossary_content);
                            }

                            $glossary[$term] = nl2br($glossary_content);
                        }
                    }
                }
            }
        }

        return ($glossary);
    }
}
