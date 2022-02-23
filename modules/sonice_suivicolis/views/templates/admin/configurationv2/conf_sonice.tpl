{**
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from Common-Services Co., Ltd.
* Use, copy, modification or distribution of this source file without written
* license agreement from the SARL SMC is strictly forbidden.
* In order to obtain a license, please contact us: support.mondialrelay@common-services.com
* ...........................................................................
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe Common-Services Co., Ltd.
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: support.mondialrelay@common-services.com
* ...........................................................................
*
* @package   sonice_suivicolis
* @author    debuss-a
* @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license   Commercial license
* Support by mail  :  support.sonice_suivicolis@common-services.com
*}

<ps-panel {*header="SoNice Suivi v{$snsc_module_version|escape:'htmlall':'UTF-8'}"*}>
    <h2>SoNice Suivi
        <small>v{$snsc_module_version|escape:'htmlall':'UTF-8'}</small>
    </h2>
    <p>{$snsc_module_description|escape:'htmlall':'UTF-8'}</p>
</ps-panel>

<div class="row">

    <div class="col-lg-6">
        <ps-panel header="{l s='Informations' mod='sonice_suivicolis'}">
            <ul>
                <li>
                    <p class="text-navy">
                        {l s='Provided by' mod='sonice_suivicolis'} :
                        <a href="http://blog.common-services.com/" target="_blank">Common-Services</a>
                    </p>
                </li>
                <li>
                    <p class="text-navy">
                        {l s='Informations, follow up on our blog' mod='sonice_suivicolis'} :<br>
                        <a href="http://www.common-services.com" target="_blank">http://www.common-services.com</a>
                    </p>
                </li>
                <li>
                    <p class="text-navy">
                        {l s='More informations about us on Prestashop website' mod='sonice_suivicolis'} :<br><br>
                        <a href="https://www.prestashop.com/en/experts/module-creators/common-services" target="_blank">
                            {*https://www.prestashop.com/en/experts/module-creators/common-services*}
                            <img src="{$snsc_img_dir}prestashop.svg" alt="PrestaShop Addons">
                        </a>
                    </p>
                </li>
                <li>
                    <p class="text-navy">
                        {l s='You will appreciate our others modules' mod='sonice_suivicolis'} :<br><br>
                        <a href="http://addons.prestashop.com/fr/58_common-services" target="_blank">
                            {*http://addons.prestashop.com/fr/58_common-services*}
                            <img src="{$snsc_img_dir}addons.png" alt="PrestaShop Addons">
                        </a>
                    </p>
                </li>
            </ul>
        </ps-panel>
    </div>

    <div class="col-lg-6">
        <ps-panel header="{l s='Documentation' mod='sonice_suivicolis'}">
            <div>
                <p>
                    <span class="text-red-bold">{l s='Please first read the provided documentation' mod='sonice_suivicolis'} :</span>
                    <a href="{$snsc_url}documentation/readme_fr.pdf" target="_blank">readme_fr.pdf</a>
                </p>
            </div>
        </ps-panel>

        <ps-panel header="{l s='Support' mod='sonice_suivicolis'}">
            <div>
                <p class="text-red-bold">
                    {l s='The technical support is available by e-mail only.' mod='sonice_suivicolis'}
                </p>
                <p class="text-navy">
                    {l s='For any support, please provide us' mod='sonice_suivicolis'} :
                </p>
                <ul>
                    <li>{l s='A detailled description of the issue or encountered problem' mod='sonice_suivicolis'}</li>
                    <li>{l s='Your Pretashop Addons Order ID available in your Prestashop Addons order history' mod='sonice_suivicolis'}</li>
                    <li>{l s='Your Prestashop version' mod='sonice_suivicolis'} :
                        <span style="color: red;">Prestashop {$smarty.const._PS_VERSION_}</span></li>
                    <li>{l s='Your module version' mod='sonice_suivicolis'} : <span style="color: red;">SoNice Suivi de Colis v{$snsc_module_version|escape:'htmlall':'UTF-8'}</span>
                    </li>
                </ul>
                <br>
                <span class="text-navy">Support Common-Services :</span>
                <a href="mailto:contact@common-services.com?subject={l s='Support SoNice Suivi de Colis' mod='sonice_suivicolis'}&body={l s='Dear Support, I am currently having some trouble with your module v%s on my Prestashop v%s.' sprintf=[$snsc_module_version, $smarty.const._PS_VERSION_] mod='sonice_suivicolis'}" title="Email">
                    contact@common-services.com
                </a>
            </div>

            <div class="clearfix"></div>
        </ps-panel>
    </div>
</div>

<div class="col-lg-12">
    <div class="row">
        <ps-panel header="{l s='Licence' mod='sonice_suivicolis'}">
            <p>
                {l s='This add-on is under a commercial licence from S.A.R.L. SMC' mod='sonice_suivicolis'}.<br>
                {l s='In case of purchase on Prestashop Addons, the invoice is the final proof of license.' mod='sonice_suivicolis'}
                <br>
                {l s='Contact us to obtain a license only in other cases' mod='sonice_suivicolis'} :
                <a href="mailto:contact@common-services.com">contact@common-services.com</a>
            </p>
        </ps-panel>
    </div>
</div>