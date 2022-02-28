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
* @author     Tran Pham
* @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license    Commercial license
* @package    Mirakl
* Support by mail  :  support.mirakl@common-services.com
*}

<div class="form-group">
    <label class="control-label col-lg-3">{l s='Marketplace' mod='mirakl'}</label>
    <div class="margin-form col-lg-9">
        <select name="selected-mkp" title="{l s='Marketplace' mod='mirakl'}">
            {foreach from=$mkps key=id_mkp item=mkp}
                <option value="{$id_mkp}">{$mkp.display_name}</option>
            {/foreach}
        </select>
    </div>
</div>
