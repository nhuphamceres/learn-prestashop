{**
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from Common-Services Co., Ltd.
* Use, copy, modification or distribution of this source file without written
* license agreement from the SARL SMC is strictly forbidden.
* In order to obtain a license, please contact us: support.mondialrelay@common-services.com
* ...........................................................................
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe Common-Services Co., Ltd.
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: support.mondialrelay@common-services.com
* ...........................................................................
*
* @package   sonice_suivicolis
* @author    debuss-a
* @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license   Commercial license
* Support by mail  :  support.sonice_suivicolis@common-services.com
*}

<div class="form-group">
    <label class="control-label col-lg-3">So Nice Suivi de Colis</label>
    <div class="margin-form snsc_typo col-lg-9">
        {if !$snsc_infos.module_info_ok}
            {foreach from=$snsc_infos.module_infos item=module_info}
                {if in_array($module_info.level, array('alert alert-warning', 'warn'))}
                    <ps-alert-warn caret="false">{$module_info.message}</ps-alert-warn>
                {elseif in_array($module_info.level, array('alert alert-danger', 'error'))}
                    <ps-alert-error caret="false">{$module_info.message}</ps-alert-error>
                {else}
                    <ps-alert-hint caret="false">{$module_info.message}</ps-alert-hint>
                {/if}
            {/foreach}
        {else}
            <ps-alert-success caret="false">{l s='Module configuration and integrity check passed successfully' mod='sonice_suivicolis'}</ps-alert-success>
        {/if}
    </div>
</div>

<div class="form-group">
    <label class="control-label col-lg-3">{l s='PHP Settings' mod='sonice_suivicolis'}</label>
    <div class="margin-form snsc_typo col-lg-9">
        {if !$snsc_infos.php_info_ok}
            {foreach from=$snsc_infos.php_infos item=php_info}
                {if in_array($php_info.level, array('alert alert-warning', 'warn'))}
                    <ps-alert-warn caret="false">{$php_info.message}</ps-alert-warn>
                {elseif in_array($php_info.level, array('alert alert-danger', 'error'))}
                    <ps-alert-error caret="false">{$php_info.message}</ps-alert-error>
                {else}
                    <ps-alert-hint caret="false">{$php_info.message}</ps-alert-hint>
                {/if}
            {/foreach}
        {else}
            <ps-alert-success caret="false">
                {l s='Module configuration and integrity check passed successfully' mod='sonice_suivicolis'}
            </ps-alert-success>
        {/if}
    </div>
</div>

<div class="form-group">
    <label class="control-label col-lg-3">{l s='Prestashop Settings' mod='sonice_suivicolis'}</label>
    <div class="margin-form snsc_typo col-lg-9">
        {if !$snsc_infos.prestashop_info_ok}
            {foreach from=$snsc_infos.prestashop_infos item=prestashop_info}
                {if in_array($prestashop_info.level, array('alert alert-warning', 'warn'))}
                    <ps-alert-warn caret="false">{$prestashop_info.message}</ps-alert-warn>
                {elseif in_array($prestashop_info.level, array('alert alert-danger', 'error'))}
                    <ps-alert-error caret="false">{$prestashop_info.message}</ps-alert-error>
                {else}
                    <ps-alert-hint caret="false">{$prestashop_info.message}</ps-alert-hint>
                {/if}
            {/foreach}
        {else}
            <ps-alert-success caret="false">
                {l s='Module configuration and integrity check passed successfully' mod='sonice_suivicolis'}
            </ps-alert-success>
        {/if}
    </div>
</div>

<div class="form-group">
    <label class="control-label col-lg-3">{l s='Additionnal support informations' mod='sonice_suivicolis'}</label>
    <div align="left" class="margin-form sol-info col-lg-9" >
        <button class="button btn btn-default" type="button" id="psinfo">Prestashop Info</button>&nbsp;&nbsp;&nbsp;<button class="button btn btn-default" type="button" id="phpinfo">PHP Info</button>
    </div>

    <label class="control-label col-lg-3">&nbsp;</label>
    <div align="left" class="margin-form sol-info col-lg-9" >

        <div class="col-lg-12" id="phpinfo_div" style="display: none;">
            {$snsc_infos.phpinfo_str} {* Escape already done in PHP *}
        </div>
        <div class="col-lg-12" id="psinfo_div" style="display: none;">
            {$snsc_infos.psinfo_str} {* Escape already done in PHP *}
            {$snsc_infos.dbinfo_str} {* Escape already done in PHP *}
        </div>
    </div>
</div>