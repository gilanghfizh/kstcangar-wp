<?php

function kst_enqueue_assets(){

    /*
    ========================
    CSS
    ========================
    */

    wp_enqueue_style(
        'kst-theme-style',
        get_stylesheet_uri()
    );

    wp_enqueue_style(
        'plus-jakarta',
        'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'kst-main',
        get_template_directory_uri() . '/assets/css/main.css',
        [],
        time()
    );



    /*
    ========================
    LIBRARY
    ========================
    */

    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        [],
        null,
        true
    );



    /*
    ========================
    API CORE
    ========================
    */

    wp_enqueue_script(
        'kst-api',
        get_template_directory_uri() . '/assets/js/api.js',
        [],
        time(),
        true
    );

    wp_localize_script(
        'kst-api',
        'kstConfig',
        [
            'baseUrl' => home_url(
                '/wp-json/kstcangar/v1'
            )
        ]
    );



    /*
    ========================
    AUTO LOGIN HELPER
    ========================
    */

    wp_enqueue_script(
        'kst-login-helper',
        get_template_directory_uri() . '/assets/js/login-helper.js',
        ['kst-api'],
        time(),
        true
    );



    /*
    ========================
    DASHBOARD
    ========================
    */

    wp_enqueue_script(
        'kst-dashboard',
        get_template_directory_uri() . '/assets/js/dashboard.js',
        [
            'chartjs',
            'kst-api',
            'kst-login-helper'
        ],
        time(),
        true
    );



    /*
    ========================
    BOOKING
    ========================
    */

    wp_enqueue_script(
        'kst-booking',
        get_template_directory_uri() . '/assets/js/booking.js',
        [
            'kst-api',
            'kst-login-helper'
        ],
        time(),
        true
    );



    /*
    ========================
    STOK
    ========================
    */

    wp_enqueue_script(
        'kst-stok',
        get_template_directory_uri() . '/assets/js/stok.js',
        [
            'kst-api',
            'kst-login-helper'
        ],
        time(),
        true
    );

}

add_action(
    'wp_enqueue_scripts',
    'kst_enqueue_assets'
);
