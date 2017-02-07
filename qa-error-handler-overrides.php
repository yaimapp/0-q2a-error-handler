<?php

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

function qa_page_db_fail_handler($type, $errno=null, $error=null, $query=null)
{
	$pass_failure_type=$type;
	$pass_failure_errno=$errno;
	$pass_failure_error=$error;
	$pass_failure_query=$query;

	require_once ERROR_HANDLER_DIR.'qa-install-custom.php';

	qa_exit('error');
}
