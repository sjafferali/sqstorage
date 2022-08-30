<?php
$user="";
require('login.php');
$error = "";
$success = "";
$settingdata = array();
$updatecheck = false;
$uptodate = false;

if ($useRegistration) {
  if (!isset($user) || !isset($user['usergroupid']) || (int)$user['usergroupid'] === 2) {
    $error = gettext('Zugriff verweigert!');
    include('accessdenied.php');
    die();
  }
}

require_once('support/urlBase.php');
$smarty->assign('urlBase', $urlBase);

require_once('./support/dba.php');
if ($usePrettyURLs) $smarty->assign('urlPostFix', '');
else $smarty->assign('urlPostFix', '.php');
if(isset($_POST['target'])){
  $mtarget = $_POST['target'];
}else{
  $mtarget = "";
}
$install_allowed = false;
if(file_exists($basedir . "/support/allow_install")) $install_allowed = true;
require_once('./support/updater.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $mtarget  == 'mail') {

}elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $mtarget  == 'install'){

}elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $mtarget  == 'updater'){

}elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $mtarget  == 'updatecheck'){

}elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $mtarget  == 'startpage'){

}elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {

}


$isEdit = false;
$isAdd = false;


$smarty->assign('updatecheck',$updatecheck);
$smarty->assign('uptodate',$uptodate);
$smarty->assign('settingdata',$settingdata);
//$smarty->assign('update_available',$dbUpdateAvailable);
$smarty->assign('install_allowed',$install_allowed);
$smarty->assign('success', $success);
$smarty->assign('error', $error);
$smarty->assign('POST', $_POST);
//$smarty->assign('user', $user);
//$smarty->assign('users', $users);
//$smarty->assign('usergroups', $usergroups);
$smarty->assign('SESSION', $_SESSION);
$smarty->assign('REQUEST', $_SERVER['REQUEST_URI']);

$smarty->display('updater.tpl');
