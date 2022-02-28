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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class AmazonRemoteDownloader
{
    const SOURCE_URL = 'https://s3-us-west-2.amazonaws.com/common-services-public/amazon/data/'; // Public directory on S3
    const TIMEOUT = 120;
    const RES_STATES = 'amazon_states.sql';

    protected $resourceName;
    protected $remoteTarget;
    protected $remoteChecksum;
    protected $compress = true;
    protected $localPath;
    protected $localFile;

    protected $errors = array();

    public function __construct($resourceName, $compress = true)
    {
        $this->resourceName = $resourceName;
        $this->remoteTarget = self::SOURCE_URL . $resourceName;
        $this->remoteChecksum = $this->remoteTarget . '.md5';
        $this->compress = $compress;
        $this->localPath = _PS_MODULE_DIR_ . 'amazon/import/';
        $this->localFile = $this->localPath . $resourceName;
    }

    public function downloadResource()
    {
        // Get checksum first
        $checksum = AmazonTools::fileGetContents($this->remoteChecksum);
        if (!strpos($checksum, $this->resourceName)) {
            $this->errors[] = 'Failed to download checksum';
            return false;   // Checksum not contain the target
        }
        $md5_remote = Tools::substr($checksum, 0, strpos($checksum, ' '));
        if (!Tools::strlen($md5_remote)) {
            $this->errors[] = 'Checksum empty';
            return false;   // Checksum empty
        }
        if (!preg_match('/^[a-f0-9]{32}$/', $md5_remote)) {
            $this->errors[] = 'Checksum not valid';
            return false;   // Checksum not valid
        }

        // Fetch target and compare to checksum
        if (!$this->prepareLocalPath()) {
            $this->errors[] = 'Failed to prepare destination path';
            return false;   // Failed to prepare local path
        }
        if (!$this->downloadRemoteResource($md5_remote)) {
            $this->errors[] = 'Failed to download resource';
            return false;
        }
        if (!file_exists($this->localFile) || !filesize($this->localFile)) {
            $this->errors[] = 'Empty local file';
            return false;
        }

        return true;
    }

    /**
     * @return bool|mixed|string
     */
    public function getResource()
    {
        if (!file_exists($this->localFile)) {
            return '';
        }
        $content = AmazonTools::fileGetContents($this->localFile);
        if (!$content) {
            return '';
        }

        return $content;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    private function prepareLocalPath()
    {
        if (!is_dir($this->localPath)) {
            if (!@mkdir($this->localPath)) {
                $this->errors[] = 'Cannot create local directory';
                return false;
            }
        }
        @chmod($this->localPath, 0777);

        if (file_put_contents($this->localPath . '.htaccess', "deny from all\n") === false) {
            $this->errors[] = 'Cannot write local directory';
            return false;
        }

        return true;
    }

    private function downloadRemoteResource($checksum)
    {
        $remoteUrl = $this->getRealRemoteTarget();
        $downloadingPath = $this->getRealLocalFile();
        $remoteContent = AmazonTools::fileGetContents($remoteUrl, false, null, self::TIMEOUT);
        if (!$remoteContent || !file_put_contents($downloadingPath, $remoteContent)) {
            $this->errors[] = 'Failed to write resource to local file';
            return false;
        }

        if (md5_file($downloadingPath) != $checksum) {
            $this->errors[] = 'Checksum mismatch';
            return false;
        }

        if ($this->compress) {
            $fileOut = fopen($this->localFile, 'wb');
            if (!$fileOut) {
                $this->errors[] = 'False to uncompress resource';
                return false;
            }
            $file = gzopen($downloadingPath, 'rb');
            while (!gzeof($file)) {
                fwrite($fileOut, gzread($file, 4096));
            }
            fclose($fileOut);
            gzclose($file);
        }

        // If not compress, $downloadingPath is the local file
        return true;
    }

    private function getRealLocalFile()
    {
        return $this->compress ? ($this->localFile . '.gz') : $this->localFile;
    }

    private function getRealRemoteTarget()
    {
        return $this->compress ? ($this->remoteTarget . '.gz') : $this->remoteTarget;
    }
}
