<?php
require_once 'inc/requireLogin.php';
require_once 'php/newcrop/NewCrop.php';
require_once 'php/data/rec/sql/MedsNewCrop.php';
require_once 'php/data/rec/erx/ErxStatus.php';
require_once 'php/data/rec/erx/ErxPharm.php';
require_once 'php/dao/FacesheetDao.php';
require_once 'php/dao/FsMedDao.php';
require_once 'php/dao/UserDao.php';
require_once 'php/data/rec/sql/ErxUsers.php';
//
echo '<pre>';
$n = new NewCrop();
switch ($_GET['test']) {
  case '1':
    try {
      if (isset($_GET['id']))
        $id = $_GET['id'];
      else
        $id = '1666';  // buffalo bill
      $hist = $n->pullCurrentMedAllergy($id);
      print_r($hist);
    } catch (SoapHistoryException $e) {
      print_r($e);
      if ($e->isPatientNotFound()) {
        echo 'patient not found';
      }
    }
    break;
  case '2':
    try {
      //$cid = $_GET['cid'];
      //$cid = '1706';  // buddy holly
      //$cid = '1637';  // buffalo bill
      $id = $_GET['id'];
      $ct = $n->buildClickThru($id);
      echo htmlentities($ct['xml']);
    } catch (DomDataRequiredException $e) {
      print_r($e->required);
    }
    break;
  case '3':
    FacesheetDao::refreshFromNewCrop(1706, '123123444');
    $fs = FacesheetDao::getClientActiveMedsAllergies(1706);
    print_r($fs);
    break;
  case '4':
    $resp = $n->pullAcctStatus();
    print_r($resp);
    break;
  case '5':
    try {
      $cid = '1628';  // susie smith
      $cid = '1706';  // buddy holly
      $ct = $n->buildClickThru($cid);
      echo htmlentities($ct['xml']);
    } catch (DomDataRequiredException $e) {
      print_r($e->required);
    }
    break;
  case '6':
    try {
      $resp = $n->pullRenewalRequests();
      print_r($resp);
      echo '<br>--------------------------------<br>';
      $recs = ErxPharm::fromRenewalRequests($resp);
      print_r($recs);
    } catch (Exception $e) {
      print_r($e);
    }
    break;
  case '7':
    try {
      $cid = '1706';  // buddy holly
      $ct = $n->buildClickThru($cid, RequestedPageType::RENEWAL, '3ce50ef2-128b-4413-9180-394592ff5fbb');
      echo htmlentities($ct['xml']);
    } catch (DomDataRequiredException $e) {
      print_r($e->required);
    }
    break;
  case '8':
    $resp = $n->pullAcctStatusDetails();
    print_r($resp);
    break;
  case '9':
    $e = new ErxStatus('1706', '1', 'Drug Info');
    print_r($e);
    break;
  case '10':
    $statuses = $n->pullAcctStatusDetails();
    print_r($statuses);
    exit;
    $ers = array();
    foreach ($statuses as $status => &$recs) {
      if ($recs) 
        foreach ($recs as &$rec) 
          $ers[] = ErxStatus::fromNewCrop($status, $rec);
    }
    print_r($ers);
    break;
  case '11':
    $j = Rec::getStaticJson('ErxStatus');
    print_r($j);
    break;
  case '12':
    $a = FsMedDao::getNewCropAudits('1706', '2011-01-15 14:20:43');
    print_r($a);
    break;
  case '13':
    $recs = Client::search('smith','john');
    print_r($recs);
    break;
  case '14':
    global $myLogin;
    $users = NcUser::fetchUsersInTypes(2);
    print_r($users);
    break;
  case '15':
    global $myLogin;
    $users = ErxUsers::getMyGroup();
    print_r($users);
    break;
  case '16':
    $user = UserGroup::fetch(2);
    print_r($user);
    break;
  case '17':
    $cid = 1666;
    $current = $n->pullCurrentMedAllergy($cid); 
    MedsNewCrop::rebuildFromNewCrop($cid, $current);
    break;
  case '18':
    $cid = '1666';
    $hist = $n->pullCurrentMedAllergyU1($cid);
    print_r($hist);
    break;
}
echo '</pre>';
?>
