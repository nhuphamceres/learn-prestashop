{**
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
 * @package   Amazon Market Place
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
*}

{include file=$template_path|cat:'multichannels_eligible/admin_order_multichannels_eligible_including.tpl'}

{if $ps_version_is_16}
<div class="col-lg-7">
    <div class="panel">
        <fieldset id="amazon-order-ps16">
            {elseif ps_version_is_15}
            <fieldset id="amazon-order-ps15" style="margin-top:10px;">
                {else}
                <fieldset id="amazon-order-ps14" style="width:400px;margin-top:10px;">
                    {/if}
                    <legend style="font-size:14px;padding-bottom: 5px;">
                        {include file=$template_path|cat:'header.tpl'
                        images_url=$images_url marketplace_flag=$marketplace_flag marketplace_region=$marketplace_region}
                    </legend>

                    {include file=$template_path|cat:'multichannels_eligible/admin_order_multichannels_eligible_body.tpl'}
                </fieldset>
                {if $ps_version_is_16}
    </div>
</div>
{/if}
