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
 * @author    Alexandre D. & Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  contact@common-services.com
 *}

{if isset($PS16) && $PS16}
<div class="panel">
	<h3 class="tab"><img src="{$images|escape:'htmlall':'UTF-8'}fnac32.gif" alt=""/> Fnac MarketPlace</h3>

	<div class="form-group">
        <input type="hidden" name="fnac_option_lang[]" value="{$id_lang|intval}"/>
		<div class="col-lg-1"><span class="pull-right"></span></div>
		<label class="control-label col-lg-2">
			<span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="{l s='Check this box to make this product unavailable on Fnac' mod='fnac'}">
			{l s='Disabled' mod='fnac'}:
			</span>
		</label>
		<div class="col-lg-9">
			<div class="input-group">
                <span class="switch prestashop-switch fixed-width-lg">
					<input id="fnac-disable-{$id_lang|intval}_1" class="ps-switch" name="fnac-disable-{$id_lang|intval}" value="1" {if isset($force_unavailable.checked) && $force_unavailable.checked}checked="checked"{/if} type="radio">
                    <label for="fnac-disable-{$id_lang|intval}_1" class="radioCheck">{l s='Yes' mod='fnac'}</label>
                    <input id="fnac-disable-{$id_lang|intval}_0" class="ps-switch" name="fnac-disable-{$id_lang|intval}" value="0" type="radio" {if empty($force_unavailable.checked)}checked="checked"{/if}>
                    <label for="fnac-disable-{$id_lang|intval}_0" class="radioCheck">{l s='No' mod='fnac'}</label>
                    <a class="slide-button btn"></a></span>
            </div>
			<div class="help-block">
					{l s='Make all the products unavailable in this' mod='fnac'} :
					<a href="javascript:void(0)" class="sensitive px-0 fnac-propagate-disable-cat">[ {l s='Category' mod='fnac'} ]</a>
					<a href="javascript:void(0)" class="sensitive px-0 fnac-propagate-disable-shop">[ {l s='Shop' mod='fnac'} ]</a>
					<a href="javascript:void(0)" class="sensitive px-0 fnac-propagate-disable-manufacturer">[ {l s='Manufacturer' mod='fnac'} ]</a>
					
					<span id="fnac-extra-disable-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/></span>
			</div>
		</div>
	</div>
	
	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right"></span></div>
		<label class="control-label col-lg-2">
			<span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="{l s='The product will always appear on Fnac, even it\'s out of Stock' mod='fnac'}">
			{l s='Force in Stock' mod='fnac'}:
			</span>
		</label>
		<div class="col-lg-9">
			<div class="input-group">
                <span class="switch prestashop-switch fixed-width-lg">
					<input id="fnac-force-{$id_lang|intval}_1" class="ps-switch" name="fnac-force-{$id_lang|intval}" value="1" {if isset($force_in_stock.checked) && $force_in_stock.checked}checked="checked"{/if} type="radio">
                    <label for="fnac-force-{$id_lang|intval}_1">{l s='Yes' mod='fnac'}</label>
                    <input id="fnac-force-{$id_lang|intval}_0" class="ps-switch" name="fnac-force-{$id_lang|intval}" value="0" type="radio" {if empty($force_in_stock.checked)}checked="checked"{/if}>
                    <label for="fnac-force-{$id_lang|intval}_0">{l s='No' mod='fnac'}</label>
                    <a class="slide-button btn"></a></span></span>
            </div>
			<div class="help-block">
					{l s='Force as available in stock for all products in this' mod='fnac'} :
					<a href="javascript:void(0)" class="sensitive px-0 fnac-propagate-force-cat">[ {l s='Category' mod='fnac'} ]</a>
					<a href="javascript:void(0)" class="sensitive px-0 fnac-propagate-force-shop">[ {l s='Shop' mod='fnac'} ]</a>
					<a href="javascript:void(0)" class="sensitive px-0 fnac-propagate-force-manufacturer">[ {l s='Manufacturer' mod='fnac'} ]</a>

					<span id="fnac-extra-force-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/></span>
			</div>
		</div>
	</div>
	
	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right"></span></div>
		<label class="control-label col-lg-2">
			<span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="{l s='Short text which will appear on the product sheet on Fnac MarketPlace' mod='fnac'}">
			{l s='Extra Text' mod='fnac'}:
			</span>
		</label>
		<div class="col-lg-9">
			<div class="form-group input-group">
				<span class="input-group-addon"><img src="{$images|escape:'htmlall':'UTF-8'}fr.gif" alt="FR"/></span>
				<input type="text" name="fnac-text-{$id_lang|intval}" class="form-control" value="{$extra_text.value|escape:'htmlall':'UTF-8'}">
			</div>
			<div class="form-group input-group">
				<span class="input-group-addon"><img src="{$images|escape:'htmlall':'UTF-8'}es.gif" alt="ES"/></span>
				<input type="text" name="fnac-text-es-{$id_lang|intval}" class="form-control" value="{$extra_text.value_es|escape:'htmlall':'UTF-8'}">
			</div>
			<div class="form-group input-group">
				<span class="input-group-addon"><img src="{$images|escape:'htmlall':'UTF-8'}pt.png" alt="PT"/></span>
				<input type="text" name="fnac-text-pt-{$id_lang|intval}" class="form-control" value="{$extra_text.value_pt|escape:'htmlall':'UTF-8'}">
			</div>
			<div class="help-block">
					{l s='Force short text for' mod='fnac'} :
                <a href="javascript:void(0)" class="sensitive px-0 fnac-propagate-text-cat">[ {l s='Category' mod='fnac'} ]</a>
                <a href="javascript:void(0)" class="sensitive px-0 fnac-propagate-text-shop">[ {l s='Shop' mod='fnac'} ]</a>
                <a href="javascript:void(0)" class="sensitive px-0 fnac-propagate-text-manufacturer">[ {l s='Manufacturer' mod='fnac'} ]</a>

                <span id="fnac-extra-text-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/></span>
			</div>
		</div>
	</div>
	
	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right"></span></div>
		<label class="control-label col-lg-2" for="fnac-price-{$id_lang|intval}">
			<span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="{l s='Net Price for Fnac Marketplace. This value will override your Shop Price' mod='fnac'}">
			{l s='Price Override' mod='fnac'}:
			</span>
		</label>
		<div class="col-lg-2">
			<div class="input-group">
				<input type="text" name="fnac-price-{$id_lang|intval}" id="fnac-price-{$id_lang|intval}" class="form-control" value="{$extra_price.value|escape:'htmlall':'UTF-8'}" />
			</div>
		</div>
	</div>
	
	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right"></span></div>
		<label class="control-label col-lg-2" for="fnac-time-{$id_lang|intval}">
			<span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="{l s='The default time to ship is 21 days. For some products, this delay is too short. If your account is authorized, you can overload this time.' mod='fnac'}">
			{l s='Time to ship' mod='fnac'}:
			</span>
		</label>
		<div class="col-lg-2">
			<div class="input-group">
				<input type="text" name="fnac-time-{$id_lang|intval}" id="fnac-time-{$id_lang|intval}" class="form-control" value="{$time_to_ship|default:''}" />
			</div>
		</div>
	</div>
	
    <div class="form-group text-center">
        <hr style="width:50%"/>
        <span style="color:brown;font-weight:bold;font-size:0.8em">{l s='Don\'t forget to click on the record button linked to this sub-tab if you modify this configuration !' mod='fnac'}</span>
    </div>

    <div class="form-group text-right">
        <div class="conf module_confirmation confirm alert alert-success" style="display:none" id="result-fnac"></div>
        <input id="productfnac-save-options" type="button" class="btn btn-primary" value="{l s='Save Fnac MarketPlace Parameters' mod='fnac'}" />
    </div>

    <input type="hidden" id="fnac-product-options-json-url" value="{$module_url|escape:'htmlall':'UTF-8'}functions/product_ext.json.php"/>
    <input type="hidden" id="fnac-text-propagate-cat"
           value="{l s='Be carefull ! Are you sure to want set this value for all the products of this Category ?' mod='fnac'}"/>
    <input type="hidden" id="fnac-text-propagate-shop"
           value="{l s='Be carefull ! Are you sure to want to set this value for all the products of the entire Shop ?' mod='fnac'}"/>
    <input type="hidden" id="fnac-text-propagate-man"
           value="{l s='Be carefull ! Are you sure to want to set this value for all the products for this Manufacturer ?' mod='fnac'}"/>

    <script>
		function ready(fn) {
		  if (document.readyState != 'loading'){
			fn();
		  } else if (document.addEventListener) {
			document.addEventListener('DOMContentLoaded', fn);
		  } else {
			document.attachEvent('onreadystatechange', function() {
			  if (document.readyState != 'loading')
				fn();
			});
		  }
		}
        function fn(){
            $(".helplabel-tooltip").tooltip();
        };
		ready(fn);
    </script>
</div>
{/if}

{if isset($PS17) && $PS17}

    {*<div class="form-group">
    <span id="productfnac-options"><img src="{$images|escape:'htmlall':'UTF-8'}fnac32.gif" alt=""/>&nbsp;&nbsp;<b>Fnac MarketPlace</b>&nbsp;&nbsp;&nbsp;<span
    style="color:grey">[</span><img src="{$module_url|escape:'htmlall':'UTF-8'}views/img/plus.png"
    rel="{$module_url|escape:'htmlall':'UTF-8'}views/img/minus.png" alt=""
    style="position:relative;top:-1px;" id="fnac-toggle-img"/><span
    style="color:grey;margin-left:-1px;">]</span></span>
    </div>

    <div class="fnac-details">*}

    <div class="form-group row">
        <input type="hidden" name="fnac_option_lang[]" value="{$id_lang|intval}"/>
        <label class="form-control-label ">
            {l s='Disabled' mod='fnac'}:
            <span class="help-box" data-toggle="popover" data-content="{l s='Check this box to make this product unavailable on Fnac' mod='fnac'}" data-original-title="" title=""></span>
        </label>
        <div class="col-sm">
            <div class="input-group">
                <span class="ps-switch">
                    <input id="fnac-disable-{$id_lang|intval}_0" class="ps-switch" name="fnac-disable-{$id_lang|intval}" value="0" type="radio" {if empty($force_unavailable.checked)}checked="checked"{/if}>
                    <label for="fnac-disable-{$id_lang|intval}_0">{l s='No' mod='fnac'}</label>
                    <input id="fnac-disable-{$id_lang|intval}_1" class="ps-switch" name="fnac-disable-{$id_lang|intval}" value="1" {if isset($force_unavailable.checked) && $force_unavailable.checked}checked="checked"{/if} type="radio">
                    <label for="fnac-disable-{$id_lang|intval}_1">{l s='Yes' mod='fnac'}</label>
                    <span class="slide-button"></span></span>
            </div>
            <span class="small font-secondary">
                {l s='Make all the products unavailable in this' mod='fnac'} :
                <a href="javascript:void(0)" class="btn sensitive px-0 fnac-propagate-disable-cat">[ {l s='Category' mod='fnac'} ]</a>
                <a href="javascript:void(0)" class="btn sensitive px-0 fnac-propagate-disable-shop">[ {l s='Shop' mod='fnac'} ]</a>
                <a href="javascript:void(0)" class="btn sensitive px-0 fnac-propagate-disable-manufacturer">[ {l s='Manufacturer' mod='fnac'} ]</a>

                <span id="fnac-extra-disable-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/></span>
            </span>
        </div>
    </div>

    <div class="form-group row">
        <label class="form-control-label ">
            {l s='Force in Stock' mod='fnac'}:
            <span class="help-box" data-toggle="popover" data-content="{l s='The product will always appear on Fnac, even it\'s out of Stock' mod='fnac'}" data-original-title="" title=""></span>
        </label>
        <div class="col-sm">
            <div class="input-group">
                <span class="ps-switch">
                    <input id="fnac-force-{$id_lang|intval}_0" class="ps-switch" name="fnac-force-{$id_lang|intval}" value="0" type="radio" {if empty($force_in_stock.checked)}checked="checked"{/if}>
                    <label for="fnac-force-{$id_lang|intval}_0">{l s='No' mod='fnac'}</label>
                    <input id="fnac-force-{$id_lang|intval}_1" class="ps-switch" name="fnac-force-{$id_lang|intval}" value="1" {if isset($force_in_stock.checked) && $force_in_stock.checked}checked="checked"{/if} type="radio">
                    <label for="fnac-force-{$id_lang|intval}_1">{l s='Yes' mod='fnac'}</label>
                    <span class="slide-button"></span></span>
            </div>
            <span class="small font-secondary">
                {l s='Force as available in stock for all products in this' mod='fnac'} :
                <a href="javascript:void(0)" class="btn sensitive px-0 fnac-propagate-force-cat">[ {l s='Category' mod='fnac'} ]</a>
                <a href="javascript:void(0)" class="btn sensitive px-0 fnac-propagate-force-shop">[ {l s='Shop' mod='fnac'} ]</a>
                <a href="javascript:void(0)" class="btn sensitive px-0 fnac-propagate-force-manufacturer">[ {l s='Manufacturer' mod='fnac'} ]</a>

                <span id="fnac-extra-force-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/></span>
            </span>
        </div>
    </div>

    <div class="form-group row">
        <label class="form-control-label ">
            {l s='Extra Text' mod='fnac'}:
            <span class="help-box" data-toggle="popover" data-content="{l s='Short text which will appear on the product sheet on Fnac MarketPlace' mod='fnac'}" data-original-title="" title=""></span>
        </label>
        <div class="col-sm">
            <div class="form-group input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><img src="{$images|escape:'htmlall':'UTF-8'}fr.gif" alt="FR"/> </span>
                </div>
                <input type="text" name="fnac-text-{$id_lang|intval}" class="form-control" value="{$extra_text.value|escape:'htmlall':'UTF-8'}">
            </div>
            <div class="form-group input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><img src="{$images|escape:'htmlall':'UTF-8'}es.gif" alt="ES"/> </span>
                </div>
                <input type="text" name="fnac-text-es-{$id_lang|intval}" class="form-control" value="{$extra_text.value_es|escape:'htmlall':'UTF-8'}">
            </div>
            <div class="form-group input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><img src="{$images|escape:'htmlall':'UTF-8'}pt.png" alt="PT"/> </span>
                </div>
                <input type="text" name="fnac-text-pt-{$id_lang|intval}" class="form-control" value="{$extra_text.value_pt|escape:'htmlall':'UTF-8'}">
            </div>
            <span class="small font-secondary">
                {l s='Force short text for' mod='fnac'} :
                <a href="javascript:void(0)" class="btn sensitive px-0 fnac-propagate-text-cat">[ {l s='Category' mod='fnac'} ]</a>
                <a href="javascript:void(0)" class="btn sensitive px-0 fnac-propagate-text-shop">[ {l s='Shop' mod='fnac'} ]</a>
                <a href="javascript:void(0)" class="btn sensitive px-0 fnac-propagate-text-manufacturer">[ {l s='Manufacturer' mod='fnac'} ]</a>

                <span id="fnac-extra-text-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/></span>
            </span>
        </div>
    </div>

    <div class="form-group row">
        <label class="form-control-label" for="fnac-price-{$id_lang|intval}">
            {l s='Price Override' mod='fnac'}:
            <span class="help-box" data-toggle="popover" data-content="{l s='Net Price for Fnac Marketplace. This value will override your Shop Price' mod='fnac'}" data-original-title="" title=""></span>
        </label>
        <div class="col-sm">
            <div class="form-group row">
                <div class="col-xs-2 col-md-3">
                    <input type="text" name="fnac-price-{$id_lang|intval}" id="fnac-price-{$id_lang|intval}" class="form-control" value="{$extra_price.value|escape:'htmlall':'UTF-8'}">
                </div>
            </div>
        </div>
    </div>

    <div class="form-group row">
        <label class="form-control-label" for="fnac-time-{$id_lang|intval}">
            {l s='Time to ship' mod='fnac'}:
            <span class="help-box" data-toggle="popover" data-content="{l s='The default time to ship is 21 days. For some products, this delay is too short. If your account is authorized, you can overload this time.' mod='fnac'}" data-original-title="" title=""></span>
        </label>
        <div class="col-sm">
            <div class="form-group row">
                <div class="col-xs-2 col-md-3">
                    <input type="text" name="fnac-time-{$id_lang|intval}" id="fnac-time-{$id_lang|intval}" class="form-control" value="{$time_to_ship|default:''}">
                </div>
            </div>
        </div>
    </div>

    <div class="form-group text-center">
        <hr style="width:50%"/>
        <span style="color:brown;font-weight:bold;font-size:0.8em">{l s='Don\'t forget to click on the record button linked to this sub-tab if you modify this configuration !' mod='fnac'}</span>
    </div>

    <div class="form-group text-right">
        <div class="conf module_confirmation confirm alert alert-success" style="display:none" id="result-fnac"></div>
        <input id="productfnac-save-options" type="button" class="btn btn-primary" value="{l s='Save Fnac MarketPlace Parameters' mod='fnac'}" />
    </div>

    {*</div>*}

    <input type="hidden" id="fnac-product-options-json-url" value="{$module_url|escape:'htmlall':'UTF-8'}functions/product_ext.json.php"/>
    <input type="hidden" id="fnac-text-propagate-cat"
           value="{l s='Be carefull ! Are you sure to want set this value for all the products of this Category ?' mod='fnac'}"/>
    <input type="hidden" id="fnac-text-propagate-shop"
           value="{l s='Be carefull ! Are you sure to want to set this value for all the products of the entire Shop ?' mod='fnac'}"/>
    <input type="hidden" id="fnac-text-propagate-man"
           value="{l s='Be carefull ! Are you sure to want to set this value for all the products for this Manufacturer ?' mod='fnac'}"/>

    <script>
        $(document).ready(function () {
            $(".help-box").popover();
        });
    </script>

{/if}