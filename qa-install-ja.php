<?php

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../');
    exit;
}

require_once QA_INCLUDE_DIR.'db/install.php';

qa_report_process_stage('init_install');


// Define database failure handler for install process, if not defined already (file could be included more than once)

if (!function_exists('qa_install_db_fail_handler')) {
    /**
     * Handler function for database failures during the installation process
     */
    function qa_install_db_fail_handler($type, $errno=null, $error=null, $query=null)
    {
        global $pass_failure_from_install;

        $pass_failure_type = $type;
        $pass_failure_errno = $errno;
        $pass_failure_error = $error;
        $pass_failure_query = $query;
        $pass_failure_from_install = true;

        require QA_INCLUDE_DIR.'qa-install.php';

        qa_exit('error');
    }
}


$success = '';
$errorhtml = '';
$suggest = '';
$buttons = array();
$fields = array();
$fielderrors = array();
$hidden = array();


// Process user handling higher up to avoid 'headers already sent' warning

if (!isset($pass_failure_type) && qa_clicked('super')) {
    require_once QA_INCLUDE_DIR.'db/users.php';
    require_once QA_INCLUDE_DIR.'app/users-edit.php';

    $inemail = qa_post_text('email');
    $inpassword = qa_post_text('password');
    $inhandle = qa_post_text('handle');

    $fielderrors = array_merge(
        qa_handle_email_filter($inhandle, $inemail),
        qa_password_validate($inpassword)
    );

    if (empty($fielderrors)) {
        require_once QA_INCLUDE_DIR.'app/users.php';

        $userid = qa_create_new_user($inemail, $inpassword, $inhandle, QA_USER_LEVEL_SUPER);
        qa_set_logged_in_user($userid, $inhandle);

        qa_set_option('feedback_email', $inemail);

        $success .= 'おめでとう！あなたのサイトにQuestion2Answerをインストールできました！！';
    }
}


//    Output start of HTML early, so we can see a nicely-formatted list of database queries when upgrading

?><!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <style>
            body, input { font: 16px Verdana, Arial, Helvetica, sans-serif; }
            body { text-align: center; width: 640px; margin: 64px auto; }
            table { margin: 16px auto; }
            th, td { padding: 2px; }
            th { text-align: right; font-weight: normal; }
            td { text-align: left; }
            .msg-success { color: #090; }
            .msg-error { color: #b00; }
        </style>
    </head>
    <body>
<?php


if (isset($pass_failure_type)) {
    // this page was requested due to query failure, via the fail handler
    switch ($pass_failure_type) {
        case 'connect':
            $errorhtml .= 'データベースに接続できませんでした。時間をおいて再度お試しください。';
            break;

        case 'select':
            $errorhtml .= 'データベースを切り換えることができませんでした。時間をおいて再度お試しください。';
            break;

        case 'query':
            global $pass_failure_from_install;

            if (@$pass_failure_from_install) {
                $errorhtml .= 'インストールクエリを実行できませんでした。時間をおいて再度お試しください。';
                $errorhtml .= "\n\n".qa_html($pass_failure_query."\n\nError ".$pass_failure_errno.": ".$pass_failure_error."\n\n");
            } else {
                $errorhtml .= 'クエリを実行できませんでした。時間をおいて再度お試しください。';
            }
            break;
    }
}
else {
    // this page was requested by user GET/POST, so handle any incoming clicks on buttons
    qa_db_connect('qa_install_db_fail_handler');

    if (qa_clicked('create')) {
        qa_db_install_tables();

        if (QA_FINAL_EXTERNAL_USERS) {
            if (defined('QA_FINAL_WORDPRESS_INTEGRATE_PATH')) {
                require_once QA_INCLUDE_DIR.'db/admin.php';
                require_once QA_INCLUDE_DIR.'app/format.php';

                // create link back to WordPress home page
                qa_db_page_move(qa_db_page_create(get_option('blogname'), QA_PAGE_FLAGS_EXTERNAL, get_option('home'), null, null, null), 'O', 1);

                $success .= 'Question2Answerのデータベースは作成され、WordPressサイトと統合されました。';

            }
            else {
                $success .= 'Question2Answerのデータベースは外部のユーザー管理のために作成されました。オンラインのドキュメントを読んで統合を完了させてください。';
            }
        }
        else {
            $success .= 'Question2Answerのデータベースは作成されました。';
        }
    }

    if (qa_clicked('nonuser')) {
        qa_db_install_tables();
        $success .= '追加のQuestion2Answerデータベーステーブルが作成されました。';
    }

    if (qa_clicked('upgrade')) {
        qa_db_upgrade_tables();
        $success .= 'Question2Answerのデータベースが更新されました。';
    }

    if (qa_clicked('repair')) {
        qa_db_install_tables();
        $success .= 'Question2Answerのデータベーステーブルが修復されました。';
    }

    if (qa_clicked('module')) {
        $moduletype = qa_post_text('moduletype');
        $modulename = qa_post_text('modulename');

        $module = qa_load_module($moduletype, $modulename);

        $queries = $module->init_queries(qa_db_list_tables());

        if (!empty($queries)) {
            if (!is_array($queries))
                $queries = array($queries);

            foreach ($queries as $query)
                qa_db_upgrade_query($query);
        }

        $success .= $modulename.' '.$moduletype.' モジュールがデータベースの初期化を完了しました。';
    }

}

if (qa_db_connection(false) !== null && !@$pass_failure_from_install) {
    $check = qa_db_check_tables(); // see where the database is at

    switch ($check) {
        case 'none':
            if (@$pass_failure_errno == 1146) // don't show error if we're in installation process
                $errorhtml = '';

            $errorhtml .= 'Question2Answerへようこそ。データベースをセットアップします';

            if (QA_FINAL_EXTERNAL_USERS) {
                if (defined('QA_FINAL_WORDPRESS_INTEGRATE_PATH')) {
                    $errorhtml .= "\n\n以下をクリックすると、Question2AnswerサイトがWordPressサイトのユーザーと統合するようにセットアップします <a href=\"".qa_html(get_option('home'))."\" target=\"_blank\">".qa_html(get_option('blogname'))."</a> 詳細については、オンラインマニュアルを参照してください。";
                }
                else {
                    $errorhtml .= "\n\n以下をクリックすると、Question2Answerサイトが既存のユーザーデータベースと統合されるように設定されます。 ユーザーはデータベースの列で参照されます。".qa_html(qa_get_mysql_user_column_type())." 詳細については、オンラインマニュアルを参照してください。";
                }

                $buttons = array('create' => 'データベースの設定');
            }
            else {
                $errorhtml .= "\n\n以下をクリックすると、内部的にユーザーIDとログインを管理するためのQuestion2Answerデータベースがセットアップされます。\n\n既存のユーザーベースまたはWebサイトでシングルサインオンを提供する場合は、先に進む前にオンラインドキュメントを参照してください。";
                $buttons = array('create' => 'ユーザー管理を含むデータベースの設定');
            }
            break;

        case 'old-version':
            // don't show error if we need to upgrade
            if (!@$pass_failure_from_install)
                $errorhtml = '';

            // don't show error before this
            $errorhtml .= 'このバージョンでは、Question2Answerデータベースをアップグレードする必要があります。';
            $buttons = array('upgrade' => 'データベースのアップグレード');
            break;

        case 'non-users-missing':
            $errorhtml = "このQuestion2Answerサイトは、別のQ2Aサイトとユーザーを共有していますが、独自のコンテンツ用に追加のデータベーステーブルが必要です。\n\n それらを作成するには、以下をクリックしてください。";
            $buttons = array('nonuser' => 'テーブルの設定');
            break;

        case 'table-missing':
            $errorhtml .= 'Question2Answerのデータベースにテーブルがありません。';
            $buttons = array('repair' => 'データベースを修復');
            break;

        case 'column-missing':
            $errorhtml .= 'Question2Answerのデータベーステーブルに列がありません。';
            $buttons = array('repair' => 'データベースを修復する');
            break;

        default:
            require_once QA_INCLUDE_DIR.'db/admin.php';

            if (!QA_FINAL_EXTERNAL_USERS && qa_db_count_users() == 0) {
                $errorhtml .= '現在、Question2Answerのデータベースにはユーザーはいません。下記の情報を入力して上級管理者を作成してください。';
                $fields = array(
                    'handle' => array('label' => 'Username:', 'type' => 'text'),
                    'password' => array('label' => 'Password:', 'type' => 'password'),
                    'email' => array('label' => 'Email address:', 'type' => 'text'),
                );
                $buttons = array('super' => '上級管理者の設定');
            }
            else {
                $tables = qa_db_list_tables();

                $moduletypes = qa_list_module_types();

                foreach ($moduletypes as $moduletype) {
                    $modules = qa_load_modules_with($moduletype, 'init_queries');

                    foreach ($modules as $modulename => $module) {
                        $queries = $module->init_queries($tables);
                        if (!empty($queries)) {
                            // also allows single query to be returned
                            $errorhtml = strtr(qa_lang_html('admin/module_x_database_init'), array(
                                '^1' => qa_html($modulename),
                                '^2' => qa_html($moduletype),
                                '^3' => '',
                                '^4' => '',
                            ));

                            $buttons = array('module' => 'データベースの初期化');

                            $hidden['moduletype'] = $moduletype;
                            $hidden['modulename'] = $modulename;
                            break;
                        }
                    }
                }
            }
            break;
    }
}

if (empty($errorhtml)) {
    if (empty($success))
        $success = 'Question2Answerのデータベースに問題はありませんでした。';

    $suggest = '<a href="'.qa_path_html('admin', null, null, QA_URL_FORMAT_SAFEST).'">管理センターへ</a>';
}

?>

        <form method="post" action="<?php echo qa_path_html('install', null, null, QA_URL_FORMAT_SAFEST)?>">

<?php

if (strlen($success))
    echo '<p class="msg-success">'.nl2br(qa_html($success)).'</p>';

if (strlen($errorhtml))
    echo '<p class="msg-error">'.nl2br($errorhtml).'</p>';

if (strlen($suggest))
    echo '<p>'.$suggest.'</p>';


//    Very simple general form display logic (we don't use theme since it depends on tons of DB options)

if (count($fields)) {
    echo '<table>';

    foreach ($fields as $name => $field) {
        echo '<tr>';
        echo '<th>'.qa_html($field['label']).'</th>';
        echo '<td><input type="'.qa_html($field['type']).'" size="24" name="'.qa_html($name).'" value="'.qa_html(@${'in'.$name}).'"></td>';
        if (isset($fielderrors[$name]))
            echo '<td class="msg-error"><small>'.qa_html($fielderrors[$name]).'</small></td>';
        else
            echo '<td></td>';
        echo '</tr>';
    }

    echo '</table>';
}

foreach ($buttons as $name => $value)
    echo '<input type="submit" name="'.qa_html($name).'" value="'.qa_html($value).'">';

foreach ($hidden as $name => $value)
    echo '<input type="hidden" name="'.qa_html($name).'" value="'.qa_html($value).'">';

qa_db_disconnect();

?>

        </form>
    </body>
</html>
