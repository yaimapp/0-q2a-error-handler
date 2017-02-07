<?php

define('DEBUG', true);

register_shutdown_function( 'qa_shutdown_handler' );

function qa_shutdown_handler()
{
    $type = '';
    $isError = false;
    if ($error = error_get_last()) {
        switch($error['type']){
            case E_ERROR:
                $type = 'E_ERROR';
                $isError = true;
                break;
            case E_PARSE:
                $type = 'E_PARSE';
                $isError = true;
                break;
            case E_CORE_ERROR:
                $type = 'E_CORE_ERROR';
                $isError = true;
                break;
            case E_CORE_WARNING:
                $type = 'E_CORE_WARNING';
                $isError = true;
                break;
            case E_COMPILE_ERROR:
                $type = 'E_COMPILE_ERROR';
                $isError = true;
                break;
            case E_COMPILE_WARNING:
                $type = 'E_COMPILE_WARNING';
                $isError = true;
                break;
        }
    }
    if ($isError){
        echo '<p>'.qa_lang('error_handler/error_message').'</p>';
        if (DEBUG) {
            echo qa_error_message($type, $error['message'],$error['file'],$error['line']);
        }
        
    }
}

function qa_error_message($type, $message, $file, $line)
{
    echo '<ul>';
    echo '<li>type:'.$type.'</li>';    
    echo '<li>'.$message.'</li>';
    echo '<li>file:'.$file.'('.$line.')</li>';
    echo '</ul>';
}
