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
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
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
     * Multi Select Carriers
     */
    $('#carrier-snsc_move-right').click(function () {
        return !$('#available-carriers option:selected').remove().appendTo('#filtered-carriers');
    });
    $('#carrier-snsc_move-left').click(function () {
        return !$('#filtered-carriers option:selected').remove().appendTo('#available-carriers');
    });

    $('#move-right-payment').click(function () {
        return !$('#unfiltered_order_payment option:selected').remove().appendTo('#filtered_order_payment');
    });
    $('#move-left-payment').click(function () {
        return !$('#filtered_order_payment option:selected').remove().appendTo('#unfiltered_order_payment');
    });

    $('#move-right-status').click(function () {
        return !$('#unfiltered_order_status option:selected').remove().appendTo('#filtered_order_status');
    });
    $('#move-left-status').click(function () {
        return !$('#filtered_order_status option:selected').remove().appendTo('#unfiltered_order_status');
    });


    /*
     * Multi Select Mapping
     */
    $('img[id^="state-move-right"]').click(function () {
        var id = $(this).attr('rel');
        var md5 = $('#available-states_' + id + ' option:selected');

        md5.each(function () {
            $(this).remove().appendTo('#filtered-states_' + id);
            $('select[id^="available-states_"] option[value="' + $(this).val() + '"]').remove();
        });
    });
    $('img[id^="state-move-left"]').click(function () {
        var id = $(this).attr('rel');
        return !$('#filtered-states_' + id + ' option:selected').remove().appendTo('select[id^="available-states_"]');
    });


    /*
     * Common Multi Select
     */
    $('input[name="submitsonice_suivicolis"], button[name="submitsonice_suivicolis"]').click(function () {
        $('#filtered-carriers option, select[id^="filtered-states_"] option, #filtered_order_payment option, #filtered_order_status option').attr('selected', true);
    });


    /*
     * Display long text in div when mouse hover <OPTION>
     */
    $('select[id^="available-states_"], select[id^="filtered-states_"]').find('option').click(function () {
        var id = $(this).attr('rel');
        var str = $(this).text();

        $('.snsc_state_info_' + id).text(str);
    });


    /*
     * Chosen the mapping selector
     */
    try {
        $('#conf-mapping').find('.select_changer select').chosen({width: "100%"});
        $('#menu-mapping').click(function () {
            $('.chzn-drop, .chzn-container-multi').css('width', '100%');
        });
    } catch (e) {
        console.log(e);
        $('.mapping_selector').css('height', '80px').css('width', '100%');
    }


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

    // qTip
    $('label[rel]').each(function () {
        var target_glossary_key = $(this).attr('rel') || 'qqch';
        var target_glossary_div = $('#glossary').find('div.' + target_glossary_key);

        if (target_glossary_div && target_glossary_div.length) {
            var title = $(this).text() || null;
            var content = target_glossary_div.html().trim() || 'N/A';
            var position = JSON.parse(($(this).data('myat') || '{}').replace(/'/g, '"'));

            $(this).addClass('tip').html('<span>' + title + '</span>').find('span').qtip({
                content: {
                    text: content,
                    title: title
                },
                position: position,
                hide: {
                    fixed: true,
                    delay: 300
                }
            });
        }
    });

    /*
     * Cron
     */
    $('.cron-mode').click(function () {
        var div_id = $(this).attr('rel');
        var cron_toggle = $('#' + div_id + '.cron-toggle');

        if ($('#' + div_id).is(':visible')) {
            cron_toggle.slideUp('slow');
            return (false);
        }

        $('.cron-toggle').hide();
        cron_toggle.slideDown('slow');
    });

    $('#install-cronjobs').click(function () {

        $('#cronjob-loader').show();

        $.ajax({
            type: 'POST',
            url: $('#snsc_cron_task_url').val(),
            dataType: 'jsonp',
            data: {
                'action': 'install-cron-jobs',
                'prestashop-cronjobs-params': $('#prestashop-cronjobs-params').text(),
                'id_shop': $('#snsc_id_shop').val()
            },
            success: function (data) {
                $('#cronjob-loader').hide();

                if (window.console)
                    console.log(data);

                if (data.error == true) {
                    $('#cronjobs_success').hide();
                    $('#cronjobs_error').show().html(data.output);
                }
                else {
                    $('#cronjobs_success').show().html(data.output);
                    $('#cronjobs_error').hide();
                }

            },
            error: function (data) {
                if (window.console)
                    console.log(data);

                $('#cronjob-loader').hide();
                $('#cronjobs_success').hide();
                $('#cronjobs_error').show().html($('#tools_ajax_error').val());
            }
        });

        return (false);
    });

});