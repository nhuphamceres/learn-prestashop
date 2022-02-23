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
 * ...........................................................................
 *
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 *}

<div id="conf-informations" style="display: none;">
    <h2>{l s='Configuration Check' mod='sonice_suivicolis'}</h2>
    <br>

    <div class="form-group">
        <label class="control-label col-lg-3">So Nice Suivi de Colis</label>
        <div class="margin-form snsc_typo col-lg-9">
            {if !$snsc_infos.module_info_ok}
                {foreach from=$snsc_infos.module_infos item=module_info}
                    <div class="{$module_info.level|escape:'htmlall':'UTF-8'}">
                        {$module_info.message|escape:'htmlall':'UTF-8'}
                    </div>
                {/foreach}
            {else}
                <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}">
                    {l s='Module configuration and integrity check passed successfully' mod='sonice_suivicolis'}
                </div>
            {/if}        
        </div>
    </div>

	<div class="form-group">
		<label class="control-label col-lg-3">{l s='PHP Settings' mod='sonice_suivicolis'}</label>
		<div class="margin-form snsc_typo col-lg-9">
			{if !$snsc_infos.php_info_ok}
				{foreach from=$snsc_infos.php_infos item=php_info}
					<div class="{$php_info.level|escape:'htmlall':'UTF-8'}">
						{$php_info.message|escape:'htmlall':'UTF-8'}
					</div>
				{/foreach}
			{else}
				<div class="{$alert_class.success|escape:'htmlall':'UTF-8'}">
					{l s='Module configuration and integrity check passed successfully' mod='sonice_suivicolis'}
				</div>
			{/if}
		</div>
	</div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Prestashop Settings' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo col-lg-9">
            {if !$snsc_infos.prestashop_info_ok}
                {foreach from=$snsc_infos.prestashop_infos item=prestashop_info}
                    <div class="{$prestashop_info.level|escape:'htmlall':'UTF-8'}">
                        {$prestashop_info.message|escape:'htmlall':'UTF-8'}
                    </div>
                {/foreach}
            {else}
                <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}">
                    {l s='Module configuration and integrity check passed successfully' mod='sonice_suivicolis'}
                </div>
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
</div>