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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
 *
 * Show Available at Amazon in front shop > product detail page
*}

<div style="{if $ps16x && !$ps17x}text-align: center;{/if} {if !$ps15x}padding-top: 7px; padding-bottom: 7px;{/if}">
    <a href="{$product_link|escape:'htmlall':'UTF-8'}" target="_blank">
        <img src="{$images_url|escape:'htmlall':'UTF-8'|cat:'available.png'}" alt="{l s='Available at Amazon' mod='amazon'}"
             style="width: 145px; {if $ps15x}padding-right: 15px;{/if}">
    </a>
</div>
