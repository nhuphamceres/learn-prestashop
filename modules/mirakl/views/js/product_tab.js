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
 *
 * @package   Mirakl
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.mirakl@common-services.com
 */

$(document).ready(function ()
{
    StartMirakl(module);
    
    function StartMirakl(module)
    {
        var section = '#' + module + '-product-tab ',
            $context = $(section),
            message_error = $('.product-options-message-error', $(section)).val(),
            message_success = $('.product-options-message-success', $(section)).val(),
            module_json_url = $('input[name="module-json"]', $(section)).val();

        $('.specific_fields_select_mkp', $context).change(function () {
            var mkp = $(this).val();
            $('.mkp_specific_fields', $context).hide();
            $('.mkp_specific_fields.' + mkp, $context).show();
        });

        $(section).on('change', 'input:visible, select[name]', function () {
            $.ajax({
                type: 'POST',
                url: module_json_url,
                data: $(section).find('input, select').serialize() + '&action=set&rand=' + new Date().getTime() + '&context_key=' + $('input[name=context_key]').val() + '&callback=?',
                dataType: 'jsonp',
                success: function (data) {
                    if (data.error)
                        showErrorMessage(message_error + '<br>' + data.msg);
                    else
                        showSuccessMessage(message_success);
                },
                error: function (data) {
                    showErrorMessage('Error');
                }
            });
        });

        $(section + '.propagate').click(function () {
            if (window.console)
                console.log(section + marketplace_options_json);
            var classes = $(this).attr('class').split(" ");
            var params = classes[0].split("-");
            var action = params[2];
            var scope = params[3];

            if (!confirm($('#'+module+'-propagate-'+scope).val()))  return (false);

            $.ajax({
                type: 'POST',
                url: module_json_url,
                data: $(section + ' input').serialize() + '&action=propagate-' + action + '-' + scope + '&rand=' + new Date().getTime() + '&context_key=' + $('input[name=context_key]').val() + '&callback=?',
                dataType: 'jsonp',
                success: function (data) {
                    if (data.error)
                        showErrorMessage(message_error);
                    else
                        showSuccessMessage(message_success);
                },
                error: function (data) {
                    if (window.console)
                        console.log('Error', data);

                    showErrorMessage('Error');
                }
            });
        });

        $(section + 'input[name^="mirakl-price-"]').blur(function () {
            DisplayPrice($(this));
        });

        function DisplayPrice(obj) {
            var price = obj.val();
            if (price <= 0 || !price) return;

            price = parseFloat(price.replace(',', '.'));
            price = price.toFixed(2);
            if (isNaN(price)) price = '';
            obj.val(price);
        }

        function comments(text) {
            text.val(text.val().substr(0, 200));
            var left = 200 - parseInt(text.val().length);
            $('#c-count').html(left);
            return (true);
        }

        $(section + 'input[name^="mirakl-text-"]').keypress(function () {
            return (comments($(this)));
        });
        $(section + 'input[name^="mirakl-text-"]').change(function () {
            return (comments($(this)));
        });

    }
});

