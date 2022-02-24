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

<ps-form-group label="{l s='Export only products with price between' mod='fnac'}">
	<div class="form-group" style="padding-bottom:1px">
		<div class="col-xs-5">
			<input type="text" name="price_limiter[down]" value="{$fnac_filters.price_limiter.down|default:0|escape:'htmlall':'UTF-8'}" style="display: inline" />
		</div>
		<div class="col-xs-1 text-center">
			<label class="control-label" style="top:10px"><span>{l s='and' mod='fnac'}</span></label>
		</div>
		<div class="col-xs-5">
			<input type="text" name="price_limiter[up]" value="{$fnac_filters.price_limiter.up|default:10000|escape:'htmlall':'UTF-8'}" />
		</div>
		<div class="col-xs-1 text-center">
			<label class="control-label" style="top:10px"><span>&euro;</span></label>
		</div>
	</div>
</ps-form-group>