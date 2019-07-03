import AJAX from './AJAX.es6.js';
import ConnectedSites from './ConnectedSites.es6.js';

jQuery( function ( $ ) {

    $('#bulk_data_push').unbind().click( function( e ) {
        e.preventDefault();
        AJAX.get(DataSync.api.url + '/source_data/push' );
    }, false);

    // ADD SITE
    $( '#add_site' ).unbind().click( function ( e ) {
        e.preventDefault();

        $( '.lightbox_wrap' ).addClass( 'display' );

        $( '#close' ).unbind().click( function () {
            $( '.lightbox_wrap' ).removeClass( 'display' );
        } );

        $( '#submit_site' ).unbind().click( function ( e ) {
            e.preventDefault();
            ConnectedSites.save();
        } );
    } );

    $( '.remove_site' ).unbind().click( function ( e ) {
        let site_id = parseInt($(this).parent().attr('id').split('site-')[1]);
        ConnectedSites.delete( site_id );
    });

    if ( document.getElementById( 'error_log' ) ) {
        // document.getElementById( 'refresh_error_log' ).onclick = function ( e ) {
        //     e.preventDefault();
        //     AJAX.get(DataSync.api.url + '/log' ).then( function( result ) {
        //         if ( document.getElementById( 'error_log' ) ) {
        //             document.getElementById( 'error_log' ).innerHTML = result;
        //         }
        //     });
        // }

        setInterval( function() {
            AJAX.get(DataSync.api.url + '/log' ).then( function( result ) {
                if ( document.getElementById( 'error_log' ) ) {
                    document.getElementById( 'error_log' ).innerHTML = result;
                }
            });
        }, 2000 );

    }



} );

