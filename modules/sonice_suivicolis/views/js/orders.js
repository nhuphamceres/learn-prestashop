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

    /** @typedef {function} showSuccessMessage */
    /** @typedef {Array} data.parcels */
    /** @typedef {string|null} data.parcels.val.eventDate */
    /** @typedef {string|null} data.parcels.val.eventSite */
    /** @typedef {string|null} data.parcels.val.eventCode */
    /** @typedef {string|null} data.parcels.val.eventLibelle */
    /** @typedef {string|null} data.parcels.val.recipientCity */
    /** @typedef {string|null} data.parcels.val.recipientZipCode */
    /** @typedef {string|null} data.parcels.val.recipientCountryCode */
    /** @typedef {string|null} data.parcels.val.orderState */

    /**
     * Update selected parcels recursively
     */
    function get_parcels() {
        $.ajax({
            type: 'POST',
            url: $('#snsc_webservice_url').val(),
            dataType: 'jsonp',
            data: $('input[name^="checkbox["]:checked').slice(0, 3).serialize(),
            error: function (data) {
                window.console && console.log(data);

                $('#update_parcel').find('.snsc_loader').hide();
                $('#snsc_error_display').show().html(data.responseText);
            },
            success: function (data) {
                window.console && console.log(data);

                if (typeof(data) === 'undefined' || data === null) {
                    alert('No data returned... Try again.');
                    return false;
                }

                if (Boolean(data.error) || Boolean(data.console)) {
                    var error_display = $('#snsc_error_display');

                    error_display.html(error_display.html() + (data.console ? ('<br>' + data.console) : '')).show();
                    // problem comes from package with statut excluded like "livré", so disable this package for next tracking
                    $('input[name^="checkbox["]:checked:first').attr('checked', false);
                }

                $.each(data.parcels, function (key, val) {
                    $('input[name^="checkbox[' + key + ']"]').attr('checked', false);

                    if (val === null) {
                        return true;
                    }

                    var target_div = $('div.suivicolis[rel="' + key + '"]');

                    // Date
                    if (typeof(val.eventDate) !== 'undefined' && val.eventDate !== null) {
                        var date = val.eventDate.split(/T|\+/)[0];

                        target_div.find('.snsc_date').text(date);
                    }

                    // Place
                    if (typeof(val.eventSite) !== 'undefined' && val.eventSite !== null) {
                        target_div.find('.snsc_location').text(
                            (typeof(val.eventSite) === 'object' ? '' : val.eventSite)
                        );
                        target_div.find('.snsc_destination').text(
                            val.recipientZipCode + ' ' + val.recipientCity + ', ' + val.recipientCountryCode
                        );
                    }

                    // Libelle
                    if (typeof(val.eventLibelle) !== 'undefined' && val.eventLibelle !== null) {
                        target_div.find('.snsc_description').text(val.eventLibelle);
                    }

                    // Change orderState
                    if (typeof(val.orderState) !== 'undefined' && val.orderState !== null && target_div.find('.current_state').val() !== val.orderState) {
                        target_div.find('.label.color_field')[0].outerHTML =
                            $('#snsc_orderstate_tpl_' + val.orderState).clone().removeAttr('id').show()[0].outerHTML;
                    }

                    target_div.find('table').show();

                    var mail_data = $('input[name="checkbox[' + key + ']"]').val() + '|' + val.eventCode;
                    $('.col-lg-12').append(
                        '<input type="hidden" class="snsc_mail_data" name="orders[]" value="' + mail_data + '">'
                    );
                });

                if ($('input[name^="checkbox["]:checked').length) {
                    setTimeout(function () {
                        get_parcels();
                    }, 300);
                } else {
                    $('#update_parcel').find('.snsc_loader').hide();
                    $('#customer_notification').show();
                }
            }
        });
    }


    /**
     * Send notification mail to customers (if necessary)
     */
    function send_mails() {
        $('#snsc_error_display').hide();

        var pAjax = {};

        pAjax.type = 'POST';
        pAjax.url = $('#snsc_sendmail_url').val();
        pAjax.data_type = 'jsonp';
        pAjax.data = $('.snsc_mail_data').serialize();

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            dataType: pAjax.data_type,
            data: pAjax.data,
            error: function (data) {
                $('#snsc_error_display').html('Mail : ' + data.console).show();
                $('#customer_notification').find('.snsc_loader').hide();
            },
            success: function (data) {
                console.log && console.log(data);

                if (typeof(data) === 'undefined' || data === null) {
                    return false;
                }

                if (Boolean(data.error) || Boolean(data.console)) {
                    $('#snsc_error_display').html(data.console).show();
                }

                var msg = 'Les mails ont été envoyés.';
                typeof(showSuccessMessage) == 'function' ? showSuccessMessage(msg) : alert(msg);

                $('#customer_notification').hide().find('.snsc_loader').hide();
            }
        });
    }


    /**
     * Checkbox range selection (a la GMail)
     * source : http://www.barneyb.com/barneyblog/2008/01/08/checkbox-range-selection-a-la-gmail/
     *
     * @param {jQuery} $ jQuery object
     */
    (function ($) {
        $.fn.enableCheckboxRangeSelection = function () {
            var lastCheckbox = null;
            var $spec = this;
            $spec.unbind("click.checkboxrange");
            $spec.bind("click.checkboxrange", function (e) {
                if (lastCheckbox !== null && (e.shiftKey || e.metaKey)) {
                    $spec.slice(
                        Math.min($spec.index(lastCheckbox), $spec.index(e.target)),
                        Math.max($spec.index(lastCheckbox), $spec.index(e.target)) + 1
                    ).attr({checked: e.target.checked ? "checked" : ""});
                }
                lastCheckbox = e.target;
            });
        };
    })(jQuery);


    $('#one_checkbox_to_rule_them_all').change(function () {
        var state = Boolean($(this).attr('checked'));
        $('input[name^="checkbox"]').not(':disabled').attr('checked', state);
    });

    $('#update_parcel').click(function () {
        $('input[name^="checkbox"]:checked').not(':disabled').length && $(this).find('.snsc_loader').show() &&
        $('.snsc_mail_data').remove() && get_parcels();
    });


    $('#customer_notification').click(function () {
        $('.snsc_mail_data').length && $(this).find('.snsc_loader').show() && send_mails();
    });

    // CheckBox Range Selection
    $('input[name^="checkbox"], tr[rel^="parcel_"] .left').enableCheckboxRangeSelection();

    /* Compatibility PS 1.6 */
    $('#content').removeClass('nobootstrap').addClass('bootstrap');

});
