<?php 
/**
 * Enumerations
 * @see http://info.medcity.ru/cda/infrastructure/vocabulary/
 */
class ActClass {
  const ACT = 'ACT';
  const BATTERY = 'BATTERY';
  const CARE_PROVISION = 'PCPR';
  const CLUSTER = 'CLUSTER';
  const ENCOUNTER = 'ENC';
  const OBSERVATION = 'OBS';
  const PROCEDURE = 'PROC';
  const SUBSTANCE_ADMINISTRATION = 'SBADM';
}
class ActMood {
  const DEFINITION = 'DEF';
  const EVENT = 'EVN';
  const INTENTION = 'INT';
  const REQUEST = 'RQO';
  const APPT_REQUEST = 'ARQ';
}
class ActRelationshipType {
  const HAS_COMPONENT = 'COMP';
  const HAS_REASON = 'RSON';
  const HAS_SUBJECT = 'SUBJ';
  const IS_DERIVED_FROM = 'DRIV'; 
  const IS_MANIFESTATION_OF = 'MFST';
  const REFERS_TO = 'REFR';
  const STARTS_AFTER_START_OF = 'SAS';
}
class EntityClass {
  const MANUFACTURED_MATERIAL = 'MMAT';
}
class RoleClass {
  const MANUFACTURED_PRODUCT = 'MANU';
}
class RoleClassAssociative {
  const GUARDIAN = 'GUA';
  const NEXT_OF_KIN = 'NOK';
}
class ParticipationType {
  const INDIRECT_TARGET = 'IND';
  const CONSUMABLE = 'CSM';
  const PERFORMER = 'PRF';
}
?>