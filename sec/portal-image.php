<?php
require_once 'inc/requireLogin.php';
require_once 'php/data/rec/group-folder/GroupFolder_Portal.php';
//
if (isset($_GET['id'])) {
  global $login;
  $folder = GroupFolder_Portal::open($login->userGroupId);
  $folder->output($_GET['id'], geta($_GET, 'h', 0), geta($_GET, 'w', 0));
}
