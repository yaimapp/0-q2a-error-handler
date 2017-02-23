<?php

class qa_html_theme_layer extends qa_html_theme_base
{
    function body_content() {
        qa_html_theme_base::body_content();
        $path = ERROR_HANDLER_DIR.'/ajax_error_dialog.html';
        include $path;
    }
}
