/**
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
 * @author    Alexandre D.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.priceminister@common-services.com
 */

var pmRepricingInitialized1 = false;
var on_load_attr = true;
$(document).ready(function () {
    if (pmRepricingInitialized1) return;
    pmRepricingInitialized1 = true;

    if (typeof($.fn.sortable) == 'function')
        $("#pm-repricing-container").sortable();

    $('#repricing-add').click(function () {
        var cloned = $('#pm-master-repricing .pm-repricing').clone().prependTo('.pm-repricing-group');
        var rand_index = parseInt(Math.random() * 10000000000);

        console.log($(cloned).find('[id="strategie-active-1_key_"]'));
        $(cloned).find('[id="strategie-active-1_key_"]').attr('id', 'strategie-active-1-' + rand_index);
        $(cloned).find('[for="strategie-active-1_key_"]').attr('for', 'strategie-active-1-' + rand_index);
        $(cloned).find('[id="strategie-active-2_key_"]').attr('id', 'strategie-active-2-' + rand_index);
        $(cloned).find('[for="strategie-active-2_key_"]').attr('for', 'strategie-active-2-' + rand_index);

        $(cloned).find('.pm-repricing-header').hide();
        $(cloned).find('.pm-repricing-body').slideDown();

        return (false);
    });

    $('#pm-repricing-container').delegate('.pm-repricing-delete', 'click', function () {
        $(this).parents().get(2).remove();
        return (false);
    });

    $('#pm-repricing-container').delegate('.pm-repricing-action-delete', 'click', function () {
        $(this).parents().get(4).remove();
        return (false);
    });

    $('#pm-repricing-container').delegate('.pm-repricing-minimize', 'click', function () {
        var repricing = $(this).parents();

        $(repricing).find('.pm-repricing-body').slideUp();
        $(repricing).find('.pm-repricing-header').slideDown();

        return (false);
    });

    $('#pm-repricing-container').delegate('.pm-repricing-action-edit', 'click', function () {
        var repricing = $(this).parents().get(3);
        $(repricing).find('.pm-repricing-header').slideUp();
        $(repricing).find('.pm-repricing-body').slideDown();
        return (false);
    });

    $('#pm-repricing-container').delegate('select.product_type', 'change', function () {
        var current = $(this);
        setValues(null, current);
        getProductTypeTemplate(current);
        return (false);
    });

});
