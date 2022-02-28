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
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

require_once(dirname(__FILE__) . '/../common/zip.class.php');

// todo: Promote to common in the future
class AmazonZip extends CommonZip
{
    protected $zipPath;
    protected $content;
    protected $debugMode;

    public function __construct($zipPath, $content, $debug = false)
    {
        $this->debugMode = $debug;
        $this->zipPath = $zipPath;
        $this->content = $content;
    }

    /**
     * @param string $zipfile Zip path
     * @param $from
     * @return bool
     * @throws PrestaShopException
     */
    protected function createZipWithZipArchive($zipfile, $from)
    {
        $this->pd(sprintf('Creating Zip File: %s', $this->zipPath));

        if (file_exists($this->zipPath)) {
            if (!unlink($this->zipPath)) {
                $this->ed(sprintf('%s(%s): ' . 'Unable to remove: %s', basename(__FILE__), __LINE__, $this->zipPath));

                return false;
            }
        }

        $zip = new ZipArchive();
        if (!$zip->open($this->zipPath, ZIPARCHIVE::CREATE)) {
            $this->ed(sprintf('%s(%s): ' . 'Unable to open zip for writing: %s', basename(__FILE__), __LINE__, $this->zipPath));

            return false;
        }

        foreach ($this->content as $origin) {
            $this->pd(sprintf('Trying to add: %s to %s' . "\n<br>", $origin, $this->zipPath));
            $basePath = dirname($this->zipPath);

            if (is_dir($origin)) {
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($origin));
                foreach ($files as $name => $file) {
                    // Skip directories (they would be added automatically)
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($basePath) + 1);
                        // Add file from realpath to relative path (based on zip path)
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            } else {
                if (!$zip->addFile($origin)) {
                    $this->ed(sprintf('%s(%s): ' . 'Unable to add to zip: %s', basename(__FILE__), __LINE__, $origin));

                    return false;
                } else {
                    $this->pd(sprintf('Added: %s' . "\n<br>", $origin));
                }
            }
        }

        $zip->close();

        return true;
    }

    protected function pd($debug)
    {
        if ($this->debugMode) {
            print $debug;
        }
    }

    protected function ed($debug)
    {
        if ($this->debugMode) {
            Tools::displayError($debug);
        }
    }
}
