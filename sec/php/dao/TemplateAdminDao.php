<?php
require_once "php/dao/_util.php";
require_once "php/dao/TemplateReaderDao.php";
require_once "php/data/ui/CrumbTrail.php";

/*
 * Template administration
 */
class TemplateAdminDao extends TemplateReaderDao {

  // Add new template
  // Returns newly created templateId
  public static function addTemplate($template) {

    global $login;
    LoginDao::authenticateUserId($template->userId);
    $sql = "INSERT INTO templates VALUES(NULL";
    $sql .= ", " . $template->userId;
    $sql .= ", " . quote($template->uid);
    $sql .= ", " . quote($template->name);
    $sql .= ", " . toBoolInt($template->public);
    $sql .= ", " . now();
    $sql .= ", NULL";
    $sql .= ", " . quote($template->desc);
    $sql .= ", " . quote($template->title);
    $sql .= ", " . $login->userGroupId;
    $sql .= ")";
    return insert($sql);
  }

  // Add new section
  // Returns newly created sectionId
  public static function addSection($section) {

    LoginDao::authenticateTemplateId($section->templateId);
    $nextSortOrder = TemplateAdminDao::nextSectionSortOrder($section->templateId);
    if (isNull($section->sortOrder)) {
      $section->sortOrder = $nextSortOrder;
    } else if ($section->sortOrder != $nextSortOrder) {
      TemplateAdminDao::shiftSectionSorts($section->templateId, $section->sortOrder);
    }
    $sql = "INSERT INTO template_sections VALUES(NULL";
    $sql .= ", " . $section->templateId;
    $sql .= ", " . quote($section->uid);
    $sql .= ", " . quote($section->name);
    $sql .= ", " . quote($section->desc);
    $sql .= ", " . $section->sortOrder;
    $sql .= ", " . quote($section->title);
    $sql .= ")";
    return insert($sql);
  }

  // Add new group
  // Returns newly created sectionId
  public static function addGroup($group) {

    LoginDao::authenticateSectionId($group->sectionId);
    $sql = "INSERT INTO template_groups VALUES(NULL";
    $sql .= ", " . $group->sectionId;
    $sql .= ", " . quote($group->uid);
    $sql .= ", " . toBoolInt($group->major);
    $sql .= ", " . $group->sortOrder;
    $sql .= ", " . quote($group->desc);
    $sql .= ")";
    return insert($sql);
  }

  // Assign paragraphs to a group
  // $parIds is an array() of paragraph IDs
  public static function assignParsToGroup($groupId, $parIds) {

    LoginDao::authenticateGroupId($groupId);
    $res = query("DELETE FROM template_group2par WHERE groupId=" . $groupId);
    $sortOrder = 1;
    foreach ($parsIds as $k => $parId) {
      $sql = "INSERT INTO template_group2par VALUES(";
      $sql .= $group_id;
      $sql .= ", " . $parId;
      $sql .= ", " . $sortOrder++;
      $res = query($sql);
    }
  }

  // Versioning a paragraph
  // Appends version timestamp to existing par and creates a brand new version,
  // including all children questions and options
  // Returns new parId
  public static function newVersionPar($parId) {

    LoginDao::authenticateParId($parId);
    $par = TemplateAdminDao::getPar($parId, true, true);
    $sql = "UPDATE template_pars SET current=0, inject_only=2 WHERE par_id=" . $parId;
    $res = query($sql);
    $pid = TemplateAdminDao::addPar($par, true);
    foreach ($par->questions as $question) {
      $question->parId = $pid;
      $qid = TemplateAdminDao::addQuestion($question, true);
    }
    return $pid;
  }

  // Add new paragraph
  // Returns newly created parId
  public static function addPar($par, $newVersion = false) {

    LoginDao::authenticateSectionId($par->sectionId);
    if (isNull($par->sortOrder)) {
      $par->sortOrder = 32767;
    }
    if ($par->sortOrder != 32767) {
      TemplateAdminDao::shiftParSorts($par->sectionId, $par->sortOrder);
    }
    $sql = "INSERT INTO template_pars VALUES(NULL";
    $sql .= ", " . $par->sectionId;
    $sql .= ", " . quote($par->uid);
    $sql .= ", " . toBoolInt($par->major);
    $sql .= ", " . $par->sortOrder;
    $sql .= ", " . quote($par->desc);
    $sql .= ", " . toBoolInt($par->noBreak);
    $sql .= ", NULL";
    $sql .= ", " . toBoolInt($par->injectOnly);
    $sql .= ", " . ($newVersion ? now() : "'0000-00-00 00:00:00'");
    $sql .= ", 1";  // current
    $sql .= ", " . quote($par->inType);
    $sql .= ", " . quote($par->inTable);
    $sql .= ", " . quote($par->inCond);
    $sql .= ", 1";  // dev
    $sql .= ")";
    return insert($sql);
  }

  // Add new question with options
  // Returns newly created questionId
  public static function addQuestion($question, $escape = false) {

    LoginDao::authenticateParId($question->parId);
    $nextSortOrder = TemplateAdminDao::nextQuestionSortOrder($question->parId);
    if (isNull($question->sortOrder)) {
      $question->sortOrder = $nextSortOrder;
    } else if ($question->sortOrder != $nextSortOrder) {
      TemplateAdminDao::shiftQuestionSorts($question->parId, $question->sortOrder);
    }
    $sql = "INSERT INTO template_questions VALUES(NULL";
    $sql .= ", " . $question->parId;
    $sql .= ", " . quote($question->uid);
    $sql .= ", " . quote($question->desc, $escape);
    $sql .= ", " . quote($question->bt, $escape);
    $sql .= ", " . quote($question->at, $escape);
    $sql .= ", " . quote($question->btms, $escape);
    $sql .= ", " . quote($question->atms, $escape);
    $sql .= ", " . quote($question->btmu, $escape);
    $sql .= ", " . quote($question->atmu, $escape);
    $sql .= ", " . quote($question->listType);
    $sql .= ", " . $question->break;
    $sql .= ", " . quote($question->test);
    $sql .= ", " . quote($question->defix);
    $sql .= ", " . quote($question->mix);
    $sql .= ", " . quote($question->mcap, $escape);
    $sql .= ", " . quote($question->mix2);
    $sql .= ", " . quote($question->mcap2, $escape);
    $sql .= ", " . quote($question->img);
    $sql .= ", " . $question->sortOrder;
    $sql .= ", " . quote($question->actions);
    $sql .= ", " . quote($question->sync);
    $sql .= ", " . quote($question->outData);
    $sql .= ", " . quote($question->inActions);
    $sql .= ", " . quote($question->dsync);
    $sql .= ", " . quote($question->billing);
    $sql .= ")";
    // echo $sql . "<br/>";
    $id = insert($sql);
    $question->id = $id;
    TemplateAdminDao::addOptionsForQuestion($question, $escape);
    return $id;
  }

  // Add options for question
  // Authentication not required since this public static function called from LoginDao::authenticated addQuestion
  public static function addOptionsForQuestion($question, $escape = false) {

    $sortOrder = 1;
    foreach ($question->options as $k => $option) {
      $option->questionId = $question->id;
      $option->sortOrder = $sortOrder++;
      TemplateAdminDao::addOption($option, $escape);
    }
  }

  // Add new option
  // Authentication not required since this function called from LoginDao::authenticated addQuestion
  public static function addOption($option, $escape = false) {
    insert($option->buildSqlInsert($escape));
  }

  // Update session (not including JSON fields)
  // Returns resource
  public static function updateSession($session) {

    LoginDao::authenticateSessionId($session->id);
    $sql = "UPDATE sessions SET ";
    $sql .= "cid=" . quote($session->cid);
    $sql .= ", cname=" . quote($session->cname);
    $sql .= ", csex=" . quote($session->csex);
    $sql .= ", cdata1=" . quote($session->cdata1);
    $sql .= ", cdata2=" . quote($session->cdata2);
    $sql .= ", cdata3=" . quote($session->cdata3);
    $sql .= ", cdata4=" . quote($session->cdata4);
    $sql .= ", cdata5=" . quote($session->cdata5);
    $sql .= ", cdata6=" . quote($session->cdata6);
    $sql .= ", cdata7=" . quote($session->cdata7);
    $sql .= ", cdata8=" . quote($session->cdata8);
    $sql .= ", cdata9=" . quote($session->cdata9);
    $sql .= ", closed=" . toBoolInt($session->closed);
    $sql .= ", date_updated=NULL";
    $sql .= " WHERE session_id=" . $session->id;
    return query($sql);
  }

  // Close session
  public static function closeSession($sessionId) {
    LoginDao::authenticateSessionId($sessionId);
    return query("UPDATE sessions SET closed=1, date_updated=NULL WHERE session_id=" . $sessionId);
  }
  public static function activateSession($sessionId) {
    LoginDao::authenticateSessionId($sessionId);
    return query("UPDATE sessions SET closed=0, date_updated=NULL WHERE session_id=" . $sessionId);
  }

  // Update template
  // Returns resource
  public static function updateTemplate($template) {

    LoginDao::authenticateTemplateId($template->id);
    $sql = "UPDATE templates SET ";
    $sql .= "uid=" . quote($template->uid);
    $sql .= ", name=" . quote($template->name);
    $sql .= ", title=" . quote($template->title);
    $sql .= ", `desc`=" . quote($template->desc);
    $sql .= ", public=" . toBoolInt($template->public);
    $sql .= " WHERE template_id=" . $template->id;
    return query($sql);
  }

  // Update section
  // Returns resource
  public static function updateSection($section) {

    LoginDao::authenticateSectionId($section->id);
    $row = fetch("SELECT template_id, sort_order FROM template_sections WHERE section_id=" . $section->id);
    if ($row["sort_order"] != $section->sortOrder) {
      TemplateAdminDao::shiftSectionSorts($row["template_id"], $section->sortOrder);
    }
    $sql = "UPDATE template_sections SET ";
    $sql .= "uid=" . quote($section->uid);
    $sql .= ", name=" . quote($section->name);
    $sql .= ", title=" . quote($section->title);
    $sql .= ", `desc`=" . quote($section->desc);
    $sql .= ", sort_order=" . $section->sortOrder;
    $sql .= " WHERE section_id=" . $section->id;
    return query($sql);
  }

  // Update paragraph
  // Returns resource
  public static function updatePar($par) {

    LoginDao::authenticateParId($par->id);
    $dev = "";
    if ($par->sortOrder != 32767) {
      $row = fetch("SELECT section_id, sort_order FROM template_pars WHERE par_id=" . $par->id);
      if ($row["sort_order"] != $par->sortOrder) {
        TemplateAdminDao::shiftParSorts($row["section_id"], $par->sortOrder);
      }
      $dev = $row["dev"];
    }
    $row = fetch("SELECT dev, date_effective FROM template_pars WHERE par_id=" . $par->id);
    $dateEffective = $row["date_effective"];
    $dev = toBoolInt($par->dev);
    if ($row["dev"] && $dev == 0) {
      $dateEffective = nowNoQuotes();
      $dev = "null";
    }
    $sql = "UPDATE template_pars SET ";
    $sql .= "uid=" . quote($par->uid);
    $sql .= ", `desc`=" . quote($par->desc);
    $sql .= ", sort_order=" . $par->sortOrder;
    $sql .= ", major=" . toBoolInt($par->major);
    $sql .= ", no_break=" . toBoolInt($par->noBreak);
    $sql .= ", inject_only=" . toBoolInt($par->injectOnly);
    $sql .= ", in_data_type=" . quote($par->inType);
    $sql .= ", in_data_table=" . quote($par->inTable);
    $sql .= ", in_data_cond=" . quote($par->inCond);
    $sql .= ", date_effective=" . quote($dateEffective);
    $sql .= ", dev=" . $dev;
    $sql .= " WHERE par_id=" . $par->id;
    query($sql);
  }

  // Update question and refresh options
  public static function updateQuestion($question) {

    LoginDao::authenticateQuestionId($question->id);
    $row = fetch("SELECT par_id, sort_order FROM template_questions WHERE question_id=" . $question->id);
    if ($row["sort_order"] != $question->sortOrder) {
      TemplateAdminDao::shiftQuestionSorts($row["par_id"], $question->sortOrder);
    }
    $sql = "UPDATE template_questions SET ";
    $sql .= "uid=" . quote($question->uid);
    $sql .= ", `desc`=" . quote($question->desc);
    $sql .= ", bt=" . quote($question->bt);
    $sql .= ", at=" . quote($question->at);
    $sql .= ", btms=" . quote($question->btms);
    $sql .= ", atms=" . quote($question->atms);
    $sql .= ", btmu=" . quote($question->btmu);
    $sql .= ", atmu=" . quote($question->atmu);
    $sql .= ", list_type=" . quote($question->listType);
    $sql .= ", no_break=" . $question->break;
    $sql .= ", test=" . quote($question->test);
    $sql .= ", actions=" . quote($question->actions);
    $sql .= ", defix=" . quote($question->defix);
    $sql .= ", mix=" . quote($question->mix);
    $sql .= ", mcap=" . quote($question->mcap);
    $sql .= ", mix2=" . quote($question->mix2);
    $sql .= ", mcap2=" . quote($question->mcap2);
    $sql .= ", img=" . quote($question->img);
    $sql .= ", sort_order=" . $question->sortOrder;
    $sql .= ", sync_id=" . quote($question->sync);
    $sql .= ", out_data=" . quote($question->outData);
    $sql .= ", in_data_actions=" . quote($question->inActions);
    $sql .= ", dsync_id=" . quote($question->dsync);
    $sql .= ", billing=" . quote($question->billing);
    $sql .= " WHERE question_id=" . $question->id;
    query($sql);
    TemplateAdminDao::deleteOptionsForQuestion($question->id);
    TemplateAdminDao::addOptionsForQuestion($question);
  }

  // Shift sort orders to make space
  // Authentication not needed (called from update)
  public static function shiftQuestionSorts($parId, $sortOrder) {
    query("UPDATE template_questions SET sort_order=sort_order+1 WHERE par_id=" . $parId . " AND sort_order>=" . $sortOrder);
  }
  public static function shiftParSorts($sectionId, $sortOrder) {
    query("UPDATE template_pars SET sort_order=sort_order+1 WHERE section_id=" . $sectionId . " AND sort_order>=" . $sortOrder . " AND sort_order<32767");
  }
  public static function shiftSectionSorts($templateId, $sortOrder) {
    query("UPDATE template_sections SET sort_order=sort_order+1 WHERE template_id=" . $templateId . " AND sort_order>=" . $sortOrder);
  }

  // Reorder sections
  // Supplied array: sectionId => sortOrder
  public static function reorderSections($arr) {
    foreach ($arr as $sectionId => $sortOrder) {
      LoginDao::authenticateSectionId($sectionId);
      $res = query("UPDATE template_sections SET sort_order=" . $sortOrder . " WHERE section_id=" . $sectionId);
    }
  }

  // Reorder groups
  // Supplied array: groupId => sortOrder
  public static function reorderGroups($arr) {
    foreach ($arr as $groupId => $sortOrder) {
      LoginDao::authenticateGroupId($groupId);
      $res = query("UPDATE groups SET sort_order=" . $sortOrder . " WHERE group_id=" . $parId);
    }
  }

  // Reorder paragraphs
  // Supplied array: parId => sortOrder
  public static function reorderPars($arr) {
    foreach ($arr as $parId => $sortOrder) {
      LoginDao::authenticateParId($parId);
      $res = query("UPDATE template_pars SET sort_order=" . $sortOrder . " WHERE par_id=" . $parId);
    }
  }

  // Clear published cache for a template
  // Returns resource
  public static function clearCache() {
    return query("DELETE FROM template_parjson");
  }
  // Delete template
  // Returns resource
  public static function deleteTemplate($templateId) {
    LoginDao::authenticateTemplateId($templateId);
    return query("DELETE FROM templates WHERE template_id=" . $templateId);
  }

  // Delete section
  // Returns resource
  public static function deleteSection($sectionId) {
    LoginDao::authenticateSectionId($sectionId);
    return query("DELETE FROM template_sections WHERE section_id=" . $sectionId);
  }

  // Delete paragraph
  // Returns resource
  public static function deletePar($parId) {
    LoginDao::authenticateParId($parId);
    return query("DELETE FROM template_pars WHERE par_id=" . $parId);
  }

  // Delete question
  // Returns resource
  public static function deleteQuestion($questionId) {
    LoginDao::authenticateQuestionId($questionId);
    return query("DELETE FROM template_questions WHERE question_id=" . $questionId);
  }

  // Delete all options for a question
  // Authentication not required because this public static function is called from LoginDao::authenticated updateQuestion
  // Returns resource
  public static function deleteOptionsForQuestion($questionId) {
    return query("DELETE FROM template_options WHERE question_id=" . $questionId);
  }

  // Delete templates from supplied array of IDs
  public static function deleteTemplates($templateIds) {
    foreach ($templateIds as $k => $templateId) {
      TemplateAdminDao::deleteTemplate($templateId);
    }
  }

  // Delete sections from supplied array of IDs
  public static function deleteSections($sectionIds) {
    foreach ($sectionIds as $k => $sectionId) {
      TemplateAdminDao::deleteSection($sectionId);
    }
  }

  // Delete paragraphs from supplied array of IDs
  public static function deletePars($parIds) {
    foreach ($parIds as $k => $parId) {
      TemplateAdminDao::deletePar($parId);
    }
  }

  public static function clearDonePars($sectionId) {
    query("UPDATE template_pars SET no_break=0 WHERE section_id=" . $sectionId);
  }

  public static function checkDonePars($sectionId, $pids) {
    TemplateAdminDao::clearDonePars($sectionId);
    foreach ($pids as $k => $pid) {
      query("UPDATE template_pars SET no_break=1 WHERE par_id=" . $pid);
    }
  }

  // Delete questions from supplied array of IDs
  public static function deleteQuestions($questionIds) {
    foreach ($questionIds as $k => $questionId) {
      TemplateAdminDao::deleteQuestion($questionId);
    }
  }

  // Copy (clone) question(s) into a paragraph
  // Supply $copyValues as a comma-delimited list of question ID's
  // Returns number of questions copied
  public static function copyQuestions($par, $copyValues) {
    $questionIds = explode(",", $copyValues);
    $count = count($questionIds);
    for ($i = 0; $i < $count; $i++) {
      $question = TemplateAdminDao::getQuestion($questionIds[$i], true);
      $question->parId = $par->id;
      $question->sortOrder = null;
      try {
        TemplateAdminDao::addQuestion($question, true);
      } catch (DuplicateInsertException $e) {
        $question->uid .= "_COPY";
        $question->uid = substr($question->uid, 0, 25);
        TemplateAdminDao::addQuestion($question, true);
      } catch (Exception $e) {
        throw $e;
      }
    }
    return $count;
  }

  // Get next question in order within par
  // Returns null if nothing found
  public static function getNextQuestion($parId, $sortOrder, $withChildren = false) {

    $row = fetch("SELECT question_id, par_id, uid, `desc`, bt, at, btms, atms, btmu, atmu, list_type, no_break, test, actions, defix, mix, mcap, mix2, mcap2, img, sort_order, sync_id, out_data, in_data_actions, dsync_id, billing FROM template_questions WHERE par_id=" . $parId . " AND sort_order>" . $sortOrder . " ORDER BY sort_order");
    $question = TemplateAdminDao::buildQuestion($row);
    if ($question != null) {
      LoginDao::authenticateQuestionId($question->id);
      if ($withChildren) {
        $question->options = TemplateAdminDao::getOptions($question->id);
      }
      $par = TemplateReaderDao::getPar($question->parId);
      $question->inTable = $par->inTable;
    }
    return $question;
  }

  // Get previous question in order within par
  // Returns null if nothing found
  public static function getPrevQuestion($parId, $sortOrder, $withChildren = false) {

    $row = fetch("SELECT question_id, par_id, uid, `desc`, bt, at, btms, atms, btmu, atmu, list_type, no_break, test, actions, defix, mix, mcap, mix2, mcap2, img, sort_order, sync_id, out_data, in_data_actions, dsync_id, billing FROM template_questions WHERE par_id=" . $parId . " AND sort_order<" . $sortOrder . " ORDER BY sort_order DESC");
    $question = TemplateAdminDao::buildQuestion($row);
    if ($question != null) {
      LoginDao::authenticateQuestionId($question->id);
      if ($withChildren) {
        $question->options = TemplateAdminDao::getOptions($question->id);
      }
      $par = TemplateReaderDao::getPar($question->parId);
      $question->inTable = $par->inTable;
    }
    return $question;
  }

  // Combo builders
  // Returns array of combo objects
  // Supply $questionId for edits, otherwise null
  public static function buildQuestionSortCombo($parId, $questionId) {
    $sql = "SELECT question_id, uid, sort_order FROM template_questions WHERE par_id=" . $parId . " ORDER BY sort_order";
    return TemplateAdminDao::buildCombo($sql, $questionId, "question_id");
  }
  public static function buildSectionSortCombo($templateId, $sectionId) {
    $sql = "SELECT section_id, uid, sort_order FROM template_sections WHERE template_id=" . $templateId . " ORDER BY sort_order";
    return TemplateAdminDao::buildCombo($sql, $sectionId, "section_id");
  }
  public static function buildTemplateCombo() {
    $sql = "SELECT template_id, uid FROM templates WHERE public=1 ORDER BY uid";
    $res = query($sql);
    $combos = array();
    for ($i = 0; $row = mysql_fetch_array($res, MYSQL_ASSOC); $i++) {
      $combos[$row["template_id"]] = $row["uid"];
    }
    return $combos;
  }
  public static function buildParSortCombo($sectionId, $id) {
    $res = query("SELECT par_id, uid, sort_order FROM template_pars WHERE sort_order<32767 AND section_id=" . $sectionId . " ORDER BY sort_order");
    $combos = array();
    $combos[32767] = "[No sort order]";
    $last = "";
    $lastId = "";
    for ($i = 0; $row = mysql_fetch_array($res, MYSQL_ASSOC); $i++) {
      $last = $row["sort_order"];
      if ($i == 0) {
        $combos[$last] = "[At the beginning]";
      } else {
        if ($lastId != $id) {
          $combos[$last] = "After [" . $lastUid . "]";
        }
      }
      $lastId = $row["par_id"];
      $lastUid = $row["uid"];
    }
    if (! is_null($id) && $id == $lastId) {
      $combos[$last] = "[At the end]";
    } else {
      $combos[$last + 1] =  "[At the end]";
    }
    return $combos;
  }
  public static function buildCombo($sql, $id, $idFieldName) {
    $res = query($sql);
    $combos = array();
    $last = "";
    $lastId = "";
    for ($i = 0; $row = mysql_fetch_array($res, MYSQL_ASSOC); $i++) {
      $last = $row["sort_order"];
      if ($i == 0) {
        $combos[$last] = "[At the beginning]";
      } else {
        if ($lastId != $id) {
          $combos[$last] = "After [" . $lastUid . "]";
        }
      }
      $lastId = $row[$idFieldName];
      $lastUid = $row["uid"];
    }
    if (! is_null($id) && $id == $lastId) {
      $combos[$last] = "[At the end]";
    } else {
      $combos[$last + 1] =  "[At the end]";
    }
    return $combos;
  }

  // If sortOrder is null, return the last entry
  public static function denullifySortOrder($sortOrders, $sortOrder) {
    if (is_null($sortOrder)) {
      $keys = array_keys($sortOrders);
      return $keys[count($sortOrders) - 1];
    } else {
      return $sortOrder;
    }
  }


  // Returns next sort order
  public static function nextSectionSortOrder($templateId) {
    $row = fetch("SELECT MAX(sort_order) AS max FROM template_sections WHERE template_id=" . $templateId);
    return $row["max"] + 1;
  }
  public static function nextParSortOrder($sectionId) {
    $row = fetch("SELECT MAX(sort_order) AS max FROM template_pars WHERE section_id=" . $sectionId);
    return $row["max"] + 1;
  }
  public static function nextQuestionSortOrder($parId) {
    $row = fetch("SELECT MAX(sort_order) AS max FROM template_questions WHERE par_id=" . $parId);
    return $row["max"] + 1;
  }

  // Breadcrumb builders
  public static function buildTemplateBreadcrumb() {
    $trail = new CrumbTrail();
    TemplateAdminDao::addDashboardCrumb($trail);
    return $trail->html();
  }
  public static function buildSectionBreadcrumb($templateId) {
    $trail = new CrumbTrail();
    TemplateAdminDao::addTemplateCrumb($trail, $templateId);
    TemplateAdminDao::addDashboardCrumb($trail);
    return $trail->html();
  }
  public static function buildParBreadcrumb($sectionId) {
    $trail = new CrumbTrail();
    $templateId = TemplateAdminDao::addSectionCrumb($trail, $sectionId);
    TemplateAdminDao::addTemplateCrumb($trail, $templateId);
    TemplateAdminDao::addDashboardCrumb($trail);
    return $trail->html();
  }
  public static function buildQuestionBreadcrumb($parId) {
    $trail = new CrumbTrail();
    $sectionId = TemplateAdminDao::addParCrumb($trail, $parId);
    $templateId = TemplateAdminDao::addSectionCrumb($trail, $sectionId);
    TemplateAdminDao::addTemplateCrumb($trail, $templateId);
    TemplateAdminDao::addDashboardCrumb($trail);
    return $trail->html();
  }
  public static function addDashboardCrumb($trail) {
    $trail->push("adminDashboard.php", "Dashboard");
  }
  public static function addTemplateCrumb($trail, $templateId) {
    $row = fetch("SELECT uid FROM templates WHERE template_id=" . $templateId);
    $trail->push("adminTemplate.php?id=" . $templateId . TemplateAdminDao::rnd(), "T:" . $row["uid"]);
  }
  public static function addSectionCrumb($trail, $sectionId) {
    $row = fetch("SELECT template_id, uid FROM template_sections WHERE section_id=" . $sectionId);
    $trail->push("adminSection.php?id=" . $sectionId . TemplateAdminDao::rnd(), "S:" . $row["uid"]);
    return $row["template_id"];
  }
  public static function addParCrumb($trail, $parId) {
    $row = fetch("SELECT s.template_id, p.section_id, p.uid FROM template_pars p INNER JOIN template_sections s ON s.section_id=p.section_id WHERE par_id=" . $parId);
    $trail->push("adminPar.php?id=" . $parId . "&tid=" . $row["template_id"] . "&sid=" . $row["section_id"] . TemplateAdminDao::rnd(), "P:" . $row["uid"]);
    return $row["section_id"];
  }

  // JSON
  public static function updateParJson($parId, $json) {
    query("UPDATE template_parjson SET json=" . quote($json, true) . " WHERE par_id=" . $parId);
  }
  public static function deleteParJson($parId) {
    query("DELETE FROM template_parjson WHERE par_id=" . $parId);
  }

  private static function rnd() {
    return "&" . mt_rand(0, 99999999);
  }
}
?>