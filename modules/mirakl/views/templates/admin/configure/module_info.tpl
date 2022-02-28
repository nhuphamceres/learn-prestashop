{**
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
*
* @package    Mirakl
* @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @author     Tran Pham
* @license    Commercial license
* Support by mail  :  support.mirakl@common-services.com
*}

{if $versionCheck.active}
    <div class="{$versionCheck.class}" style="font-weight:bold">
        {l s='Module Update: Your version will be auto-updated from %s to %s after configuration changes' sprintf=[$versionCheck.savedVersion, $versionCheck.currentVersion] mod='mirakl'}
        <br />
        {l s='Please verify again your settings. Please clear your Smarty and Browser caches...' mod='mirakl'}
    </div>
{/if}

{if $memoryPeak.active}
    <div class="conf confirm">
        Memory Peak: {$memoryPeak.memory|string_format:'%.2f'} MB - Post Count: {$memoryPeak.postCount}
    </div>
{/if}