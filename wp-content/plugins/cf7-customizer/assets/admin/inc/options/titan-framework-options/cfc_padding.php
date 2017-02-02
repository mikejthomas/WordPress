<?php
/**
 * Option: Border
 *
 * Section: Form Padding, Margin & Border
 *
 * @since 0.0.1
 * @package CFC
 *
 */

// if OPT_CFC_PADDING is defiend, use it's value( true/false )
// else use true and always display this option to the user
$cfc_state = defined( 'OPT_CFC_PADDING' ) ? OPT_CFC_PADDING : true;

if ( $cfc_state ) {

    $cfc_sec_pmb->createOption( array(

        'id'      => 'cfc_padding',
        'type'    => 'number',

        'name'    => 'Padding',
        'desc'    => 'Adds padding in pixels (px) to the form',

        'default' => '0',
        'max'     => '250',

        'css'     => '#cfc { padding: valuepx; }'

    ) );


}