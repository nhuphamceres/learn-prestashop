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

setTimeout(function () {
    if (document.readyState !== 'loading'){
        SoNiceSuivi();
    } else if (document.addEventListener) {
        document.addEventListener('DOMContentLoaded', SoNiceSuivi);
    } else {
        /** @typedef function document.attachEvent */
        document.attachEvent('onreadystatechange', function() {
            if (document.readyState !== 'loading') {
                SoNiceSuivi();
            }
        });
    }
}, 700);

function SoNiceSuivi() {
    
    
    var auto_update_done = false;
    
    
    /**
     * Update tracking information
     */
    $('#snsc_update_tracking').click(function() {
        // Hide error <div> and empty error code/msg
        $('#snsc_error_display').hide();
        $('#snsc_error_code, #snsc_error_msg').html('');

        var oldMsg = [
            $('#snsc_status').text(),
            $('#snsc_last_update').text(),
            $('#snsc_location').text(),
            $('#snsc_destination').text()
        ];

        $('#snsc_status').text('-') ;
        $('#snsc_last_update').text('-');
        $('#snsc_location').text('-') ;
        $('#snsc_destination').text('-') ;

        $('#snsc_loader').show();
        
        var pAjax = {};
        pAjax.type = 'POST';
        pAjax.url = $('#snsc_get_parcel').val();
        pAjax.data_type = 'jsonp';
        pAjax.data = $('input[name="checkbox[0]"]').serialize();

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            dataType: pAjax.data_type,
            data:  pAjax.data,
            success: function(data) {
                window.console && console.log(data);
                
                $('#snsc_loader').hide();
                
                if (typeof(data) === 'undefined' || data === null || typeof(data.parcels[0]) === 'undefined' || data.parcels.parcel_o === null)
                    {
                        var errorMsg;
                        var tmp = document.createElement('div');

                        tmp.innerHTML = data.console;
                        errorMsg = tmp.textContent || tmp.innerText || '';

                        $('#snsc_error_code').text('Message');
                        $('#snsc_error_msg').text(errorMsg);
                        $('#snsc_error_display').show();

                        $('#snsc_status').text(oldMsg[0]) ;
                        $('#snsc_last_update').text(oldMsg[1]);
                        $('#snsc_location').text(oldMsg[2]) ;
                        $('#snsc_destination').text(oldMsg[3]) ;

                        return (false);
                    }
                
                if (typeof(data.console) !== 'undefined' && data.console && data.console.length)
                    {
                        var errorMsg = data.console;
                        var tmp = document.createElement('div');

                        tmp.innerHTML = errorMsg;
                        errorMsg = tmp.textContent || tmp.innerText || '';

                        // $('#snsc_error_code').html('');
                        $('#snsc_error_msg').html(errorMsg);
                        $('#snsc_error_display').show();
                        $('#snsc_loader').hide();

                        $('#snsc_status').text(oldMsg[0]) ;
                        $('#snsc_last_update').text(oldMsg[1]);
                        $('#snsc_location').text(oldMsg[2]) ;
                        $('#snsc_destination').text(oldMsg[3]) ;

                        return (false);
                    }
                
                // Status
                if (!(data.parcels[0].eventLibelle === $('#snsc_status').text()))
                    $('#snsc_notify_customer').attr('disabled', false).removeClass('mail_disable');
                $('#snsc_status').text(data.parcels[0].eventLibelle);
                $('input[name$="[event]"]').val(data.parcels[0].eventLibelle);
                
                // Places
                $('#snsc_location').text(data.parcels[0].eventSite);
                $('#snsc_destination').text(data.parcels[0].recipientZipCode + ' ' + data.parcels[0].recipientCity + ', ' + data.parcels[0].recipientCountryCode);
                $('input[name$="[location]"]').val(address);
                
                // date
                if (typeof(data.parcels[0].eventDate) !== 'undefined' && data.parcels[0].eventDate !== null)
                {
                    var date = new Date(data.parcels[0].eventDate);
                    $('#snsc_date').text((date.getDate() < 10 ? '0' : '') + date.toLocaleString());
                    $('#snsc_last_update').text((date.getDate() < 10 ? '0' : '') + date.toLocaleString());
                    $('input[name$="[date]"]').val((date.getDate() < 10 ? '0' : '') + date.toLocaleString());
                }

                if (typeof(showSuccessMessage) === 'function') {
                    showSuccessMessage('Le suivi de colis a été mis à jour.');
                } // TODO
            },
            error: function(data) {
                window.console && console.log(data);
                $('#snsc_loader').hide();
                data.responseText.length && $('#snsc_error_display').show().find('div:first').html(data.responseText);
            }
        });
    });
    
    
    
    /**
     * Send mail to customer
     */
    $('#snsc_notify_customer').click(function() {
        var pAjax = new Object();
        
        pAjax.type = 'POST';
        pAjax.url = $('#snsc_send_mail').val();
        pAjax.data_type = 'jsonp';
        pAjax.data = $('input[name^="orders"]').serialize();
        
        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            dataType: pAjax.data_type,
            data:  pAjax.data,
            success: function(data) {
                if (window.console)
                    console.log(data);
                if (typeof(data) === 'undefined' || data === null)
                    return;
                
                $('#snsc_mail_green').fadeIn('slow');
            }
        });
    });
    
    
    
    /**
     * If auto_update is set in the module configuration then update the parcel tracking informations
     */
    $('body').ajaxStop(function() {
        var upd = $('#snsc_auto_update').val();
        
        if (upd === '1' && !auto_update_done)
            {
                $('#snsc_update_tracking').click();
                auto_update_done = true;
            }
    });
    
    
}