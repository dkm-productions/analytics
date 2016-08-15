<?php
/**
 * Version
 */
class Version {
  //
  const MAJOR = "5.0";
  const PROD_DEPLOY = "1";
  const TEST_DEPLOY = "5";
  //
  public static function getLabel() {
    return Version::MAJOR . "." . Version::PROD_DEPLOY;
  }
  public static function getUrlSuffix() {
    return Version::MAJOR . "p" . Version::PROD_DEPLOY . "t" . Version::TEST_DEPLOY;
  }
}
