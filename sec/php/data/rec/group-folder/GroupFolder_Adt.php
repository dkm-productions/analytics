<?php
require_once 'php/data/rec/group-folder/GroupFolder.php';
//
/**
 * GroupFolder_Adt
 * @author Warren Hornsby
 */
class GroupFolder_Adt extends GroupFolder {
  /**
   * @param ADTMessage $msg
   * @param string $password for encryption (optional)
   * @return GroupFile_Adt
   */
  public function save($msg, $password = null) {
    $file = GroupFile_Adt::asNew($this, $msg);
    return $file->save($msg->toHl7(), $password);
  }
  /**
   * @param string $filename
   */
  public function download($filename) {
    $file = GroupFile_Adt::from($this, $filename);
    $file->download(self::MIME_XML);
  }
  //
  static function open() {
    global $login;
    return parent::open($login->userGroupId, 'adt');
  }
}
/**
 * GroupFile_Adt
 */
class GroupFile_Adt extends GroupFile {
  /**
   * @param ADTMessage $msg
   * @return GroupFile_Adt
   */
  static function asNew($folder, $msg) {
    $client = $msg->_fs->Client;
    $filename = $client->lastName . '_' . $client->uid . '_ADT.hl7';
    return self::from($folder, $filename);
  }
}
