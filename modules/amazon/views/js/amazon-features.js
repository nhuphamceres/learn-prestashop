/**
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
 */

$(document).ready(function () {

    var start_time = [];
    var context = $('#content');
    var features_div = $('#menudiv-features', context);

    function logtime(action, end)
    {
        if (!window.console)
            return(false);

        if (typeof(start_time[action]) == 'undefined' || start_time[action] == null)
            start_time[action] = new Date().getTime();

        if (end)
        {
            var end_time = new Date().getTime();

            console.log('Logtime for '+action+' duration:', end_time - start_time[action]);

            start_time[action] = null;
        }
    }
    logtime('amazon-features.js overall', false);

    function toggleFeature(name)
    {
        var $featureCheckbox = $('#feat-' + name + '-cb', features_div),
            $target = $(".amazon-" + name, context);

        if ($featureCheckbox.prop('checked')) {
            $('*[rel="amazon-' + name + '"]', context).fadeIn().show();
            // todo: Migrate to class
            $target.fadeIn().show();
        } else {
            $target.find('input[type=radio]').not('.fixed').attr('checked', false);
            $target.find('input[type=checkbox]').not('.fixed').attr('checked', false);
            $target.find('input[rel][type=checkbox]').not('.fixed').attr('checked', true);
            $target.find('input[rel][type=radio]').not('.fixed').attr('checked', true);
            $target.fadeOut().hide();

            // Subfeatures
            $target.find('.is-amazon-feature[rel]').each(function () {
                toggleFeature($(this).attr('rel'));
            });
        }

        $('#feat-' + name + '-cb', context).unbind('click');
        $('#feat-' + name + '-cb', context).click(function () {
            toggleFeature(name);
        });

    }

    toggleFeature('products-creation');
    toggleFeature('prices-rules');
    toggleFeature('second-hand');
    toggleFeature('filters');
    toggleFeature('import_products'); //todo: cannot file any element with import_products => need to update later
    toggleFeature('expert-mode');
    toggleFeature('europe');
    toggleFeature('worldwide');
    toggleFeature('messaging');
    toggleFeature('fba');
    toggleFeature('gcid');
    toggleFeature('orders');
    toggleFeature('repricing');
    toggleFeature('shipping');
    toggleFeature('cancel-orders');
    toggleFeature('prime');

    logtime('amazon-features.js overall', true);
});
