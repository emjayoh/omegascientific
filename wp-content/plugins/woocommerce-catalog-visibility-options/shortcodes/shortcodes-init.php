<?php

function wc_cvo_login_url($atts, $content='') {
    if (is_null($content) || empty($content)) {
        $content = __('Login');
    }

    return '<a href="' . wp_login_url() . '">' . $content . '</a>';
}

function wc_cvo_register_url($atts, $content='') {
    if (is_null($content) || empty($content)) {
        $content = __('Register');
    }
    $url = site_url('wp-login.php?action=register', 'login');
    return '<a href="' . $url . '">' . $content . '</a>';
}

function wc_cvo_forgot_password_link($atts, $content='') {
    if (is_null($content) || empty($content)) {
        $content = __('Forgot Your Password');
    }

    return '<a href="' . wp_login_url(get_permalink()) . '&action=lostpassword' . '>">' . $content . '</a>';
}

function wc_cvo_logon_form($atts, $content='') {
    global $error;

    $args = shortcode_atts(array($atts), array());
    $args['echo'] = false;
    
    $html = '';
    if (isset($_GET['logon']) && $_GET['logon'] == 'failed') {
        $html = '<div class="logon-failed">' . __('Logon Failed') . '</div>';
    }
    
    $args['redirect_to'] = 'http://www.google.com';
    return $html .= wp_login_form($args);
}

add_shortcode('woocommerce_logon_link', 'wc_cvo_login_url');
add_shortcode('woocommerce_register_link', 'wc_cvo_register_url');
add_shortcode('woocommerce_forgot_password_link', 'wc_cvo_forgot_password_link');
add_shortcode('woocommerce_logon_form', 'wc_cvo_logon_form');