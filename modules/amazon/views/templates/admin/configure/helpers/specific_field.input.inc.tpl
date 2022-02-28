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
{if isset($data.comment)}
    <!-- {$data.comment|escape:'quotes':'UTF-8'} -->
{/if}
<input type="text" name="{$data.name|escape:'quotes':'UTF-8'}"
       {if isset($data.placeholder)}placeholder="{$data.placeholder|escape:'quotes':'UTF-8'}"{/if}
        {if isset($data.class)}class="{$data.class|escape:'quotes':'UTF-8'}"{/if}
        {if isset($data.style)}style="{$data.style|escape:'quotes':'UTF-8'}"{/if}
        {if isset($data.rel)}rel="{$data.rel|escape:'quotes':'UTF-8'}"{/if}
       value="{$data.value|escape:'quotes':'UTF-8'}"/>