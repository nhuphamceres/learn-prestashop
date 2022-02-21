{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 * @package   CDiscount
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

<input type="hidden" id="id_lang" value="{$id_lang|escape:'htmlall':'UTF-8'}"/>
<input type="hidden" id="check_url" value="{$module_url|escape:'htmlall':'UTF-8'}functions/check.php?instant_token={$instant_token|escape:'htmlall':'UTF-8'}"/>

<script type="text/javascript" src="{$module_url|escape:'htmlall':'UTF-8'}views/js/cdiscount.js?version={$version|escape:'htmlall':'UTF-8'}"></script>
<script type="text/javascript" src="{$module_url|escape:'htmlall':'UTF-8'}views/js/html2canvas.min.js?version={$version|escape:'htmlall':'UTF-8'}"></script>
<script type="text/javascript" src="{$module_url|escape:'htmlall':'UTF-8'}views/js/information.js?version={$version|escape:'htmlall':'UTF-8'}"></script>

<link rel="stylesheet" type="text/css" href="{$module_url|escape:'htmlall':'UTF-8'}views/css/cdiscount.css?version={$version|escape:'htmlall':'UTF-8'}">

{if !$ps16x}
    <link rel="stylesheet" type="text/css" href="{$module_url|escape:'htmlall':'UTF-8'}views/css/chosen.min.css" />
    <script type="text/javascript" src="{$module_url|escape:'htmlall':'UTF-8'}views/js/chosen.jquery.min.js"></script>
{/if}

<!-- heading -->

<div class="header-logo">
	
    <img src="{$images_url|escape:'htmlall':'UTF-8'}common-services.png" title="{l s='Common-Services' mod='cdiscount'}"/>
    <img src="{$images_url|escape:'htmlall':'UTF-8'}cdiscount.png" title="{l s='CDiscount' mod='cdiscount'}"/>
</div>
<div style="clear:both;padding-bottom:60px;"></div>