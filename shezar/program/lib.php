<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @package shezar
 * @subpackage program
 */

require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/datalib.php');
require_once($CFG->libdir . '/ddllib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/shezar/program/program.class.php');
require_once($CFG->dirroot . '/shezar/certification/lib.php'); // For the constants
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');

/**
 * Can logged in user view user's required learning
 *
 * @access  public
 * @param   int     $learnerid   Learner's id
 * @return  boolean
 */
function prog_can_view_users_required_learning($learnerid) {
    global $USER;

    if (!isloggedin()) {
        return false;
    }

    $systemcontext = context_system::instance();

    // If the user can view any programs
    if (has_capability('shezar/program:accessanyprogram', $systemcontext)) {
        return true;
    }

    // If the user cannot view any programs
    if (!has_capability('shezar/program:viewprogram', $systemcontext)) {
        return false;
    }

    // If this is the current user's own required learning
    if ($learnerid == $USER->id) {
        return true;
    }

    // If this user is their manager
    if (\shezar_job\job_assignment::is_managing($USER->id, $learnerid)) {
        return true;
    }

    $usercontext = context_user::instance($learnerid);
    if (has_capability('shezar/core:markusercoursecomplete', $usercontext)) {
        return true;
    }

    return false;
}

/**
 * Return a list of a user's programs or a count
 *
 * @global object $DB
 * @param int $userid
 * @param string $sort The order in which to sort the programs
 * @param mixed $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
 * @param mixed $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
 * @param bool $returncount Whether to return a count of the number of records found or the records themselves
 * @param bool $showhidden Whether to include hidden programs in records returned when using normal visibility
 * @param bool $onlyprograms Only return programs (excludes certifications)
 * @param bool $onlyactive Only return active programs.
 * @param bool $onlycertifications Only return certifications (excludes programs)
 * @return array|int
 */
function prog_get_all_programs($userid, $sort = '', $limitfrom = '', $limitnum = '', $returncount = false,
                               $showhidden = false, $onlyprograms = false, $onlyactive = true, $onlycertifications = false) {
    global $DB;

    // Construct sql query.
    $count = 'SELECT COUNT(*) ';
    $select = 'SELECT p.*, p.fullname AS progname, pc.timedue AS duedate, pc.status AS status ';
    list($insql, $params) = $DB->get_in_or_equal(array(PROGRAM_EXCEPTION_RAISED, PROGRAM_EXCEPTION_DISMISSED),
            SQL_PARAMS_NAMED, 'param', false);
    $from = "FROM {prog} p
       INNER JOIN {context} ctx ON (p.id = ctx.instanceid AND ctx.contextlevel = :contextlevel)
       INNER JOIN {prog_completion} pc
               ON p.id = pc.programid AND pc.coursesetid = 0 ";

    $where = "WHERE pc.userid = :userid
              AND EXISTS(SELECT id
                           FROM {prog_user_assignment} pua
                          WHERE pua.exceptionstatus {$insql}
                            AND pc.programid = pua.programid
                            AND pc.userid = pua.userid
                        ) ";
    if ($onlyactive) {
        $where .= " AND pc.status <> :statuscomplete";
        $params['statuscomplete'] = STATUS_PROGRAM_COMPLETE;
    }
    if ($onlyprograms) {
        $where .= " AND p.certifid IS NULL";
    }
    if ($onlycertifications) {
        $where .= " AND p.certifid IS NOT NULL";
    }

    $params['contextlevel'] = CONTEXT_PROGRAM;
    $params['userid'] = $userid;

    list($visibilitysql, $visibilityparams) = shezar_visibility_where($userid,
                                                                      'p.id',
                                                                      'p.visible',
                                                                      'p.audiencevisible',
                                                                      'p',
                                                                      'certification',
                                                                      false,
                                                                      $showhidden);
    $params = array_merge($params, $visibilityparams);
    $where .= " AND {$visibilitysql} ";

    if ($returncount) {
        return $DB->count_records_sql($count.$from.$where, $params);
    } else {
        return $DB->get_records_sql($select.$from.$where.$sort, $params, $limitfrom, $limitnum);
    }
}

/**
 * Return a list of a user's required learning programs or a count
 *
 * @global object $DB
 * @param int $userid
 * @param string $sort The order in which to sort the programs
 * @param mixed $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
 * @param mixed $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
 * @param bool $returncount Whether to return a count of the number of records found or the records themselves
 * @param bool $showhidden Whether to include hidden programs in records returned when using normal visibility
 * @param bool $onlyprograms Only return programs (excludes certifications)
 * @return array|int
 */
function prog_get_required_programs($userid, $sort='', $limitfrom='', $limitnum='', $returncount=false, $showhidden=false,
                                    $onlyprograms=true) {
    return prog_get_all_programs($userid, $sort, $limitfrom, $limitnum, $returncount, $showhidden,
                                 $onlyprograms);
}

/**
 * Return a list of a user's certification programs or a count
 *
 * @global object $DB
 * @param int $userid
 * @param string $sort SQL fragment to order the programs
 * @param mixed $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
 * @param mixed $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
 * @param bool $returncount Whether to return a count of the number of records found or the records themselves
 * @param bool $showhidden Whether to include hidden programs in records returned when using normal visibility
 * @param bool $activeonly Whether to restrict to only active programs (programs where "Progress" is not "Complete")
 * @return array|int
 */
function prog_get_certification_programs($userid, $sort='', $limitfrom='', $limitnum='', $returncount=false,
                                         $showhidden=false, $activeonly=false) {
    global $DB;

    $params = array();
    $params['contextlevel'] = CONTEXT_PROGRAM;
    $params['userid'] = $userid;
    $params['comptype'] = CERTIFTYPE_PROGRAM;

    list($exceptionsql, $exceptionparams) = $DB->get_in_or_equal(array(PROGRAM_EXCEPTION_RAISED, PROGRAM_EXCEPTION_DISMISSED),
                                                                                        SQL_PARAMS_NAMED, 'exception', false);
    $params = array_merge($params, $exceptionparams);

    // Construct sql query
    $count = 'SELECT COUNT(*) ';
    $select = 'SELECT p.*, p.fullname AS progname, pc.timedue AS duedate, cfc.certifpath, cfc.status, cfc.timeexpires ';
    $from = "FROM {prog} p
            INNER JOIN {context} ctx ON (p.id = ctx.instanceid AND ctx.contextlevel = :contextlevel)
            INNER JOIN {prog_completion} pc ON p.id = pc.programid
                    AND pc.coursesetid = 0
                    AND pc.userid = :userid
            INNER JOIN {certif} cf ON cf.id = p.certifid
            INNER JOIN {certif_completion} cfc ON cfc.certifid = cf.id
                    AND cfc.userid = pc.userid ";

    // Is the user assigned? Exists is more efficient than using distinct.
    $where = "WHERE EXISTS (SELECT userid
                            FROM {prog_user_assignment} pua
                            WHERE pua.programid = pc.programid
                            AND pua.userid = pc.userid
                            AND pua.exceptionstatus {$exceptionsql})";

    list($visibilitysql, $visibilityparams) = shezar_visibility_where($userid,
                                                                      'p.id',
                                                                      'p.visible',
                                                                      'p.audiencevisible',
                                                                      'p',
                                                                      'certification',
                                                                      false,
                                                                      $showhidden);
    $params = array_merge($params, $visibilityparams);
    $where .= " AND {$visibilitysql} ";

    if ($activeonly) {
        // This should only show non complete certifications and due/expired recertifications.
        $where .= "AND (cfc.status <> :cstatus OR cfc.renewalstatus <> :rstatus) ";
        $params['cstatus'] = CERTIFSTATUS_COMPLETED;
        $params['rstatus'] = CERTIFRENEWALSTATUS_NOTDUE;
    }

    if ($returncount) {
        return $DB->count_records_sql($count.$from.$where, $params);
    } else {
        return $DB->get_records_sql($select.$from.$where.$sort, $params, $limitfrom, $limitnum);
    }
}

/**
 * Return markup for displaying a table of a specified user's required programs
 * (i.e. programs that have been automatically assigned to the user)
 *
 * This includes hidden programs but excludes unavailable programs
 *
 * @access  public
 * @param   int     $userid     Program assignee
 * @return  string
 */
function prog_display_required_programs($userid) {
    global $CFG, $OUTPUT;

    $count = prog_get_required_programs($userid, '', '', '', true, true);

    // Set up table
    $tablename = 'progs-list-programs';
    $tableheaders = array(get_string('programname', 'shezar_program'));
    $tablecols = array('progname');

    // Due date
    $tableheaders[] = get_string('duedate', 'shezar_program');
    $tablecols[] = 'duedate';

    // Progress
    $tableheaders[] = get_string('progress', 'shezar_program');
    $tablecols[] = 'progress';

    $baseurl = $CFG->wwwroot . '/shezar/program/required.php?userid='.$userid;

    $table = new flexible_table($tablename);
    $table->define_headers($tableheaders);
    $table->define_columns($tablecols);
    $table->define_baseurl($baseurl);
    $table->set_attribute('class', 'fullwidth generalbox');
    $table->set_control_variables(array(
        TABLE_VAR_SORT    => 'tsort',
    ));
    $table->sortable(true);
    $table->no_sorting('progress');

    $table->setup();
    $table->pagesize(15, $count);
    $sort = $table->get_sql_sort();
    $sort = empty($sort) ? '' : ' ORDER BY '.$sort;

    // Add table data
    $programs = prog_get_required_programs($userid, $sort, $table->get_page_start(), $table->get_page_size(), false, true);

    if (!$programs) {
        return '';
    }
    $rowcount = 0;
    foreach ($programs as $p) {
        if (!prog_is_accessible($p)) {
            continue;
        }
        $row = array();
        $row[] = prog_display_summary_widget($p, $userid);
        $row[] = prog_display_duedate($p->duedate, $p->id, $userid);
        $row[] = prog_display_progress($p->id, $userid);
        $table->add_data($row);
        $rowcount++;
    }

    unset($programs);

    if ($rowcount > 0) {
        //2.2 flexible_table class no longer supports $table->data and echos directly on each call to add_data
        ob_start();
        $table->finish_html();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    } else {
        return '';
    }
}

/**
 * Return markup for displaying a table of a specified user's certification programs
 * This includes hidden programs but excludes unavailable programs
 *
 * @param   int $userid     Program assignee
 * @return  string
 */
function prog_display_certification_programs($userid) {

    $count = prog_get_certification_programs($userid, '', '', '', true, true, true);

    // Set up table
    $tablename = 'progs-list-cert';
    $tableheaders = array(get_string('certificationname', 'shezar_program'));
    $tablecols = array('progname');

    // Due date
    $tableheaders[] = get_string('duedate', 'shezar_program');
    $tablecols[] = 'duedate';

    // Progress
    $tableheaders[] = get_string('progress', 'shezar_program');
    $tablecols[] = 'progress';

    $baseurl = new moodle_url('/shezar/program/required.php', array('userid' => $userid));

    $table = new flexible_table($tablename);
    $table->define_headers($tableheaders);
    $table->define_columns($tablecols);
    $table->define_baseurl($baseurl);
    $table->set_attribute('class', 'fullwidth generalbox');
    $table->set_control_variables(array(
        TABLE_VAR_SORT    => 'tsort',
    ));
    $table->sortable(true);
    $table->no_sorting('progress');

    $table->setup();
    $table->pagesize(15, $count);
    $sort = $table->get_sql_sort();
    $sort = empty($sort) ? '' : ' ORDER BY '.$sort;

    // Add table data
    $cprograms = prog_get_certification_programs($userid, $sort, $table->get_page_start(), $table->get_page_size(),
            false, true, true);

    if (!$cprograms) {
        return '';
    }

    $rowcount = 0;
    foreach ($cprograms as $cp) {
        if (!prog_is_accessible($cp)) {
            continue;
        }
        $row = array();
        $row[] = prog_display_summary_widget($cp, $userid);
        if (!empty($cp->timeexpires)) {
            $row[] = prog_display_duedate($cp->timeexpires, $cp->id, $userid, $cp->certifpath, $cp->status);
        } else {
            $row[] = prog_display_duedate($cp->duedate, $cp->id, $userid, $cp->certifpath, $cp->status);
        }
        $row[] = prog_display_progress($cp->id, $userid, $cp->certifpath);
        $table->add_data($row);
        $rowcount++;
    }

    unset($cprograms);

    if ($rowcount > 0) {
        //2.2 flexible_table class no longer supports $table->data and echos directly on each call to add_data
        ob_start();
        $table->finish_html();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    } else {
        return '';
    }
}

/**
 * Display the user message box
 *
 * @access public
 * @param  int    $programuser the id of the user
 * @return string $out      the display code
 */
function prog_display_user_message_box($programuser) {
    global $CFG, $PAGE, $DB;
    $user = $DB->get_record('user', array('id' => $programuser));
    if (!$user) {
        return false;
    }
    $user->courseid = 1;

    $a = new stdClass();
    $a->name = fullname($user);
    $a->userid = $programuser;
    $a->site = $CFG->wwwroot;

    $renderer = $PAGE->get_renderer('shezar_program');
    $out = $renderer->display_user_message_box($user, $a);
    return $out;
}

/**
 * Add lowest levels of breadcrumbs to program
 *
 * @return void
 */
function prog_add_base_navlinks() {
    global $PAGE;

    $PAGE->navbar->add(get_string('browsecategories', 'shezar_program'), new moodle_url('/shezar/program/index.php'));
}

/**
 * Add lowest levels of breadcrumbs to required learning
 *
 * Exact links added depends on if the require learning being viewed belongs
 * to the current user or not.
 *
 * @param array &$navlinks The navlinks array to update (passed by reference)
 * @param integer $userid ID of the required learning's owner
 *
 * @return boolean True if it is the user's own required learning
 */
function prog_add_required_learning_base_navlinks($userid) {
    global $USER, $PAGE, $DB;

    // the user is viewing their own learning
    if ($userid == $USER->id) {
        $PAGE->navbar->add(get_string('requiredlearning', 'shezar_program'), new moodle_url('/shezar/program/required.php'));
        return true;
    }

    // the user is viewing someone else's learning
    $user = $DB->get_record('user', array('id' => $userid));
    if ($user) {
        if (shezar_feature_visible('myteam')) {
            $PAGE->navbar->add(get_string('team', 'shezar_core'), new moodle_url('/my/teammembers.php'));
        }
        $PAGE->navbar->add(get_string('xsrequiredlearning', 'shezar_program', fullname($user)), new moodle_url('/shezar/program/required.php', array('userid' => $userid)));
    } else {
        $PAGE->navbar->add(get_string('unknownusersrequiredlearning', 'shezar_program'), new moodle_url('/shezar/program/required.php', array('userid' => $userid)));
    }

    return true;
}

/**
 * Returns list of programs, for whole site, or category
 *
 * Note: Cannot use p.* in $fields because MSSQL does not handle DISTINCT text fields.
 * See T-11732
 */
function prog_get_programs($categoryid="all", $sort="p.sortorder ASC",
                           $fields="p.id, p.category, p.sortorder, p.shortname, p.fullname, p.visible, p.icon, p.audiencevisible",
                           $type = 'program', $options = array()) {
    global $USER, $DB, $CFG;
    require_once($CFG->dirroot . '/shezar/cohort/lib.php');

    $offset = !empty($options['offset']) ? $options['offset'] : 0;
    $limit = !empty($options['limit']) ? $options['limit'] : null;
    $userid = !empty($options['userid']) ? $options['userid'] : $USER->id;

    $params = array('contextlevel' => CONTEXT_PROGRAM);
    $isprogram = ($type === 'program');
    $wheresql = $isprogram ? " p.certifid IS NULL" : " p.certifid IS NOT NULL";

    if ((int)$categoryid > 0) {
        $wheresql .= " AND p.category = :category";
        $params['category'] = (int)$categoryid;
    }

    if (empty($sort)) {
        $sortstatement = "";
    } else {
        $sortstatement = "ORDER BY $sort";
    }

    // Manage visibility.
    list($visibilityjoinsql, $visibilityjoinparams) = shezar_visibility_join($userid, $type, 'p');
    $params = array_merge($params, $visibilityjoinparams);

    // Get context data for preload.
    $ctxfields = context_helper::get_preload_record_columns_sql('ctx');
    $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = p.id AND ctx.contextlevel = :contextlevel)";

    // Get all programs matching the criteria, with additional visibility info.
    $sql = "SELECT DISTINCT {$fields}, {$ctxfields}, visibilityjoin.isvisibletouser
              FROM {prog} p
                   {$visibilityjoinsql}
                   {$ctxjoin}
             WHERE {$wheresql} {$sortstatement}";
    $programs = $DB->get_records_sql($sql, $params, $offset, $limit);

    // Remove programs that aren't visible.
    foreach ($programs as $id => $program) {
        if ($program->isvisibletouser) {
            unset($program->isvisibletouser); // Visible.
        } else {
            context_helper::preload_from_record($program);
            $context = context_program::instance($id);
            if ($isprogram && has_capability('shezar/program:viewhiddenprograms', $context) ||
                !$isprogram && has_capability('shezar/certification:viewhiddencertifications', $context) ||
                !empty($CFG->audiencevisibility) && has_capability('shezar/coursecatalog:manageaudiencevisibility', $context)) {
                unset($program->isvisibletouser); // Visible.
            } else {
                unset($programs[$id]); // Not visible.
            }
        }
    }

    return $programs;
}

/**
 * Gets the path of breadcrumbs for a category path matching $categoryid
 *
 * @param integer $categoryid The id of the current category
 * @param string $viewtype Type of the page
 * @return array Multidimensional array containing name, link, and type of breadcrumbs
 *
 */
function prog_get_category_breadcrumbs($categoryid, $viewtype = 'program') {
    global $CFG, $DB;

    $category = $DB->get_record('course_categories', array('id' => $categoryid));

    if (strpos($category->path, '/') === false) {
        return array();
    }

    $bread = explode('/', substr($category->path, 1));
    list($breadinsql, $params) = $DB->get_in_or_equal($bread);
    $sql = "SELECT id, name FROM {course_categories} WHERE id {$breadinsql} ORDER BY depth";
    $cat_bread = array();

    if ($bread_info = $DB->get_records_sql($sql, $params)) {
        foreach ($bread_info as $crumb) {
            $cat_bread[] = array('name' => format_string($crumb->name),
                                 'link' => new moodle_url("/shezar/program/index.php",
                                                 array('categoryid' => $crumb->id,
                                                       'viewtype' => $viewtype)),
                                 'type' => 'misc');

        }
    }
    return $cat_bread;
}

/**
 * Returns list of courses and programs, for whole site, or category
 * (This is the counterpart to get_courses_page in /lib/datalib.php)
 *
 * Similar to prog_get_programs, but allows paging
 *
 */
function prog_get_programs_page($categoryid="all", $sort="sortorder ASC",
                          $fields="p.id,p.sortorder,p.shortname,p.fullname,p.summary,p.visible",
                          &$totalcount, $limitfrom="", $limitnum="", $type = 'program') {
    global $CFG, $DB;

    $params = array();
    $categoryselect = "";
    if ($categoryid != "all" && is_numeric($categoryid)) {
        $categoryselect = " AND p.category = :cat ";
        $params['cat'] = $categoryid;
    }

    $isprogram = ($type === 'program');
    $typesql = $isprogram ? " p.certifid IS NULL" : " p.certifid IS NOT NULL";

    // Visibility.
    list($visibilityjoinsql, $visibilityjoinparams) = shezar_visibility_join(null, $type, 'p');
    $params = array_merge($params, $visibilityjoinparams);

    // Get context data for preload.
    $ctxfields = context_helper::get_preload_record_columns_sql('ctx');
    $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = p.id AND ctx.contextlevel = :contextlevel)";
    $params['contextlevel'] = CONTEXT_PROGRAM;

    // Pull out all programs matching the cat.
    $visibleprograms = array();

    $progselect = "SELECT {$fields}, 'program' AS listtype, {$ctxfields}, visibilityjoin.isvisibletouser
                     FROM {prog} p
                          {$visibilityjoinsql}
                          {$ctxjoin}
                    WHERE {$typesql}";

    $select = $progselect.$categoryselect.' ORDER BY '.$sort;
    $rs = $DB->get_recordset_sql($select, $params);

    $totalcount = 0;
    $visiblecount = 0;

    if (!$limitfrom) {
        $limitfrom = 0;
    }

    // Iterate through the records until enough have been found, skipping those that are not visible.
    foreach ($rs as $program) {
        $visible = false;
        if ($program->isvisibletouser) {
            $visible = true;
        } else {
            context_helper::preload_from_record($program);
            $context = context_program::instance($program->id);
            if ($isprogram && has_capability('shezar/program:viewhiddenprograms', $context) ||
                !$isprogram && has_capability('shezar/certification:viewhiddencertifications', $context) ||
                !empty($CFG->audiencevisibility) && has_capability('shezar/coursecatalog:manageaudiencevisibility', $context)) {
                $visible = true;
            }
        }
        if ($visible) {
            $totalcount++;
            if ($totalcount > $limitfrom && (!$limitnum || $visiblecount < $limitnum)) {
                unset($program->isvisibletouser);
                $visibleprograms [] = $program;
                $visiblecount++;
            }
        }
    }

    $rs->close();

    return $visibleprograms;
}

/**
 * Efficiently moves many programs around while maintaining
 * sortorder in order.
 * (This is the counterpart to move_courses in /course/lib.php)
 *
 * $programids is an array of program ids
 *
 **/
function prog_move_programs($programids, $categoryid) {
    global $DB, $OUTPUT;

    if (!empty($programids)) {

            $programids = array_reverse($programids);

            foreach ($programids as $programid) {

                if (!$program  = $DB->get_record("prog", array("id" => $programid))) {
                    echo $OUTPUT->notification(get_string('error:findingprogram', 'shezar_program'));
                } else {
                    // figure out a sortorder that we can use in the destination category
                    $sortorder = $DB->get_field_sql('SELECT MIN(sortorder)-1 AS min
                                                     FROM {prog}
                                                     WHERE category = ?', array($categoryid));
                    if (is_null($sortorder) || $sortorder === false) {
                        // the category is empty
                        // rather than let the db default to 0
                        // set it to > 100 and avoid extra work in fix_program_sortorder()
                        $sortorder = 200;
                    } else if ($sortorder < 10) {
                        prog_fix_program_sortorder($categoryid);
                    }

                    $program->category  = $categoryid;
                    $program->sortorder = $sortorder;

                    if (!$DB->update_record('prog', $program)) {
                        echo $OUTPUT->notification(get_string('error:prognotmoved', 'shezar_program'));
                    }

                    $context   = context_program::instance($program->id);
                    $newparent = context_coursecat::instance($program->category);
                    $context->update_moved($newparent);
                }
            }
            prog_fix_program_sortorder();
        }
    return true;
}

/**
 * This recursive function makes sure that the program order is consecutive
 * (This is the counterpart to fix_course_sortorder in /lib/datalib.php)
 *
 * $n is the starting point, offered only for compatilibity -- will be ignored!
 * $safe (bool) prevents it from assuming category-sortorder is unique, used to upgrade
 * safely from 1.4 to 1.5
 *
 * @global <type> $CFG
 * @param <type> $categoryid
 * @param <type> $n
 * @param <type> $safe
 * @param <type> $depth
 * @param <type> $path
 * @return <type>
 */
function prog_fix_program_sortorder($categoryid=0, $n=0, $safe=0, $depth=0, $path='') {

    global $DB;

    $counters = new stdClass();
    $counters->programcount = 0;
    $counters->certifcount = 0;
    $count = 0;

    $catgap    = 1000; // "standard" category gap
    $tolerance = 200;  // how "close" categories can get

    if ($categoryid > 0){
        // update depth and path
        $cat   = $DB->get_record('course_categories', array('id' => $categoryid));
        if ($cat->parent == 0) {
            $depth = 0;
            $path  = '';
        } else if ($depth == 0 ) { // doesn't make sense; get from DB
            // this is only called if the $depth parameter looks dodgy
            $parent = $DB->get_record('course_categories', array('id' => $cat->parent));
            $path  = $parent->path;
            $depth = $parent->depth;
        }
        $path  = $path . '/' . $categoryid;
        $depth = $depth + 1;

        if ($cat->path !== $path) {
            $DB->set_field('course_categories', 'path', $path, array('id' => $categoryid));
        }
        if ($cat->depth != $depth) {
            $DB->set_field('course_categories', 'depth', $depth, array('id' => $categoryid));
        }
    }

    // get some basic info about programs in the category
    $info = $DB->get_record_sql('SELECT MIN(sortorder) AS min,
                                        MAX(sortorder) AS max,
                                        COUNT(sortorder) AS count,
                                        COALESCE(SUM(CASE WHEN certifid IS NULL THEN 1 ELSE 0 END),0) AS programcount,
                                        COALESCE(SUM(CASE WHEN certifid IS NULL THEN 0 ELSE 1 END),0) AS certifcount
                                   FROM {prog}
                                  WHERE category = ?', array($categoryid));
    if (is_object($info)) { // no courses?
        $max   = $info->max;
        $counters->programcount = $info->programcount;
        $counters->certifcount = $info->certifcount;
        $count = $info->count;
        $min   = $info->min;
        unset($info);
    }

    if ($categoryid > 0 && $n == 0) { // only passed category so don't shift it
        $n = $min;
    }

    // $hasgap flag indicates whether there's a gap in the sequence
    $hasgap    = false;
    if ($max-$min+1 != $count) {
        $hasgap = true;
    }

    // $mustshift indicates whether the sequence must be shifted to
    // meet its range
    $mustshift = false;
    if ($min < $n-$tolerance || $min > $n+$tolerance+$catgap ) {
        $mustshift = true;
    }

    // actually sort only if there are programs,
    // and we meet one ofthe triggers:
    //  - safe flag
    //  - they are not in a continuos block
    //  - they are too close to the 'bottom'
    if ($count && ( $safe || $hasgap || $mustshift ) ) {
        // special, optimized case where all we need is to shift
        if ($mustshift && !$safe && !$hasgap) {
            $shift = $n + $catgap - $min;
            if ($shift < $count) {
                $shift = $count + $catgap;
            }

            $DB->execute("UPDATE {prog}
                          SET sortorder = sortorder + ?
                          WHERE category = ?", array($shift, $categoryid));
            $n = $n + $catgap + $count;

        } else { // do it slowly
            $n = $n + $catgap;
            // if the new sequence overlaps the current sequence, lack of transactions
            // will stop us -- shift things aside for a moment...
            if ($safe || ($n >= $min && $n+$count+1 < $min && $DB->get_dbfamily() === 'mysql')) {
                $shift = $max + $n + 1000;
                $DB->execute("UPDATE {prog}
                              SET sortorder = sortorder+$shift
                              WHERE category = ?". array($categoryid));
            }

            $programs = prog_get_programs($categoryid, 'p.sortorder ASC', 'p.id,p.sortorder');

            $transaction = $DB->start_delegated_transaction();

            $tx = true; // transaction sanity
            foreach ($programs as $program) {
                if ($tx && $program->sortorder != $n ) { // save db traffic
                    $tx = $tx && $DB->set_field('prog', 'sortorder', $n, array('id' => $program->id));
                }
                $n++;
            }
            if ($tx) {
                $transaction->allow_commit();
            } else {
                if (!$safe) {
                    // if we failed when called with !safe, try
                    // to recover calling self with safe=true
                    return prog_fix_program_sortorder($categoryid, $n, true, $depth, $path);
                }
            }
        }
    }
    if ($categoryid) {
        $counters->id = $categoryid;
        $DB->update_record('course_categories', $counters);
    }

    // $n could need updating
    $max = $DB->get_field_sql("SELECT MAX(sortorder)
                               FROM {prog}
                               WHERE category = ?", array($categoryid));
    if ($max > $n) {
        $n = $max;
    }

    if ($categories = coursecat::get($categoryid)->get_children()) {
        foreach ($categories as $category) {
            $n = prog_fix_program_sortorder($category->id, $n, $safe, $depth, $path);
        }
    }

    return $n+1;
}

/**
 * Checks whether or not a user should have access to a course that belongs to a
 * program in the user's required learning. If so, the user will be automatically
 * enrolled onto the course as a student.
 *
 * @global object $CFG
 * @param object $user
 * @param object $course
 * @return object $result containing properties:
 *         'enroled' (boolean: whether user is enroled on the course)
 *         'notify' (boolean: whether a new enrolment has been made so notify user)
 *         'program' (string: name of program they have obtained access through)
 */
function prog_can_enter_course($user, $course) {
    global $DB;

    $result = new stdClass();
    $result->enroled = false;
    $result->notify = false;
    $result->program = null;

    // Get program enrolment plugin class, and default role.
    $program_plugin = enrol_get_plugin('shezar_program');
    $defaultrole = $program_plugin->get_config('roleid');
    if (empty($defaultrole)) {
        return $result;
    }

    // Get programs containing this course that this user is assigned to, either via learning plans or required learning
    $get_programs = "
        SELECT p.*
          FROM {prog} p
          WHERE p.available = ?
          AND (
              p.id IN
              (
                SELECT DISTINCT pc.programid
                  FROM {dp_plan_program_assign} pc
            INNER JOIN {dp_plan} pln ON pln.id = pc.planid
             LEFT JOIN {prog_courseset} pcs ON pc.programid = pcs.programid
             LEFT JOIN {prog_courseset_course} pcsc ON pcs.id = pcsc.coursesetid AND pcsc.courseid = ?
                 WHERE pc.approved >= ?
                   AND pln.userid = ?
                   AND pln.status = ?
             )
            OR p.id IN
             (
                SELECT DISTINCT pua.programid
                  FROM {prog_user_assignment} pua
             LEFT JOIN {prog_completion} pc
                    ON pua.programid = pc.programid AND pua.userid = pc.userid
             LEFT JOIN {prog_courseset} pcs ON pua.programid = pcs.programid
             LEFT JOIN {prog_courseset_course} pcsc ON pcs.id = pcsc.coursesetid AND pcsc.courseid = ?
                 WHERE pua.userid = ?
                   AND pc.coursesetid = ?
                   AND (pc.timedue = ?
                        OR pc.status <> ? )
             ))
    ";
    $params = array(AVAILABILITY_TO_STUDENTS, $course->id, DP_APPROVAL_APPROVED, $user->id, DP_PLAN_STATUS_APPROVED, $course->id, $user->id, 0, COMPLETION_TIME_NOT_SET, STATUS_PROGRAM_COMPLETE);
    $program_records = $DB->get_records_sql($get_programs, $params);

    if (!empty($program_records)) {
        foreach ($program_records as $program_record) {
            $program = new program($program_record->id);
            if (prog_is_accessible($program_record) && $program->can_enter_course($user->id, $course->id)) {
                //check if program enrolment plugin is enabled on this course
                //should be added when coursesets are created but just in case we'll double-check
                $instance = $program_plugin->get_instance_for_course($course->id);
                if (!$instance) {
                    //add it
                    $instanceid = $program_plugin->add_instance($course);
                    $instance = $DB->get_record('enrol', array('id' => $instanceid));
                }
                //check if user is already enroled under the program plugin
                if (!$ue = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $user->id))) {
                    //enrol them
                    $program_plugin->enrol_user($instance, $user->id, $defaultrole);
                    $result->enroled = true;
                    $result->notify = true;
                    $result->program = $program->fullname;
                } else {
                    //already enroled
                    $result->enroled = true;
                }
                return $result;
            }
        }
    }
    return $result;
}


/**
 * A list of programs that match a search
 *
 * @uses $DB, $USER
 * @param array $searchterms Terms to search
 * @param string $sort Sort order of the records
 * @param int $page
 * @param int $recordsperpage
 * @param int $totalcount Passed in by reference.
 * @param string $type Are we looking for programs or certifications
 * @return object {@link $COURSE} records
 */
function prog_get_programs_search($searchterms, $sort='fullname ASC', $page=0, $recordsperpage=50, &$totalcount, $type = 'program') {
    global $DB;

    $REGEXP    = $DB->sql_regex(true);
    $NOTREGEXP = $DB->sql_regex(false);

    $fullnamesearch = '';
    $summarysearch = '';
    $idnumbersearch = '';
    $shortnamesearch = '';

    $fullnamesearchparams = array();
    $summarysearchparams = array();
    $idnumbersearchparams = array();
    $shortnamesearchparams = array();
    $params = array();

    foreach ($searchterms as $searchterm) {
        if ($fullnamesearch) {
            $fullnamesearch .= ' AND ';
        }
        if ($summarysearch) {
            $summarysearch .= ' AND ';
        }
        if ($idnumbersearch) {
            $idnumbersearch .= ' AND ';
        }
        if ($shortnamesearch) {
            $shortnamesearch .= ' AND ';
        }

        if (substr($searchterm,0,1) == '+') {
            $searchterm      = substr($searchterm,1);
            $summarysearch  .= " p.summary $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $fullnamesearch .= " p.fullname $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $idnumbersearch  .= " p.idnumber $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $shortnamesearch  .= " p.shortname $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
        } else if (substr($searchterm,0,1) == "-") {
            $searchterm      = substr($searchterm,1);
            $summarysearch  .= " p.summary $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $fullnamesearch .= " p.fullname $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $idnumbersearch .= " p.idnumber $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $shortnamesearch .= " p.shortname $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
        } else {
            $summaryparam = rb_unique_param('summary');
            $summarysearch .= $DB->sql_like('summary', ":{$summaryparam}", false, true, false) . ' ';
            $summarysearchparams[$summaryparam] = '%' . $searchterm . '%';

            $fullnameparam = rb_unique_param('fullname');
            $fullnamesearch .= $DB->sql_like('fullname', ":{$fullnameparam}", false, true, false) . ' ';
            $fullnamesearchparams[$fullnameparam] = '%' . $searchterm . '%';

            $idnumberparam = rb_unique_param('idnumber');
            $idnumbersearch .= $DB->sql_like('idnumber', ":{$idnumberparam}", false, true, false) . ' ';
            $idnumbersearchparams[$idnumberparam] = '%' . $searchterm . '%';

            $shortnameparam = rb_unique_param('shortname');
            $shortnamesearch .= $DB->sql_like('shortname', ":{$shortnameparam}", false, true, false) . ' ';
            $shortnamesearchparams[$shortnameparam] = '%' . $searchterm . '%';
        }
    }

    // If search terms supplied, include in where.
    if (count($searchterms)) {
        $where = "
            WHERE (( $fullnamesearch ) OR ( $summarysearch ) OR ( $idnumbersearch ) OR ( $shortnamesearch ))
            AND category > 0
        ";
        $params = array_merge($params, $fullnamesearchparams, $summarysearchparams, $idnumbersearchparams, $shortnamesearchparams);
    } else {
        // Otherwise return everything.
        $where = " WHERE category > 0 ";
    }

    if ($type == 'program') {
        $where .= " AND p.certifid IS NULL"; // Filter out certifications.
    } else {
        $where .= " AND p.certifid IS NOT NULL";
    }

    // Add visibility query.
    list($visibilitysql, $visibilityparams) = shezar_visibility_where(null, 'p.id', 'p.visible', 'p.audiencevisible', 'p', $type);
    $params = array_merge($params, $visibilityparams);
    $sql = "SELECT p.*,
                   ctx.id AS ctxid, ctx.path AS ctxpath,
                   ctx.depth AS ctxdepth, ctx.contextlevel AS ctxlevel
            FROM {prog} p
            JOIN {context} ctx ON (p.id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_PROGRAM.")
            {$where} AND {$visibilitysql}
            ORDER BY {$sort}";

    $programs = array();

    $limitfrom = $page * $recordsperpage;
    $limitto   = $limitfrom + $recordsperpage;
    $c = 0; // Counts how many visible programs we've seen.

    $rs = $DB->get_recordset_sql($sql, $params);

    foreach ($rs as $program) {
        // Don't exit this loop till the end we need to count all the visible programs to update $totalcount.
        if ($c >= $limitfrom && $c < $limitto) {
            $programs[] = $program;
        }
        $c++;
    }

    $rs->close();

    // Our caller expects 2 bits of data - our return array, and an updated $totalcount.
    $totalcount = $c;
    return $programs;
}

/**
 * @deprecated since 9.0.
 * @param $assignment
 */
function prog_store_position_assignment($assignment) {
    throw new coding_exception('prog_store_position_assignment has been deprecated since 9.0. Use \shezar_job\job_assignment::update() instead.');
}

/**
 * Retrieves any recurring programs and returns them in an array or an empty
 * array
 *
 * @return array
 */
function prog_get_recurring_programs() {
    global $DB;
    $recurring_programs = array();

    // get all programs
    $program_records = $DB->get_records('prog');
    foreach ($program_records as $program_record) {
        $program = new program($program_record->id);
        $content = $program->get_content();
        $coursesets = $content->get_course_sets();

        if ((count($coursesets) == 1) && ($coursesets[0]->is_recurring())) {
            $recurring_programs[] = $program;
        }
    }

    return $recurring_programs;
}


function prog_get_tab_link($userid) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    $progtable = new xmldb_table('prog');
    if ($dbman->table_exists($progtable)) {
        $programcount = prog_get_required_programs($userid, '', '', '', true, true);
        $certificationcount = prog_get_certification_programs($userid, '', '', '', true, true, true);
        $requiredlearningcount = $programcount + $certificationcount;
        if ($requiredlearningcount == 1) {
            if ($programcount == 1) {
                $program = prog_get_required_programs($userid, '', '', '', false, true);
            } else {
                $program = prog_get_certification_programs($userid, '', '', '', false, true, true);
            }
            $program = reset($program); // resets array pointer and returns value of first element
            if (!prog_is_accessible($program)) {
                return false;
            }
            return $CFG->wwwroot . '/shezar/program/required.php?id=' . $program->id;
        } else if ($requiredlearningcount > 1) {
            return $CFG->wwwroot . '/shezar/program/required.php';
        }
    }

    return false;
}


/*


/**
 * Processes extension request to grant or deny them given
 * an array of exceptions and the action to take
 *
 * @param array $extensionslist list of extension ids and actions in the form array(id => action)
 * @param array $reasonfordecision Reason for granting or denying the extension
 * @return array Contains count of extensions processed and number of failures
 */
function prog_process_extensions($extensionslist, $reasonfordecision = array()) {
    global $CFG, $DB, $USER;

    if (empty($CFG->enableprogramextensionrequests)) {
        print_error('error:notextensionallowed', 'shezar_program');
    }

    if (!empty($extensionslist)) {
        $update_fail_count = 0;
        $update_extension_count = 0;

        // Get valid extensions to process. Extensions that are in prog_extensions and extensions for programs that allow them.
        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($extensionslist));
        $inparams[] = 1;
        $sql = "SELECT pe.*
                  FROM {prog_extension} pe
            INNER JOIN {prog} p
                    ON pe.programid = p.id
                 WHERE pe.id {$insql}
                   AND p.allowextensionrequests = ?";
        $extensions = $DB->get_records_sql($sql, $inparams);

        // Update fail count in case some of them are not valid at this point.
        $update_fail_count = count($extensionslist) - count($extensions);

        foreach ($extensions as $extension) {
            $id = $extension->id;
            $action = $extensionslist[$extension->id];

            if ($action == 0) {
                continue;
            }

            $update_extension_count++;

            // Ensure that the message is actually coming from $user's manager.
            if (\shezar_job\job_assignment::is_managing($USER->id, $extension->userid)) {
                $userfrom = $USER;
            } else {
                print_error('error:notusersmanager', 'shezar_program');
            }

            if ($action == PROG_EXTENSION_DENY) {

                $userto = $DB->get_record('user', array('id' => $extension->userid));
                $stringmanager = get_string_manager();

                $program = $DB->get_record('prog', array('id' => $extension->programid), 'fullname');

                $messagedata = new stdClass();
                $messagedata->userto           = $userto;
                $messagedata->userfrom         = $userfrom;
                $messagedata->subject          = $stringmanager->get_string('extensiondenied', 'shezar_program', fullname($USER), $userto->lang);
                $messagedata->contexturl       = $CFG->wwwroot.'/shezar/program/required.php?id='.$extension->programid;
                $messagedata->contexturlname   = $stringmanager->get_string('launchprogram', 'shezar_program', null, $userto->lang);
                $messagedata->fullmessage      = $stringmanager->get_string('extensiondeniedmessage', 'shezar_program', $program->fullname, $userto->lang);
                $messagedata->icon             = 'program-decline';
                $messagedata->msgtype          = shezar_MSG_TYPE_PROGRAM;

                if (!empty($reasonfordecision[$id])) {
                    // Add reason to the message.
                    $messagedata->fullmessage  .= html_writer::empty_tag('br') . html_writer::empty_tag('br');
                    $messagedata->fullmessage  .= $stringmanager->get_string('reasondeniedmessage', 'shezar_program', $reasonfordecision[$id], $userto->lang);
                }

                $eventdata = new stdClass();
                $eventdata->message = $messagedata;

                if ($result = tm_alert_send($messagedata)) {

                    $extension_todb = new stdClass();
                    $extension_todb->id = $extension->id;
                    $extension_todb->status = PROG_EXTENSION_DENY;
                    $extension_todb->reasonfordecision = $reasonfordecision[$id];

                    if (!$DB->update_record('prog_extension', $extension_todb)) {
                        $update_fail_count++;
                    }
                    \shezar_program\event\extension_denied::create_from_instance($extension)->trigger();
                } else {
                    print_error('error:failedsendextensiondenyalert', 'shezar_program');
                }
            } elseif ($action == PROG_EXTENSION_GRANT) {
                // Load the program for this extension
                $extension_program = new program($extension->programid);

                if ($prog_completion = $DB->get_record('prog_completion', array('programid' => $extension_program->id, 'userid' => $extension->userid, 'coursesetid' => 0))) {
                    $duedate = empty($prog_completion->timedue) ? 0 : $prog_completion->timedue;

                    if ($extension->extensiondate < $duedate) {
                        $update_fail_count++;
                        continue;
                    }
                }

                $now = time();
                if ($extension->extensiondate < $now) {
                    $update_fail_count++;
                    continue;
                }

                // Try to update due date for program using extension date
                if (!$extension_program->set_timedue($extension->userid, $extension->extensiondate)) {
                    $update_fail_count++;
                    continue;
                } else {
                    $userto = $DB->get_record('user', array('id' => $extension->userid));
                    if (!$userto) {
                        print_error('error:failedtofinduser', 'shezar_program', $extension->userid);
                    }

                    // Ensure the message is actually coming from $user's manager, default to support.
                    $userfrom = \shezar_job\job_assignment::is_managing($USER->id, $extension->userid, $USER->id) ? $USER : core_user::get_support_user();
                    $stringmanager = get_string_manager();
                    $messagedata = new stdClass();
                    $messagedata->userto           = $userto;
                    $messagedata->userfrom         = $userfrom;
                    $messagedata->subject          = $stringmanager->get_string('extensiongranted', 'shezar_program', fullname($USER), $userto->lang);
                    $messagedata->contexturl       = $CFG->wwwroot.'/shezar/program/required.php?id='.$extension->programid;
                    $messagedata->contexturlname   = $stringmanager->get_string('launchprogram', 'shezar_program', null, $userto->lang);
                    $messagedata->fullmessage      = $stringmanager->get_string('extensiongrantedmessage', 'shezar_program',
                        userdate($extension->extensiondate, get_string('strftimedatetime', 'langconfig'), core_date::get_user_timezone($userto)),
                        $userto->lang);
                    $messagedata->icon             = 'program-approve';
                    $messagedata->msgtype          = shezar_MSG_TYPE_PROGRAM;

                    if (!empty($reasonfordecision[$id])) {
                        // Add reason to the message.
                        $messagedata->fullmessage  .= html_writer::empty_tag('br') . html_writer::empty_tag('br');
                        $messagedata->fullmessage  .= $stringmanager->get_string('reasonapprovedmessage', 'shezar_program', $reasonfordecision[$id], $userto->lang);
                    }

                    if ($result = tm_alert_send($messagedata)) {

                        $extension_todb = new stdClass();
                        $extension_todb->id = $extension->id;
                        $extension_todb->status = PROG_EXTENSION_GRANT;
                        $extension_todb->reasonfordecision = $reasonfordecision[$id];

                        if (!$DB->update_record('prog_extension', $extension_todb)) {
                            $update_fail_count++;
                        }
                        \shezar_program\event\extension_granted::create_from_instance($extension)->trigger();
                    } else {
                        print_error('error:failedsendextensiongrantalert','shezar_program');
                    }
                 }
            }
        }
        return array('total' => $update_extension_count, 'failcount' => $update_fail_count, 'updatefailcount' => $update_fail_count);
    }
    return array();
}

/**
 * Update program completion status for particular user
 *
 * @param int $userid
 * @param program $program if not set - all programs will be updated
 */
function prog_update_completion($userid, program $program = null) {
    global $DB;

    if (!$program) {
        $proglist = prog_get_all_programs($userid, '', '', '', false, true);
        $programs = array();
        foreach ($proglist as $progrow) {
            $programs[] = new program($progrow->id);
        }
    } else {
        $programs = array($program);
    }

    foreach ($programs as $program) {
        // Get the program content.
        $program_content = $program->get_content();

        if ($program->certifid) {
            // If this is a certification program get course sets for groups on the path the user is on.
            $path = get_certification_path_user($program->certifid, $userid);
        } else {
            // If standard program get the courseset groups (just one path).
            $path = CERTIFPATH_STD;
        }
        $courseset_groups = $program_content->get_courseset_groups($path);

        // First check if the program is already marked as complete for this user and do nothing if it is.
        if (prog_is_complete($program->id, $userid)) {
            continue;
        }

        $courseset_group_completed = false;
        $previous_courseset_group_completed = false;

        // Go through the course set groups to determine the user's completion status.
        foreach ($courseset_groups as $courseset_group) {
            $courseset_group_completed = prog_courseset_group_complete($courseset_group, $userid);

            if ($courseset_group_completed) {
                $previous_courseset_group_completed = true;
            } else {
                // If the user has not completed the course group the program is not complete.
                // Set the timedue for the course set in this group with the shortest
                // time allowance so that course set due reminders will be triggered
                // at the appropriate time.
                if ($previous_courseset_group_completed) {
                    $program_content->set_courseset_group_timedue($courseset_group, $userid);
                    $previous_courseset_group_completed = false;
                }
                break;
            }
        }

        // Courseset_group_completed will be true if all the course groups in the program have been completed.
        if ($courseset_group_completed) {
            // Get maximum completion date of the coursesets in the current path.
            $sql = "SELECT MAX(pc.timecompleted) AS timecompleted
                      FROM {prog_completion} pc
                      JOIN {prog_courseset} pcs ON pcs.id = pc.coursesetid
                     WHERE pc.programid = ? AND pc.userid = ? AND pcs.certifpath = ?";
            $params = array($program->id, $userid, $path);
            $coursesetcompletion = $DB->get_record_sql($sql, $params);

            $completionsettings = array(
                'status'        => STATUS_PROGRAM_COMPLETE,
                'timecompleted' => $coursesetcompletion->timecompleted
                );
            $program->update_program_complete($userid, $completionsettings);
        }
    }
}

/**
 * Check if a courseset group is completed and optionally update courseset completion.
 *
 * @throws ProgramException
 * @param array $courseset_group a group of coursesets, as returned by get_courseset_groups
 * @param int $userid of the user for which completion should be checked/updated
 * @param boolean $updatecomplete also update courseset completion
 *
 * @return boolean true/false, dependent on whether the courseset group is complete or not
 */
function prog_courseset_group_complete($courseset_group, $userid, $updatecomplete = true) {

    // Keep track of the state of the last run of "and"ed courses.
    $accumulator = true;

    $last = end($courseset_group); // PHP7 do not use end() inside foreach!

    foreach ($courseset_group as $courseset) {
        // First check if the course set is already marked as complete.
        if ($courseset->is_courseset_complete($userid)) {
            $coursesetcomplete = true;
        } else if (!$updatecomplete) {
            $coursesetcomplete = false;
        } else {
            // Otherwise carry out a check to see if the course set should be marked as complete and mark it as complete if so.
            if ($courseset->check_courseset_complete($userid)) {
                $coursesetcomplete = true;
            } else {
                $coursesetcomplete = false;
            }
        }

        // Combine the current set into the "and" accumulator.
        $accumulator = $accumulator && $coursesetcomplete;

        switch ($courseset->nextsetoperator) {
            case NEXTSETOPERATOR_AND:
                // Do nothing. The current result was added to the accumulator and the next result will be added as well.
                break;
            case NEXTSETOPERATOR_OR:
                if ($accumulator) {
                    // The last run of "and"ed course sets were all true.
                    // We can stop now because the final result must be true.
                    return true;
                } else {
                    // The last run of "and"ed course sets must have had at least one incomplete.
                    // Reset the accumulator, starting a new run of "and"ed course sets.
                    $accumulator = true;
                }
                break;
            case NEXTSETOPERATOR_THEN:
            default:
                if ($courseset == $last) {
                    // This is the last course set. The final result is determined by the last run of "and"ed course sets.
                    return $accumulator;
                } else {
                    // We got THEN or no operator, but it wasn't at the end of the course set group.
                    throw new ProgramException(get_string('error:invalidcoursesetgroupoperator', 'shezar_program'));
                }
                break;
        }
    }

    throw new ProgramException(get_string('error:nextcoursesetmissing', 'shezar_program'));
}

/**
 * This function is to cope with program assignments set up
 * with completion deadlines 'from first login' where the
 * user had not yet logged in.
 *
 * Used by program_hourly_cron and observer for first user login event.
 *
 * @param int $user User object to check first firstlogin for
 * @return boolean True if all the update_learner_assignments() succeeded or there was nothing to do
 */
function prog_assignments_firstlogin($user) {
    global $DB;

    // NOTE: in theory this might get called multiple times at the same time,
    //       please make sure it would not result in fatal errors.

    $status = true;

    /* Future assignments for this user that can now be processed
     * (because this user has logged in)
     * we are looking for:
     * - future assignments for this user
     * - that relate to a "first login" assignment
     */
    $rs = $DB->get_recordset_sql(
        "SELECT pfua.* FROM
            {prog_future_user_assignment} pfua
        LEFT JOIN
            {prog_assignment} pa
            ON pfua.assignmentid = pa.id
        WHERE
            pfua.userid = ?
            AND pa.completionevent = ?"
    , array($user->id, COMPLETION_EVENT_FIRST_LOGIN));
    // Group the future assignments by 'programid'.
    $pending_by_program = shezar_group_records($rs, 'programid');

    if ($pending_by_program) {
        foreach ($pending_by_program as $programid => $assignments) {

            // Update each program.
            $program = new program($programid);
            if ($program->update_learner_assignments()) {
                // If the update succeeded, delete the future assignments related to this program.
                $future_assignments_to_delete = array();
                foreach ($assignments as $assignment) {
                    $future_assignments_to_delete[] = $assignment->id;
                }
                if (!empty($future_assignments_to_delete)) {
                    list($deleteids_sql, $deleteids_params) = $DB->get_in_or_equal($future_assignments_to_delete);
                    $DB->delete_records_select('prog_future_user_assignment', "id {$deleteids_sql}", $deleteids_params);
                }
            } else {
                $status = false;
            }
        }
    }

    return $status;
}

/**
 * Returns an array of course objects for all the courses which
 * are part of any program.
 *
 * If an array of courseids are provided, the query is restricted
 * to only check for those courses
 *
 * @param array $courses Array of courseids to check for (optional) Defaults to all courses
 * @return array Array of course objects
 */
function prog_get_courses_associated_with_programs($courses = null) {
    global $DB;

    $limitcourses = (isset($courses) && is_array($courses) && count($courses) > 0);

    // restrict by list of courses provided
    if ($limitcourses) {
        list($insql, $inparams) = $DB->get_in_or_equal($courses);
        $insql = " AND c.id $insql";
    } else {
        $insql = '';
        $inparams = array();
    }

    // get courses mentioned in the courseset_course tab, and also any courses
    // linked to competencies used in any courseset
    // always exclude the site course and optionally restrict to a selected list of courses

    //mssql fails because of the 'ntext not comparable' issue
    //so we have to use a subquery to perform union
    $subquery = "SELECT c.id FROM {prog_courseset_course} pcc
                INNER JOIN {course} c ON c.id = pcc.courseid
                WHERE c.id <> ? $insql
            UNION
                SELECT c.id FROM {course} c
                JOIN {comp_criteria} cc ON c.id = cc.iteminstance
                AND cc.itemtype = ?
                WHERE cc.competencyid IN
                    (SELECT DISTINCT competencyid FROM {prog_courseset} WHERE competencyid <> 0)
                AND c.id <> ? $insql";
    $sql = "SELECT * FROM {course} WHERE id IN ($subquery)";

    // build up the params array
    $params = array(SITEID);
    $params = array_merge($params, $inparams);
    $params[] = 'coursecompletion';
    $params[] = SITEID;
    $params = array_merge($params, $inparams);

    return $DB->get_records_sql($sql, $params);
}

function shezar_program_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options=array()) {
    $component = 'shezar_program';
    $itemid = $args[0];
    $filename = $args[1];
    $fs = get_file_storage();

    $file = $fs->get_file($context->id, $component, $filearea, $itemid, '/', $filename);

    if (empty($file)) {
        send_file_not_found();
    }

    send_stored_file($file, 60*60*24, 0, false, $options); //enable long cache and disable forcedownload
}

/**
 * Returns options to use in program overviewfiles filemanager
 *
 * @param null|stdClass|course_in_list|int $program either object that has 'id' property or just the course id;
 *     may be empty if course does not exist yet (course create form)
 * @return array|null array of options such as maxfiles, maxbytes, accepted_types, etc.
 *     or null if overviewfiles are disabled
 */
function prog_program_overviewfiles_options($program) {
    global $CFG;
    if (empty($CFG->courseoverviewfileslimit)) {
        return null;
    }
    $accepted_types = preg_split('/\s*,\s*/', trim($CFG->courseoverviewfilesext), -1, PREG_SPLIT_NO_EMPTY);
    if (in_array('*', $accepted_types) || empty($accepted_types)) {
        $accepted_types = '*';
    } else {
        // Since config for $CFG->courseoverviewfilesext is a text box, human factor must be considered.
        // Make sure extensions are prefixed with dot unless they are valid typegroups
        foreach ($accepted_types as $i => $type) {
            if (substr($type, 0, 1) !== '.') {
                require_once($CFG->libdir. '/filelib.php');
                if (!count(file_get_typegroup('extension', $type))) {
                    // It does not start with dot and is not a valid typegroup, this is most likely extension.
                    $accepted_types[$i] = '.'. $type;
                    $corrected = true;
                }
            }
        }
        if (!empty($corrected)) {
            set_config('courseoverviewfilesext', join(',', $accepted_types));
        }
    }
    $options = array(
                    'maxfiles' => $CFG->courseoverviewfileslimit,
                    'maxbytes' => $CFG->maxbytes,
                    'subdirs' => 0,
                    'accepted_types' => $accepted_types
    );
    if (!empty($program->id)) {
        $options['context'] = context_program::instance($program->id);
    } else if (is_int($program) && $program > 0) {
        $options['context'] = context_program::instance($program);
    }
    return $options;
}

/**
 * Returns true if the category has programs in it (count does not include programs
 * in child categories)
 *
 * @param coursecat $category
 * @return bool
 */
function prog_has_programs($category) {
    global $DB;
    return $DB->record_exists_sql("SELECT 1 FROM {prog} WHERE category = :category AND certifid IS NULL",
            array('category' => $category->id));
}

/** Returns number of programs visible to the user
 *
 * @param coursecat $category
 * @param string $type Program or certification
 * @return int
 */
function prog_get_programs_count($category, $type = 'program') {
    // We have no programs at site level.
    if ($category->id == 0) {
        return 0;
    }
    $programs = prog_get_programs($category->id, '', 'p.id', $type);
    return count($programs);
}

/**
 * Can the current user delete programs in this category?
 *
 * @param int $categoryid
 * @return boolean
 */
function prog_can_delete_programs($categoryid) {
    global $DB;

    $context = context_coursecat::instance($categoryid);
    $sql = context_helper::get_preload_record_columns_sql('ctx');
    $programcontexts = $DB->get_records_sql('SELECT ctx.instanceid AS progid, '.
                    $sql. ' FROM {context} ctx '.
                    'WHERE ctx.path like :pathmask and ctx.contextlevel = :programlevel',
                    array('pathmask' => $context->path. '/%',
                          'programlevel' => CONTEXT_PROGRAM));
    foreach ($programcontexts as $ctxrecord) {
        context_helper::preload_from_record($ctxrecord);
        $programcontext = context_program::instance($ctxrecord->progid);
        if (!has_capability('shezar/program:deleteprogram', $programcontext)) {
            return false;
        }
    }

    return true;
}

/**
 * Class to store information about one program in a list of programs
 *
 * Written to resemble {@link course_in_list} class in coursecatlib.php
 */
class program_in_list implements IteratorAggregate {

    /** @var stdClass record retrieved from DB, may have additional calculated property such as managers and hassummary */
    protected $record;

    /**
     * Creates an instance of the class from record
     *
     * @param stdClass $record except fields from prog table it may contain
     *     field hassummary indicating that summary field is not empty.
     *     Also it is recommended to have context fields here ready for
     *     context preloading
     */
    public function __construct(stdClass $record) {
        context_helper::preload_from_record($record);
        $this->record = new stdClass();
        foreach ($record as $key => $value) {
            $this->record->$key = $value;
        }
    }

    /**
     * Indicates if the program has non-empty summary field
     *
     * @return bool
     */
    public function has_summary() {
        if (isset($this->record->hassummary)) {
            return $this->record->hassummary;
        }
        if (!isset($this->record->summary)) {
            // We need to retrieve summary.
            $this->__get('summary');
        }
        $this->record->hassummary = !empty($this->record->summary);
        return $this->record->hassummary;
    }

    /**
     * Checks if program has any associated overview files
     *
     * @return bool
     */
    public function has_program_overviewfiles() {
        global $CFG;
        if (empty($CFG->courseoverviewfileslimit)) {
            return 0;
        }
        require_once($CFG->libdir. '/filestorage/file_storage.php');
        $fs = get_file_storage();
        $context = context_program::instance($this->id);
        return $fs->is_area_empty($context->id, 'program', 'overviewfiles');
    }

    /**
     * Returns all program overview files
     *
     * @return array array of stored_file objects
     */
    public function get_program_overviewfiles() {
        global $CFG;
        if (empty($CFG->courseoverviewfileslimit)) {
            return array();
        }
        require_once($CFG->libdir . '/filestorage/file_storage.php');
        $fs = get_file_storage();
        $context = context_program::instance($this->id);
        $files = $fs->get_area_files($context->id, 'shezar_program', 'overviewfiles', false, 'filename', false);
        if (count($files)) {
            $overviewfilesoptions = prog_program_overviewfiles_options($this->id);
            $acceptedtypes = $overviewfilesoptions['accepted_types'];
            if ($acceptedtypes !== '*') {
                // Filter only files with allowed extensions.
                require_once($CFG->libdir . '/filelib.php');
                foreach ($files as $key => $file) {
                    if (!file_extension_in_typegroup($file->get_filename(), $acceptedtypes)) {
                        unset($files[$key]);
                    }
                }
            }
            if (count($files) > $CFG->courseoverviewfileslimit) {
                $files = array_slice($files, 0, $CFG->courseoverviewfileslimit, true);
            }
        }
        return $files;
    }

    public function __isset($name) {
        return isset($this->record->$name);
    }

    /**
     * Magic method to get a program property
     *
     * Returns any field from table prog (from cache or from DB) and/or special field 'hassummary'
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        global $DB;
        if (property_exists($this->record, $name)) {
            return $this->record->$name;
        } else if ($name === 'summary') {
            // retrieve fields summary and summaryformat together because they are most likely to be used together
            $record = $DB->get_record('prog', array('id' => $this->record->id), 'summary', MUST_EXIST);
            $this->record->summary = $record->summary;
            return $this->record->$name;
        } else if (array_key_exists($name, $DB->get_columns('prog'))) {
            // another field from table 'prog' that was not retrieved
            $this->record->$name = $DB->get_field('prog', $name, array('id' => $this->record->id), MUST_EXIST);
            return $this->record->$name;
        }
        debugging('Invalid program property accessed! ' . $name, DEBUG_DEVELOPER);
        return null;
    }

    /**
     * ALl properties are read only, sorry.
     * @param string $name
     */
    public function __unset($name) {
        debugging('Can not unset ' . get_class($this) . ' instance properties!', DEBUG_DEVELOPER);
    }

    /**
     * Magic setter method, we do not want anybody to modify properties from the outside
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        debugging('Can not change ' . get_class($this) . ' instance properties!', DEBUG_DEVELOPER);
    }

    /**
     * Create an iterator because magic vars can't be seen by 'foreach'.
     * Exclude context fields
     */
    public function getIterator() {
        $ret = array('id' => $this->record->id);
        foreach ($this->record as $property => $value) {
            $ret[$property] = $value;
        }
        return new ArrayIterator($ret);
    }
}

/**
 * Returns the minimum time for the program as html or returns the time string only
 *
 * @param int $seconds
 * @param boolean $timeonly false = output html, true = time string only
 * @return string
 */
function prog_format_seconds($seconds, $timeonly = false) {

    $years = floor($seconds / DURATION_YEAR);
    $str_years = get_string('xyears', 'shezar_program', $years);
    $seconds = $seconds % DURATION_YEAR;

    $months = floor($seconds / DURATION_MONTH);
    $str_months = get_string('xmonths', 'shezar_program', $months);
    $seconds = $seconds % DURATION_MONTH;

    $weeks = floor($seconds / DURATION_WEEK);
    $str_weeks = get_string('xweeks', 'shezar_program', $weeks);
    $seconds = $seconds % DURATION_WEEK;

    $days = floor($seconds / DURATION_DAY);
    $str_days = get_string('xdays', 'shezar_program', $days);

    $timestring = !empty($years) ? ' ' . $str_years : '';
    $timestring .= !empty($months) ? ' ' . $str_months : '';
    $timestring .= !empty($weeks) ? ' ' . $str_weeks : '';
    $timestring .= !empty($days) ? ' ' . $str_days : '';

    if ($timeonly) {
        return $timestring;
    }

    $output = '';
    $output .= html_writer::start_tag('div', array('id' => 'programtimerequired'));
    $output .= html_writer::start_tag('p');
    $output .= get_string('minprogramtimerequired', 'shezar_program');
    $output .= $timestring;
    $output .= html_writer::end_tag('p');
    $output .= html_writer::end_tag('div');

    return $output;
}

/**
 * updates the course enrolments for a program enrolment plugin, unenrolling students if the program is unavailable.
 *
 * @param enrol_shezar_program_plugin $program_plugin
 * @param int $programid
 * @param boolean debugging
 */
function prog_update_available_enrolments(enrol_shezar_program_plugin $program_plugin, $programid, $debugging = false) {
    global $DB;

    // Get all the courses in all the coursesets of the program.
    $coursesql = "SELECT c.*
                    FROM {course} c
                   WHERE c.id IN (SELECT DISTINCT(pcc.courseid)
                                    FROM {prog_courseset_course} pcc
                                    JOIN {prog_courseset} pc
                                      ON pcc.coursesetid = pc.id
                                   WHERE pc.programid = :pid
                                 )";
    $courseparams = array('pid' => $programid);
    $courses = $DB->get_records_sql($coursesql, $courseparams);

    foreach ($courses as $course) {
        $userstosuspend = array();
        if (CLI_SCRIPT && $debugging) {
            mtrace("Checking enrolments for Course-{$course->id}...");
        }

        // Get all the users enrolled in the course through the program enrolment plugin.
        $enrolsql = "SELECT ue.*
                      FROM {user_enrolments} ue
                      JOIN {enrol} e
                        ON ue.enrolid = e.id
                     WHERE e.courseid = :cid
                       AND e.enrol = 'shezar_program'";
        $enrolparams = array('cid' => $course->id);
        $enrolments = $DB->get_records_sql($enrolsql, $enrolparams);
        $instance = $program_plugin->get_instance_for_course($course->id);

        foreach ($enrolments as $enrolment) {
            // Check to see if the user should still be able to access the course.
            if (CLI_SCRIPT && $debugging) {
                mtrace("Checking enrolment-{$enrolment->id}");
            }

            $user = $DB->get_record('user', array('id' => $enrolment->userid));
            $access = prog_can_enter_course($user, $course);
            if (!$access->enroled) {
                // If they can't, then add the user to the list of users to suspend.
                if (CLI_SCRIPT && $debugging) {
                    mtrace("suspending enrolment for user-{$enrolment->userid}");
                }
                $userstosuspend[] = $enrolment->userid;
            } else if (CLI_SCRIPT && $debugging) {
                mtrace("user-{$enrolment->userid} can still access the course");
            }
        }
        if (!empty($userstosuspend)) {
            $program_plugin->process_program_unassignments($instance, $userstosuspend);
        }
    }
}

/**
 * Prints an error if Program is not enabled
 *
 */
function check_program_enabled() {
    if (shezar_feature_disabled('programs')) {
        print_error('programsdisabled', 'shezar_program');
    }
}

/**
 * Prints an error if Certification is not enabled
 *
 */
function check_certification_enabled() {
    if (shezar_feature_disabled('certifications')) {
        print_error('certificationsdisabled', 'shezar_certification');
    }
}

/*
 * Checks the programs availability based off the available from/untill dates.
 *
 * @param int $availablefrom    - A time stamp of the time a program becomes available
 * @param int $availableuntil   - A time stamp of the time a program becomes unavailable
 * @return int                  - Either AVAILABILITY_NOT_TO_STUDENTS or AVAILABILITY_TO_STUDENTS
 */
function prog_check_availability($availablefrom, $availableuntil) {
    // Note: there used to be $timezone parameter which is now ignored.

    $now = time();

    if (!empty($availablefrom) && $availablefrom > $now) {
        return AVAILABILITY_NOT_TO_STUDENTS;
    }
    if (!empty($availableuntil) && $availableuntil < $now) {
        return AVAILABILITY_NOT_TO_STUDENTS;
    }

    return AVAILABILITY_TO_STUDENTS;
}

/**
 * Checks if a given program is required for a given user
 *
 * @param int $progid - The id of a program
 * @param int $userid - The id of a user
 * @return boolean
 */
function prog_required_for_user($progid, $userid) {
    global $DB;

    $countsql = "SELECT COUNT(*)
                   FROM {prog_user_assignment}
                  WHERE exceptionstatus <> :ex1
                    AND exceptionstatus <> :ex2
                    AND programid = :pid
                    AND userid = :uid";
    $countparams = array('ex1' => PROGRAM_EXCEPTION_RAISED, 'ex2' => PROGRAM_EXCEPTION_DISMISSED,
                         'pid' => $progid, 'uid' => $userid);

    if ($DB->count_records_sql($countsql, $countparams) > 0) {
        $params = array('programid' => $progid, 'userid' => $userid, 'coursesetid' => 0);
        $completion = $DB->get_field('prog_completion', 'status', $params);
        if ($completion == STATUS_PROGRAM_COMPLETE) {
            // Not required if the program is complete.
            return false;
        }

        // Required if the program is assigned but not complete.
        return true;
    }

    // Not required if there are no active assignments.
    return false;
}

/**
 * Generates the HTML to display a program icon that links to a page to view the program
 *
 * @param int $progid               The id of a program
 * @param int $userid   optional    The id of a user, defaults to $USER if not set
 * @return html
 */
function prog_display_link_icon($progid, $userid = null) {
    global $OUTPUT, $USER, $DB;

    $prog = new program($progid);
    $user = isset($userid) ? $DB->get_record('user', array('id' => $userid)) : $USER;

    $accessibility = prog_check_availability($prog->availablefrom, $prog->availableuntil);
    $accessible = $accessibility == AVAILABILITY_TO_STUDENTS;
    $assigned = $prog->user_is_assigned($user->id);

    $progicon = shezar_get_icon($prog->id, shezar_ICON_TYPE_PROGRAM);
    $icon = html_writer::empty_tag('img', array('src' => $progicon, 'class' => 'course_icon', 'alt' => ''));

    if ($assigned && $accessible) {
        $url = new moodle_url('/shezar/program/required.php', array('id' => $prog->id, 'userid' => $user->id));
        $html = $OUTPUT->action_link($url, $icon . $prog->fullname);
    } else if ($accessible) {
        $url = new moodle_url('/shezar/program/view.php', array('id' => $prog->id));
        $html = $OUTPUT->action_link($url, $icon . $prog->fullname);
    } else {
        $html = $icon . $prog->fullname;
    }

    return $html;
}

/**
 * Display widget containing a program summary
 *
 * @param stdClass  $program    A program database record.
 * @param int       $userid     The userid of the record of learning.
 * @return string $out
 */
function prog_display_summary_widget($program, $userid = null) {
    global $USER;

    $params = array();
    if (($userid != null) && ($userid != $USER->id)) {
        $params['userid'] = $userid;
    }

    $params['id'] = $program->id;
    $url = new moodle_url("/shezar/program/required.php", $params);
    $summary = file_rewrite_pluginfile_urls($program->summary, 'pluginfile.php',
            context_program::instance($program->id)->id, 'shezar_program', 'summary', 0);

    $out = '';
    $out .= html_writer::start_tag('div', array('class' => 'cell'));
    $out .= html_writer::link($url, $program->fullname);
    $out .= html_writer::end_tag('div');
    $out .= html_writer::start_tag('div', array('class' => 'dp-summary-widget-description'));
    $out .= $summary . html_writer::end_tag('div');

    return $out;
}

/**
 * Display the due date for a program
 *
 * @param int $duedate
 * @param int $progid
 * @param int $userid
 * @param int $certifpath   Optional param telling us the path of the certification
 * @param int $certstatus   Optional param telling us the status of the certification
 * @param int $compstatus Whether the user has completed the program.
 * @param boolean $isexport Whether the output needs to be formatted for export.
 * @return string
 */
function prog_display_duedate($duedate, $progid, $userid, $certifpath = null, $certstatus = null, $compstatus = null, $isexport = false) {
    global $CFG, $PAGE;

    $renderer = $PAGE->get_renderer('shezar_program');

    if (empty($duedate) || $duedate == COMPLETION_TIME_NOT_SET) {
        if ($certifpath == null && $certstatus == null) {
            // This is a program, display no due date set.
            return get_string('duedatenotset', 'shezar_program');
        } else if ($certifpath == CERTIFPATH_CERT) {
            if ($certstatus == CERTIFSTATUS_EXPIRED) {
                // This certification has expired.
                return $renderer->error_text(get_string('overdue', 'shezar_program'));
            } else {
                // This is the first run through of the certification and no due date was set.
                return get_string('duedatenotset', 'shezar_program');
            }
        }
    }

    $out = '';
    if (!empty($duedate)) {
        $out .= userdate($duedate, get_string('strftimedatetime', 'langconfig'), 99, false);
    }

    $completed = isset($completion) ? $completion == STATUS_PROGRAM_COMPLETE : prog_is_complete($progid, $userid);
    if (!$completed && !$isexport) {
        $out .= $renderer->display_duedate_highlight_info($duedate);
    }

    return $out;
}


/**
 * Determines and displays the progress of this program for a specified user.
 *
 * Progress is determined by course set completion statuses.
 *
 * @access  public
 * @param int $programid
 * @param int $userid
 * @param int $certifpath (defaults to cert for programs)
 * @return  string
 */
function prog_display_progress($programid, $userid, $certifpath = CERTIFPATH_CERT, $export = false) {
    global $DB, $PAGE;

    $prog_completion = $DB->get_record('prog_completion', array('programid' => $programid, 'userid' => $userid, 'coursesetid' => 0));

    if (!$prog_completion) {
        $out = get_string('notassigned', 'shezar_program');
        return $out;
    } else if ($prog_completion->status == STATUS_PROGRAM_COMPLETE) {
        $overall_progress = 100;
    } else {
        $program = new program($programid);
        $overall_progress = $program->get_progress($userid);
    }

    if ($export) {
        return $overall_progress;
    }

    $tooltipstr = 'DEFAULTTOOLTIP';

    // Get relevant progress bar and return for display.
    $renderer = $PAGE->get_renderer('shezar_core');
    return $renderer->progressbar($overall_progress, 'medium', false, $tooltipstr);
}

/**
 * Checks accessiblity of the program for user if the user parameter is
 * passed to the function otherwise checks if the program is generally
 * accessible.
 *
 * @param stdClass  $program    A program database record
 * @param object    $user       If this parameter is included check availibilty to this user
 * @return boolean
 */
function prog_is_accessible($program, $user = null) {
    global $CFG;
    require_once($CFG->dirroot . '/shezar/cohort/lib.php');

    // If a user is set check if they area a site admin, if so, let them have access.
    if (!empty($user->id)) {
        if (is_siteadmin($user->id)) {
            return true;
        }
    }

    // Check if this program is not available, if it's not then deny access.
    if ($program->available) {
        return true;
    }

    if (!empty($user->id)) {
        // Check capabilities.
        $context = context_program::instance($program->id);
        $isprogram = empty($program->certifid);
        if ($isprogram && has_capability('shezar/program:viewhiddenprograms', $context) ||
            !$isprogram && has_capability('shezar/certification:viewhiddencertifications', $context) ||
            !empty($CFG->audiencevisibility) && has_capability('shezar/coursecatalog:manageaudiencevisibility', $context)) {
            return true;
        }
    }

    return false;
}


/**
 * Return true or false depending on whether or not the specified user has
 * completed a specified program
 *
 * @param int $progid   The id of a program
 * @param int $userid   The id of a user
 * @return bool
 */
function prog_is_complete($progid, $userid) {
    global $DB;

    if ($prog_completion_status = $DB->get_record('prog_completion', array('programid' => $progid, 'userid' => $userid, 'coursesetid' => 0))) {
        if ($prog_completion_status->status == STATUS_PROGRAM_COMPLETE) {
            return true;
        }
    }
    return false;
}

/**
 * Return true if the specified user has started but not completed the specified program,
 * otherwise return false.
 *
 * @param int $progid   The id of a program
 * @param int $userid   The id of a user
 * @return bool
 */
function prog_is_inprogress($progid, $userid) {
    global $DB;

    if ($prog_completion_status = $DB->get_record('prog_completion', array('programid' => $progid, 'userid' => $userid, 'coursesetid' => 0))) {
        if ($prog_completion_status->status == STATUS_PROGRAM_INCOMPLETE && $prog_completion_status->timestarted > 0) {
            return true;
        }
    }
    return false;
}

/**
 * Snippet to determine if a program is available based on the available fields.
 *
 * @param $fieldalias Alias for the program table used in the query
 * @param $separator Character separator between the alias and the field name
 * @param int|null $userid The user ID that wants to see the program
 * @return array
 */
function get_programs_availability_sql($fieldalias, $separator, $userid = null) {
    global $DB, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $user = $DB->get_record('user', array('id' => $userid));
    $now = time();

    $availabilitysql = " (({$fieldalias}{$separator}available = :available) AND
                          ({$fieldalias}{$separator}availablefrom = 0 OR {$fieldalias}{$separator}availablefrom < :timefrom) AND
                          ({$fieldalias}{$separator}availableuntil = 0 OR {$fieldalias}{$separator}availableuntil > :timeuntil))";
    $availabilityparams = array('available' => AVAILABILITY_TO_STUDENTS, 'timefrom' => $now, 'timeuntil' => $now);

    return array($availabilitysql, $availabilityparams);
}

/**
 * Move prog_completion record to history.
 *
 * @param $record prog_completion record
 * @return bool|int Result of the insertion.
 */
function shezar_prog_completion_to_history($record) {
    global $DB;

    return $DB->insert_record('prog_completion_history', $record);
}

/**
 * Get extension request setting for a particular program.
 *
 * @param int $programid The program ID
 * @return mixed
 */
function shezar_prog_extension_allowed($programid) {
    global $DB;

    return $DB->get_field('prog', 'allowextensionrequests', array('id' => $programid));
}

/**
 * Get a list of current assignments to a program, taking into account any that have not yet been saved
 * to the database. The resulting array may need some extra processing, but can then be
 * passed to a shezar dialog as the selected_items.
 *
 * @param int $programid - id of the program
 * @param string $selected - value of url param 'selected', will be ids separated by commas
 * @param string $removed - value of url param 'removed', will be ids separated by commas
 * @param int $assigntype - constant for assignment type, e.g. ASSIGNTYPE_INDIVIDUAL
 * @return array of ids that are assigned or selected, with $removed ids taken out.
 */
function shezar_prog_removed_selected_ids($programid, $selected, $removed, $assigntype) {
    global $DB;

    $selectedids = array();

    // Get ids of items already assigned.
    $alreadyassigned = $DB->get_records('prog_assignment', array('programid' => $programid, 'assignmenttype' => $assigntype), '', 'assignmenttypeid');
    foreach ($alreadyassigned as $assignment) {
        $selectedids[$assignment->assignmenttypeid] = $assignment->assignmenttypeid;
    }

    // Add selected but not yet saved to DB.
    if (!empty($selected)) {
        $selected = explode(',', $selected);
        foreach ($selected as $selectedid) {
            $selectedids[$selectedid] = $selectedid;
        }
    }

    // Remove removed but not yet removed from DB.
    if (!empty($removed)) {
        $removed = explode(',', $removed);
        foreach ($removed as $removedid) {
            if (isset($selectedids[$removedid])) {
                unset($selectedids[$removedid]);
            }
        }
    }

    return $selectedids;
}

/**
 * Checks the state of a user's program completion record.
 *
 * When an inconsistent state is detected, this function assumes that the status is correct, and reports
 * problems with other fields relative to this. It is possible that the problem (or solution to the
 * problem) is that the status is incorrect, and the other fields are correct, but it's not possible to
 * distinguish between the two scenarios.
 *
 * @param stdClass $progcompletion as stored in the prog_completion table (not all fields are required)
 * @return array describes any problems (error key => form field)
 */
function prog_get_completion_errors($progcompletion) {
    $errors = array();

    if ($progcompletion->timedue == COMPLETION_TIME_UNKNOWN) {
        $errors['error:timedueunknown'] = 'timedue';
    }

    switch ($progcompletion->status) {
        case STATUS_PROGRAM_INCOMPLETE:
            if ($progcompletion->timecompleted > 0) {
                $errors['error:stateincomplete-timecompletednotempty'] = 'timecompleted';
            }
            break;
        case STATUS_PROGRAM_COMPLETE:
            if ($progcompletion->timecompleted <= 0) {
                $errors['error:statecomplete-timecompletedempty'] = 'timecompleted';
            }
            break;
        default:
            $errors['error:progstatusinvalid'] = 'status';
            break;
    }

    return $errors;
}

/**
 * Convert the errors returned by prog_get_completion_errors into errors that can be used for form validation.
 *
 * @param array $errors as returned by prog_get_completion_errors
 * @return array of form validation errors
 */
function prog_get_completion_form_errors($errors) {
    $formerrors = array();
    foreach ($errors as $stringkey => $formkey) {
        if (isset($formerrors[$formkey])) {
            $formerrors[$formkey] .= '<br>' . get_string($stringkey, 'shezar_program');
        } else {
            $formerrors[$formkey] = get_string($stringkey, 'shezar_program');
        }
    }
    return $formerrors;
}

/**
 * Given a set of errors, calculate a unique problem key (just sort and concatenate errors).
 *
 * @param array $errors as returned by prog_get_completion_errors
 * @return string
 */
function prog_get_completion_error_problemkey($errors) {
    if (empty($errors)) {
        return '';
    }

    $errorkeys = array_keys($errors);
    sort($errorkeys);
    return implode('|', $errorkeys);
}

/**
 * Given a problem key returned by prog_get_completion_error_problemkey, return any known explanation or solutions, in html format.
 *
 * @param string $problemkey as returned by prog_get_completion_error_problemkey
 * @param int $programid if provided (non-0), url should only fix problems for this program
 * @param int $userid if provided (non-0), url should only fix problems for this user
 * @param bool $returntoeditor true if you want to return to the certification editor for this user/cert, default false for checker
 * @return string html formatted, possibly including url links to activate known fixes
 */
function prog_get_completion_error_solution($problemkey, $programid = 0, $userid = 0, $returntoeditor = false) {
    if (empty($problemkey)) {
        return '';
    }

    $params = array(
        'progorcert' => 'program',
        'progid' => $programid,
        'userid' => $userid,
        'returntoeditor' => $returntoeditor,
        'sesskey' => sesskey()
    );
    $baseurl = new moodle_url('/shezar/program/check_completion.php', $params);

    switch ($problemkey) {
        // See certs for examples of automated fixes. Remove this when a fix is implemented.
        case 'error:timedueunknown':
            $html = get_string('error:info_timedueunknown', 'shezar_program');
            break;
        default:
            $html = get_string('error:info_unknowncombination', 'shezar_program');
            break;
    }

    return $html;
}

/**
 * Applies the specified fix to program completion record.
 *
 * @param string $fixkey the key for the specific fix to be applied (see switch in code)
 * @param int $programid if provided (non-0), only fix problems for this program
 * @param int $userid if provided (non-0), only fix problems for this user
 */
function prog_fix_completions($fixkey, $programid = 0, $userid = 0) {
    global $DB;

    // Get all completion records, applying the specified filters.
    $sql = "SELECT pc.*
              FROM {prog_completion} pc
              JOIN {prog} prog
                ON prog.id = pc.programid
             WHERE pc.coursesetid = 0
               AND prog.certifid IS NULL";
    $params = array();
    if ($programid) {
        $sql .= " AND pc.programid = :programid";
        $params['programid'] = $programid;
    }
    if ($userid) {
        $sql .= " AND pc.userid = :userid";
        $params['userid'] = $userid;
    }

    $rs = $DB->get_recordset_sql($sql, $params);

    foreach ($rs as $progcompletion) {
        // Check for errors.
        $errors = prog_get_completion_errors($progcompletion);

        // Nothing wrong, so skip this record.
        if (empty($errors)) {
            continue;
        }

        $problemkey = prog_get_completion_error_problemkey($errors);
        $result = "";
        $ignoreproblem = "";

        // Only fix if this is an exact match for the specified problem.
        switch ($fixkey) {
            // See certif_fix_completions for an example. Remove this comment when first fix is implemented.
            // When adding the first fix here, you must also implement (copy from certs) the following tests:
            // * test_prog_fix_completions_only_selected
            // * test_prog_fix_completions_only_specified_state
            // * test_prog_fix_completions_only_if_isolated_problem
            // * test_prog_fix_completions_known_unfixed_problems
            // Plus one test for each fix function.
            default:
                break;
        }

        // Nothing happened, so no need to update or log.
        if (empty($result)) {
            continue;
        }

        prog_write_completion($progcompletion, $ignoreproblem, $result);
    }
}

/**
 * Create or update prog_completion record. Checks are performed to ensure that the data is valid before it
 * can be written to the db.
 *
 * NOTE: $ignoreproblemkey should only be used by prog_fix_completions!!! If specified, the record will be
 *       written to the db even if the records have the specified problem, and only that exact problem, or
 *       no problem at all, otherwise the update will not occur.
 *
 * @param stdClass $progcompletion A prog_completion record to be saved, including 'id' if this is an update.
 * @param string $message If provided, will be added to the program completion log message.
 * @param mixed $ignoreproblemkey String returned by prog_get_completion_error_problemkey which can be ignored.
 * @return True if the record was successfully created or updated.
 */
function prog_write_completion($progcompletion, $message = '', $ignoreproblemkey = false) {
    global $DB;

    // Decide if this is an insert or update.
    $isinsert = empty($progcompletion->id);

    // Ensure the record matches the database records.
    if ($isinsert) {
        $sql = "SELECT prog.id, pc.id AS pcid
                  FROM {prog} prog
             LEFT JOIN {prog_completion} pc
                    ON pc.programid = prog.id AND pc.userid = :pcuserid AND pc.coursesetid = 0
                 WHERE prog.id = :programid";
        $params = array('programid' => $progcompletion->programid, 'pcuserid' => $progcompletion->userid);
        $prog = $DB->get_record_sql($sql, $params);
        if (empty($prog) || !empty($prog->pcid)) {
            print_error(get_string('error:updatinginvalidcompletionrecord', 'shezar_program'));
        }

        if (empty($message)) {
            $message = "Completion record created";
        }
    } else {
        $sql = "SELECT pc.id
                  FROM {prog_completion} pc
                  JOIN {prog} prog
                    ON prog.id = pc.programid
                 WHERE pc.id = :pcid
                   AND pc.programid = :programid
                   AND pc.userid = :userid
                   AND pc.coursesetid = 0
                   AND prog.certifid IS NULL";
        $params = array('pcid' => $progcompletion->id, 'programid' => $progcompletion->programid, 'userid' => $progcompletion->userid);
        if (!$DB->record_exists_sql($sql, $params)) {
            print_error(get_string('error:updatinginvalidcompletionrecord', 'shezar_program'));
        }
    }

    // Before applying the changes, verify that the new record is in a valid state.
    $errors = prog_get_completion_errors($progcompletion);
    if (!empty($errors)) {
        $problemkey = prog_get_completion_error_problemkey($errors);
    }

    if (empty($errors) || $problemkey === $ignoreproblemkey) {
        if ($isinsert) {
            $DB->insert_record('prog_completion', $progcompletion);
        } else {
            $DB->update_record('prog_completion', $progcompletion);
        }

        prog_write_completion_log($progcompletion->programid, $progcompletion->userid, $message);

        return true;
    } else {
        // Some error was detected, and it wasn't specified in $ignoreproblemkey.
        return false;
    }
}

/**
 * Write a record to the program completion log.
 *
 * @param int    $programid    ID of the program.
 * @param int    $userid       ID of the user who's record is being affected, or null if it affects the whole program.
 * @param string $description  Describing what happened, including details. Can include simple html formatting.
 * @param null   $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
 */
function prog_log_completion($programid, $userid, $description, $changeuserid = null) {
    global $DB, $USER;

    if (is_null($changeuserid)) {
        $changeuserid = $USER->id;
    }

    $record = new stdClass();
    $record->programid = $programid;
    $record->userid = $userid;
    $record->changeuserid = $changeuserid;
    $record->description = $description;
    $record->timemodified = time();

    $DB->insert_record('prog_completion_log', $record);
}

/**
 * Write a log message (in the program completion log) when a program completion has been added or edited.
 *
 * @param int $programid
 * @param int $userid
 * @param string $message If provided, will be added at the start of the log message (instead of "Completion record edited")
 * @param null $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
 */
function prog_write_completion_log($programid, $userid, $message = '', $changeuserid = null) {
    global $DB;

    $progcompletion = $DB->get_record('prog_completion', array('programid' => $programid, 'userid' => $userid, 'coursesetid' => 0));

    $progstatus = '';
    switch ($progcompletion->status) {
        case STATUS_PROGRAM_INCOMPLETE:
            $progstatus = 'Not complete';
            break;
        case STATUS_PROGRAM_COMPLETE:
            $progstatus = 'Complete';
            break;
    }

    if ($progcompletion->timedue > 0) {
        $timedue = userdate($progcompletion->timedue, '%d %B %Y, %H:%M', 0) .
            ' (' . $progcompletion->timedue . ')';
    } else {
        $timedue = "Not set ({$progcompletion->timedue})";
    }
    if ($progcompletion->timecompleted > 0) {
        $timecompleted = userdate($progcompletion->timecompleted, '%d %B %Y, %H:%M', 0) .
            ' (' . $progcompletion->timecompleted . ')';
    } else {
        $timecompleted = "Not set ({$progcompletion->timecompleted})";
    }

    if (empty($message)) {
        $message = 'Completion record edited';
    }

    $description = $message . '<br>' .
        '<ul><li>Status: ' . $progstatus . '</li>' .
        '<li>Due date: ' . $timedue . '</li>' .
        '<li>Completion date: ' . $timecompleted . '</li></ul>';

    prog_log_completion(
        $programid,
        $userid,
        $description,
        $changeuserid
    );
}

/**
 * Delete program course set completion records, logging it in the prog completion log.
 *
 * Note that the $path param should only be used for certifications, not normal programs. This is not checked by the function.
 *
 * @param int $programid
 * @param int $userid
 * @param int $path null if course sets in all paths should be reset, else CERTIFPATH_CERT or CERTIFPATH_RECERT
 * @param string $message If provided, will override the default program completion log message.
 */
function prog_reset_course_set_completions($programid, $userid, $path = null, $message = '') {
    global $CERTIFPATH, $DB;

    // State changed from complete (progs) or before window opens (certs) to something else, so delete the related course set completion records.
    $sql = "UPDATE {prog_completion}
               SET status = :statusincomplete,
                   timestarted = 0,
                   timedue = 0,
                   timecompleted = 0
             WHERE programid = :programid1
               AND userid = :userid";
    $params = array(
        'statusincomplete' => STATUS_COURSESET_INCOMPLETE,
        'programid1' => $programid,
        'userid' => $userid
    );

    if (is_null($path)) {
        $sql .= " AND coursesetid != 0";
    } else {
        $sql .= " AND coursesetid IN (SELECT id
                                        FROM {prog_courseset}
                                       WHERE programid = :programid2
                                         AND certifpath = :path)";
        $params['programid2'] = $programid;
        $params['path'] = $path;
    }

    $DB->execute($sql, $params);

    if (empty($message)) {
        if (is_null($path)) {
            $message = 'Course set records reset';
        } else {
            $message = 'Course set records reset for path: ' . $CERTIFPATH[$path];
        }
    }

    prog_log_completion(
        $programid,
        $userid,
        $message
    );
}

/**
 * Processes completion data submitted by an admin - transforms it to look like a program completion record, suitable
 * for use in $DB->update_record().
 *
 * Note that the prog_completion record must already exist in the database (matching the user and program id
 * supplied), and their record ids will be included in the returned data. Creating new completion records should be
 * achieved automatically by assigning a user to a program, not manually in a form.
 *
 * @param object $submitted contains the data submitted by the form
 * @return object $progcompletion compatible with the corresponding database record
 */
function prog_process_submitted_edit_completion($submitted) {
    // Get existing record id.
    $existingrecord = prog_load_completion($submitted->id, $submitted->userid);

    $now = time();

    $progcompletion = new stdClass();
    $progcompletion->id = $existingrecord->id;
    $progcompletion->programid = $submitted->id;
    $progcompletion->userid = $submitted->userid;
    $progcompletion->status = $submitted->status;
    // Fix stupid timedue should be -1 for not set problem.
    $progcompletion->timedue = ($submitted->timeduenotset === 'yes') ? COMPLETION_TIME_NOT_SET : $submitted->timedue;
    $progcompletion->timecompleted = $submitted->timecompleted;
    $progcompletion->timemodified = $now;

    return $progcompletion;
}

/**
 * Load a prog_completion record out of the db.
 *
 * Use this function to make sure you don't accidentally get a course set completion record.
 *
 * @param int $programid
 * @param int $userid
 * @param bool $mustexist If records are missing, default true causes an error, false returns false
 * @return mixed
 */
function prog_load_completion($programid, $userid, $mustexist = true) {
    global $DB;

    $progcompletion = $DB->get_record('prog_completion', array('programid' => $programid, 'userid' => $userid, 'coursesetid' => 0));

    if (empty($progcompletion)) {
        if ($mustexist) {
            $a = array('programid' => $programid, 'userid' => $userid);
            print_error(get_string('error:cannotloadcompletionrecord', 'shezar_program', $a));
        } else {
            return false;
        }
    }

    return $progcompletion;
}
