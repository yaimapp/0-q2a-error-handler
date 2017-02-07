<?php
/*
    Plugin Name: Error Handler
    Plugin URI:
    Plugin Update Check URI:
    Plugin Description: Error handling to display messages.
    Plugin Version: 1.0.0
    Plugin Date: 2017-02-06
    Plugin Author: 38qa.net
    Plugin Author URI:
    Plugin License: GPLv2
    Plugin Minimum Question2Answer Version: 1.7
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
        header('Location: ../../');
        exit;
}

define('ERROR_HANDLER_DIR', __DIR__);

// language file
qa_register_plugin_phrases('qa-error-handler-lang-*.php', 'error_handler');

require_once(ERROR_HANDLER_DIR.'/qa-error-handler-function.php');



/*
    Omit PHP closing tag to help avoid accidental output
*/
