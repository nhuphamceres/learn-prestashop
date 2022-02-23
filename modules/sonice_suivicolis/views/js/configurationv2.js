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
 * @package   sonice_suivicolis
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice_suivicolis@common-services.com
 */

$(document).ready(function () {

    /*
     * PS INFO
     */
    $('#psinfo').unbind('click').click(function (ev) {
        $('#phpinfo_div').hide();
        $('#psinfo_div').toggle();
    });
    $('#phpinfo').unbind('click').click(function () {
        $('#psinfo_div').hide();
        $('#phpinfo_div').toggle();
    });

    /*
     * Login checking
     */
    $('#login_checker').click(function () {
        if ($('input[name="return_info[login]"]').val() === '' || $('input[name="return_info[pwd]"]').val() === '') {
            alert($('#empty_field').val());
            return (false);
        }

        $('#etg_loader').show();

        $.ajax({
            type: 'POST',
            url: $('#snsc_checklogin_url').val(),
            dataType: 'jsonp',
            data: $('input[name^="return_info"]').serialize(),
            success: function (data) {
                $('#etg_loader, #login_ok, #login_not_ok').hide();

                if (window.console)
                    console.log(data);

                if (data.label !== null && data.label !== 'undefined' && data.label.errorCode !== null && data.label.errorCode !== 'undefined' && data.label.errorCode) {
                    if (data.label.errorCode === '201' || data.label.errorCode === '202') {
                        $('#errorID').text(data.label.errorCode);
                        $('#error').text(data.label.errorMessage);
                        $('#error_request').html(data.request);
                        $('#error_response').html(data.response);
                        $('#error_output').html(data.output);
                        $('#login_not_ok').show();
                    }
                    else
                        $('#login_ok').show();
                }
                else {
                    $('#login_not_ok').show();
                    $('#errorID').text('SoNice_Network_error');
                    $('#error').text(data.responseText);
                }
            },
            error: function (data) {
                $('#etg_loader, #login_ok, #login_not_ok').hide();
                if (window.console)
                    console.log(data);
                $('#login_not_ok').show();
                $('#errorID').text('Erreur');
                $('#error').html(data.responseText);
            }
        });
    });

});