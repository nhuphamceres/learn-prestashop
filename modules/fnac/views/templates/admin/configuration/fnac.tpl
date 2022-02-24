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

<ps-panel>
    <div class="col-lg-6">
        <h2>
            {$module_name}
            <small>v{$module_version}</small>
        </h2>
        <p>{$module_description}</p>
    </div>
    <div class="col-lg-6">
        <img src="{$module_img_dir}fnac.png" class="img-responsive pull-right" width="60px">
    </div>
    <div class="clearfix">&nbsp;</div>
</ps-panel>

<div class="row">
    <div class="col-lg-6">
        <ps-panel header="{l s='Informations' mod='fnac'}">
            <ul>
                <li>
                    <p class="text-navy">
                        {l s='Provided by' mod='fnac'} :<br>
                        <a href="http://blog.common-services.com/" target="_blank">
                            <img src="{$module_img_dir}cs_logo.png">
                        </a>
                    </p>
                    <p class="text-navy">
                        <small>
                            {l s='Informations, follow up on our blog' mod='fnac'} :
                            <a href="http://blog.common-services.com" target="_blank">http://blog.common-services.com</a>
                        </small>
                    </p>
                </li>
                <li>
                    <p class="text-navy">
                        {l s='More informations about us on Prestashop website' mod='fnac'} :<br><br>
                        <a href="https://www.prestashop.com/en/experts/module-creators/common-services" target="_blank">
                            <img src="{$module_img_dir}prestashop.svg" alt="PrestaShop Addons">
                        </a>
                    </p>
                </li>
                <li>
                    <p class="text-navy">
                        {l s='You will appreciate our others modules' mod='fnac'} :<br><br>
                        <a href="http://addons.prestashop.com/fr/58_common-services" target="_blank">
                            <img src="{$module_img_dir}addons.png" alt="PrestaShop Addons">
                        </a>
                    </p>
                </li>
            </ul>
        </ps-panel>
    </div>

    <div class="col-lg-6">
        <ps-panel header="{l s='Documentation' mod='fnac'}">
            <div>
                <p>
                    <span class="text-red-bold">{l s='Please first read the provided documentation' mod='fnac'} :</span>
                    <a href="{$module_url}documentation/readme_fr.pdf" target="_blank">readme_fr.pdf</a>
                </p>
            </div>
        </ps-panel>

        <ps-panel header="{l s='Support' mod='fnac'}">
            <div>
                <p class="text-red-bold">
                    {l s='The technical support is available by e-mail only.' mod='fnac'}
                </p>
                <p class="text-navy">
                    {l s='For any support, please provide us' mod='fnac'} :
                </p>
                <ul>
                    <li>{l s='A detailled description of the issue or encountered problem' mod='fnac'}</li>
                    <li>{l s='Your Pretashop Addons Order ID available in your Prestashop Addons order history' mod='fnac'}</li>
                    <li>{l s='Your Prestashop version' mod='fnac'} :
                        <span style="color: red;">Prestashop {$smarty.const._PS_VERSION_}</span></li>
                    <li>{l s='Your module version' mod='fnac'} : <span style="color: red;">{$module_name} v{$module_version}</span>
                    </li>
                </ul>
                <br>
                <p>
                    Support Common-Services :
                    <a href="mailto:contact@common-services.com?subject={l s='Support Fnac' mod='fnac'}&body={l s='Dear Support, I am currently having some trouble with your module v%s on my Prestashop v%s.' sprintf=[$module_version, $smarty.const._PS_VERSION_] mod='fnac'}" title="Email">
                        support.fnac@common-services.com
                    </a>
                </p>
            </div>

            <div class="clearfix"></div>
        </ps-panel>
    </div>
</div>

<div class="col-lg-12">
    <div class="row">
        <ps-panel header="{l s='License' mod='fnac'}">
            <p>
                {l s='This add-on is under a commercial licence from Common-Services' mod='fnac'}.<br>
                {l s='In case of purchase on Prestashop Addons, the invoice is the final proof of license.' mod='fnac'}
                <br>
                {l s='Contact us to obtain a license only in other cases' mod='fnac'} :
                <a href="mailto:contact@common-services.com">contact@common-services.com</a>
            </p>
        </ps-panel>
    </div>
</div>
