{** NOTICE OF LICENSE
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
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 * ...........................................................................
 * @package    CommonServices
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @author     Tran Pham
 *}

{if !$below16}<div class="bootstrap">{/if}
    <div class="{$class|escape:'htmlall':'UTF-8'}">
        {if $message|is_string}
            {$message|escape:'htmlall':'UTF-8'}
        {else}
            <ul>
                {foreach from=$message item="msg"}
                    <li>{$msg|escape:'htmlall':'UTF-8'}</li>
                {/foreach}
            </ul>
        {/if}
    </div>
{if !$below16}</div>{/if}
