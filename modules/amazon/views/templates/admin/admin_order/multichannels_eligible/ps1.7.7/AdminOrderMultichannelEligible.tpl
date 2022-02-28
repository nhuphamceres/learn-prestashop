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

<div class="card mt-2">
    <div class="card-header">
        <h3 class="card-header-title">
            {include file=$template_path|cat:'header.tpl'
            images_url=$images_url marketplace_flag=$marketplace_flag marketplace_region=$marketplace_region}
        </h3>
    </div>
    <div class="card-body">
        {include file=$template_path|cat:'multichannels_eligible/admin_order_multichannels_eligible_body.tpl'}
    </div>
</div>
