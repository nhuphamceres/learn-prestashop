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

<div id="conf-mkp_specific" class="tabItem" style="display:none;">
    <h2>{$configuration.display_name|escape:'htmlall':'UTF-8'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3" for="referencia_generica_eci">
            {$configuration.specific_fields.referencia_generica_eci.label|escape:'htmlall':'UTF-8'}
        </label>
        <div class="margin-form col-lg-9">
            <input type="text" id="referencia_generica_eci" name="mkp_specific_fields[referencia_generica_eci]" style="width: 300px;"
                   value="{if isset($configuration.selected.referencia_generica_eci)}{$configuration.selected.referencia_generica_eci|escape:'htmlall':'UTF-8'}{/if}">
        </div>
    </div>

    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
</div>
