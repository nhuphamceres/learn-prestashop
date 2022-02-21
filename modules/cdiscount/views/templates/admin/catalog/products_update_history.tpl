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

<div id="history-history">
    {if isset($xmlfile) && isset($zipfile)}
        <h4>{l s='Resources' mod='cdiscount'}</h4>
        <ul id="batch_ressources">

            <li>{l s='XML File' mod='cdiscount'}: {$xmlfile|escape:'htmlall':'UTF-8'} - URL: <a
                        href="{$xmlurl|escape:'htmlall':'UTF-8'}" target="_blank">{$xmlurl|escape:'htmlall':'UTF-8'}</a>
            </li>
            <li>{l s='ZIP File' mod='cdiscount'}: {$zipfile|escape:'htmlall':'UTF-8'} - URL: <a
                        href="{$zipurl|escape:'htmlall':'UTF-8'}" target="_blank">{$zipurl|escape:'htmlall':'UTF-8'}</a>
            </li>
        </ul>
    {/if}

    {if count($batches)}
        <h4>{l s='Batch History' mod='cdiscount'}</h4>
        <table id="batch_history">
            <thead>
            <tr>
                <td>{l s='Start' mod='cdiscount'}</td>
                <td>{l s='Stop' mod='cdiscount'}</td>
                <td>{l s='Duration' mod='cdiscount'}</td>
                <td>{l s='Records' mod='cdiscount'}</td>
                <td>{l s='Type' mod='cdiscount'}</td>
                <td>{l s='Id' mod='cdiscount'}</td>
            </tr>
            </thead>
            <tbody>
            {foreach from=$batches item=batch}
                <tr>
                    <td>{$batch.timestart|escape:'htmlall':'UTF-8'}</td>
                    <td>{$batch.timestop|escape:'htmlall':'UTF-8'}</td>
                    <td>{$batch.duration|escape:'htmlall':'UTF-8'}</td>
                    <td>{$batch.records|escape:'htmlall':'UTF-8'}</td>
                    <td>{$batch.type|escape:'htmlall':'UTF-8'}</td>
                    <td {if $batch.hasid}class="reportid"{/if}>{$batch.id|escape:'htmlall':'UTF-8'}
                        {if $batch.hasid}
                            <img src="{$images|escape:'htmlall':'UTF-8'}magnifier_medium.png" alt="" />
                        {/if}
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    {/if}
    <div id="history-loader"></div>
</div>

<div id="history-report-content" style="display:none">
    <h4>{l s='Report Preview' mod='cdiscount'}</h4>    
    <pre>
    </pre>
</div>