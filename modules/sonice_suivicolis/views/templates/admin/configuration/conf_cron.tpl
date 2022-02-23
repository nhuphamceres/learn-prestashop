{* NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 * ...........................................................................
 * @package    SO COLISSIMO
 * @copyright  Copyright(c) 2010-2013 S.A.R.L S.M.C - http://www.common-services.com
 * @author     Olivier B. / Debusschere A.
 * @license    Commercial license
 * Contact by Email :  olivier@common-services.com
 * Contact on Prestashop forum : delete (Olivier B)
 * Skype : delete13_fr (please prefer email)
 *}

<div id="conf-cron" style="display: none;">
	<h2>{l s='Cron task' mod='sonice_suivicolis'}</h2>

	<div class="cleaner">&nbsp;</div>

	<div class="form-group">
		<div class="margin-form col-lg-offset-3">
			<div id="cronjobs_success" class="{$alert_class.success|escape:'htmlall':'UTF-8'}" style="display:none;">
			</div>

			<div id="cronjobs_error" class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" style="display:none;">
			</div>
		</div>
	</div>

	<div class="form-group">
		<div class="margin-form col-lg-offset-3">
			<div class="cron-mode" rel="prestashop-cron">
				<img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}/prestashop-cronjobs-icon.png"
					 title="{l s='Prestashop Cronjobs (Module)' mod='sonice_suivicolis'}"/>
				<h4>{l s='Prestashop Cronjobs (Module)' mod='sonice_suivicolis'}</h4>

				<div style="float:right" class="cron-prestashop">
					{if $snsc_cron.installed}
						<span style="color:green">{l s='Installed' mod='sonice_suivicolis'}</span>
					{elseif $snsc_cron.exists}
						<span style="color:red">{l s='Detected, Not installed' mod='sonice_suivicolis'}</span>
					{else}
						<span style="color:red">{l s='Not detected' mod='sonice_suivicolis'}</span>
					{/if}
				</div>
			</div>

		</div>
	</div>

	<div id="prestashop-cron" class="cron-toggle" {if !$snsc_cron.installed}style="display:none"{/if} >
		<div class="form-group">
			<div class="margin-form col-lg-offset-3">
				{if !$snsc_cron.installed}
					<div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}">{l s='Prestashop Cronjobs is not installed.' mod='sonice_suivicolis'}
						{if !$snsc_cron.exists}
							<br>
							(
							<a href="https://github.com/PrestaShop/cronjobs/archive/master.zip"
							   target="_blank">https://github.com/PrestaShop/cronjobs</a>
							)
						{/if}
					</div>
				{else}
					<span class="title">{l s='Those lines will be added in Prestashop Cronjobs module' mod='sonice_suivicolis'}:</span>
					<div id="prestashop-cronjobs-lines">
						<b>{l s='Update Package Tracking' mod='sonice_suivicolis'}</b>
						: {l s='each' mod='sonice_suivicolis'} {$snsc_cron.frequency|escape:'htmlall':'UTF-8'} {l s='hours' mod='sonice_suivicolis'}
						, {l s='url' mod='sonice_suivicolis'} :
						<a href="{$snsc_cron_task|escape:'htmlall':'UTF-8'}" target="_blank">{$snsc_cron_task|escape:'htmlall':'UTF-8'}</a>
						<br><br>
						<b>{l s='Send mail notification' mod='sonice_suivicolis'}</b>
						: {l s='each' mod='sonice_suivicolis'} {$snsc_cron.frequency|escape:'htmlall':'UTF-8'} {l s='hours' mod='sonice_suivicolis'}
						, {l s='url' mod='sonice_suivicolis'} :
						<a href="{$snsc_cron_task_mail|escape:'htmlall':'UTF-8'}" target="_blank">{$snsc_cron_task_mail|escape:'htmlall':'UTF-8'}</a>
						<br><br>
						<b>{l s='Send mail incentive' mod='sonice_suivicolis'}</b>
						: {l s='each' mod='sonice_suivicolis'} {$snsc_cron.frequency|escape:'htmlall':'UTF-8'} {l s='hours' mod='sonice_suivicolis'}
						, {l s='url' mod='sonice_suivicolis'} :
						<a href="{$snsc_incentive_cron_task|escape:'htmlall':'UTF-8'}" target="_blank">{$snsc_incentive_cron_task|escape:'htmlall':'UTF-8'}</a>
					</div>
					<textarea id="prestashop-cronjobs-params" name="prestashop-cronjobs-params" style="display:none">
						SoNice_SuiviColis|{$snsc_cron.frequency|escape:'htmlall':'UTF-8'}|{$snsc_cron_task|escape:'htmlall':'UTF-8'}!SoNice_SuiviColis Email|{$snsc_cron.frequency|escape:'htmlall':'UTF-8'}|{$snsc_cron_task_mail|escape:'htmlall':'UTF-8'}!SoNice_SuiviColis Incentive|{$snsc_cron.frequency|escape:'htmlall':'UTF-8'}|{$snsc_incentive_cron_task|escape:'htmlall':'UTF-8'}
					</textarea>
					<br/>
					{if $snsc_cron.installed}
						<span style="color:green">{l s='Click on install/update button to auto-configure your Prestashop cronjobs module' mod='sonice_suivicolis'}
							:</span>
						<button class="button btn btn-default" style="float:right" id="install-cronjobs">
							<img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}plus.png" alt="+" style="width:11px;"/>&nbsp;&nbsp;
							{l s='Install / Update' mod='sonice_suivicolis'}
						</button>
						<img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}loader-connection.gif" alt="" id="cronjob-loader"/>
					{/if}
				{/if}
			</div>
		</div>
	</div>

	<div class="form-group">
		<div class="margin-form col-lg-offset-3">
			<div class="cron-mode" rel="manual-cron">
				<img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}/terminal.png" title="{l s='Manual Cron URL' mod='sonice_suivicolis'}"/>
				<h4>{l s='Manual Cron URLs' mod='sonice_suivicolis'}</h4>
			</div>
		</div>
	</div>

	<div id="manual-cron" class="cron-toggle" {if $snsc_cron.installed}style="display:none"{/if}>
		<div class="form-group">
			<label class="control-label col-lg-3"></label>

			<div class="margin-form col-lg-9">
				<input type="text" class="cron_task" value="{$snsc_cron_task|escape:'htmlall':'UTF-8'}">
				<span class="so-warning">{l s='This URL allow you to configure your CRON task in order to update your parcel status automatically.' mod='sonice_suivicolis'}</span>
			</div>

			<label class="control-label col-lg-3"></label>

			<div class="margin-form col-lg-9">
				<input type="text" class="cron_task" value="{$snsc_cron_task_mail|escape:'htmlall':'UTF-8'}">
				<span class="so-warning">{l s='This URL allow you to configure your CRON task in order to send notification to your customer automatically.' mod='sonice_suivicolis'}</span>
			</div>

			<label class="control-label col-lg-3"></label>

			<div class="margin-form col-lg-9">
				<input type="text" class="cron_task" value="{$snsc_incentive_cron_task|escape:'htmlall':'UTF-8'}">
				<span class="so-warning">{l s='This URL allow you to configure your CRON task in order to ask incentive from your customer automatically.' mod='sonice_suivicolis'}</span>
			</div>
		</div>
	</div>
</div>