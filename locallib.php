<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once($CFG->dirroot.'/availability/classes/info_module.php');

/**
 * get all groupspecifichtml instances in the current course and get it back
 * as a list for select options.
 */
function certificate_get_groupspecific_block_instances() {
    global $COURSE, $DB;

    $parentcontext = context_course::instance($COURSE->id);

    $blockoptions = array();
    if (!$gsis = $DB->get_records_select('block_instances', " blockname = 'groupspecifichtml' AND parentcontextid = ? ", array($COURSE->id, $parentcontext->id))) {
        foreach($gsis as $gsi) {
            $blockinstance = block_instance('groupspecifichtml', $gsi);
            $blockoptions["{$gsi->id}"] = $blockinstance->config->title;
        }
    }

    return $blockoptions;
}

/**
 * get the content of the current group in the groupspecificcontent.
 */
function certificate_get_groupspecific_content(&$certificate){
    global $CFG, $COURSE, $DB;

    if (empty($certificate->groupspecificcontent)) return '';

    $gid = 0 + groups_get_course_group($COURSE);
    $blockrec = $DB->get_record('groupspecifichtml', array('id' => $certificate->groupspecificcontent));
    $blockinstance = block_instance('groupspecifichtml', $blockrec);

    return $blockinstance->get_group_content($gid);
}

// ----------------------------------------------------------
// Linked course implementation

/**
 * Get linkable courses for mod_form.
 * A linkable cours is a course that is : 
 * - not the current course
 * // - not a metacourse
 * - not linked to the current course through a linking loop, everything we are linked to cannot be candidate
 * @return array The index is the id of the course, the
 * value is the course fullname
 */
function certificate_get_linkable_courses() {
    global $COURSE, $DB;

    // not ourself
    $discardedcourseids = array();
    $discardedcourseids[] = $COURSE->id;

    // @TODO : add metacourse dependencies filtering
    // one of our child courses cannot be linked as we are already syncing enrolments to it.

    // parents of us at any distance
    $parents = $DB->get_records('certificate_linked_courses', array('courseid' => $COURSE->id));

    while (!empty($parents)) {
        $directparents = array();
        foreach ($parents as $parent) {
            $parentcourse = $DB->get_field('certificate', 'course', array('id' => $parent->certificateid));
            if (!in_array($parentcourse, $discardedcourseids)) {
                $directparents[] = $parentcourse;
            }
        }
        $parentlist = implode("','", $directparents);
        $discardedcourseids = array_merge($discardedcourseids, $directparents);

        $parents = $DB->get_records_select('certificate_linked_courses', " courseid IN ('$parentlist') ", array());
    }

    $discardedcourselist = implode("','", $discardedcourseids);
    $availablecourses = $DB->get_records_select_menu('course', " id NOT IN ('$discardedcourselist') AND visible = 1  ", array(), 'fullname', 'id, fullname');    
    
    // TODO check real accessibility of the course for real students (if category is hidden ?)
    $availablecourses[0] = get_string('none', 'certificate');
    
    asort($availablecourses);
    return $availablecourses;
}

/**
* get linked course records array for the certificate
* @param int certid
*/
function certificate_get_linked_courses($certid) {
    global $DB;
    
    if (!$certid){
        return array();
    }
    
    if (is_numeric($certid)) {
        return $DB->get_records('certificate_linked_courses', array('certificateid' => $certid), 'id', 'courseid, id, certificateid, mandatory, roletobegiven');
    } else {
        return $DB->get_records('certificate_linked_courses', array('certificateid' => $certid->id), 'id', 'courseid, id, certificateid, mandatory, roletobegiven');
    }
}

/**
 * formats the list of linked courses
 */
function certificate_print_linked_courses($courses) {
    $str = '';

    if (empty($courses)) return $str;

    $coursestr = get_string('course');
    $mandatorystr = get_string('mandatory', 'certificate');

    $str .= "<center><br/><table style=\"margin-top:10px;\" id=\"courserequired\" width=\"90%\">";
    $str .= "<tr><th>$coursestr</th><th>$mandatorystr</th></tr>";
    foreach($courses as $course){
        $coursename = format_string($course->fullname);
        $str .= "<tr><td>$coursename</td><td align=\"right\"><input type=\"checkbox\" name=\"mandatorycourse_$course->id\" value=\"1\" /></td></tr>";
    }
    $str .= "</table></center>";

    return $str;
}

/**
* get the possible contexts a certification mentor is allowed to operate
*
*/
function certificate_get_possible_contexts(){
    global $USER, $COURSE;
    
    $contexts[CONTEXT_COURSE] = get_string('thiscourse', 'certificate');
    if (has_capability('moodle/category:manage', context_coursecat::instance($COURSE->category))){
        $contexts[CONTEXT_COURSECAT] = get_string('thiscategory', 'certificate');
    }
    if (has_capability('moodle/course:manageactivities', context_course::instance(SITEID))){
        $contexts[1] = get_string('sitecourse', 'certificate');
    }
    if (has_capability('moodle/site:config', context_system::instance())){
        $contexts[CONTEXT_SYSTEM] = get_string('system', 'certificate');
    }
    
    return $contexts;
}

function certificate_get_state($certificate, $cm, $page, $pagesize, $group, &$total, &$certifiableusers) {
    global $DB;
    
    $context = context_module::instance($cm->id);

    $state = new StdClass;
    $state->totalcertifiedcount = 0;
    $state->notyetusers = 0;
    if (!empty($group)) {
        $total = get_users_by_capability($context, 'mod/certificate:apply', 'u.id,'.get_all_user_name_fields(true, 'u'), '', '', '', $group, '', false);
        $state->totalcount = count($total);
        $certifiableusers = get_users_by_capability($context, 'mod/certificate:apply', 'u.id,'.get_all_user_name_fields(true, 'u').',picture,imagealt,email', 'lastname,firstname', $page * $pagesize, $pagesize, $group, '', false);
    } else {
        $total = get_users_by_capability($context, 'mod/certificate:apply', 'u.id,'.get_all_user_name_fields(true, 'u'), '', '', '', '', '', false);
        $state->totalcount = count($total);
        $certifiableusers = get_users_by_capability($context, 'mod/certificate:apply', 'u.id,'.get_all_user_name_fields(true, 'u').',picture,imagealt,email', 'lastname,firstname', $page * $pagesize, $pagesize, '', '', false);
    }
    
    // This may be quite costfull on large courses. Not for MOOCS !!
    foreach ($total as $u) {
        if ($DB->record_exists('certificate_issues', array('userid' => $u->id, 'certificateid' => $certificate->id))) {
            $state->totalcertifiedcount++;
        } else {
            if ($errors = certificate_check_conditions($certificate, $cm, $u->id)) {
                $state->notyetusers++;
            }
        }
    }
    return $state;
}

/**
 * checks all conditions for this certificate to be deliverable to user
 * @returns false if all conditions are OK, an array of error signals if not.
 */
function certificate_check_conditions($certificate, $cm, $userid) {
    global $DB;
    static $CACHE;

    if (empty($CACHE)) {
        $CACHE = array();
    }

    if (!isset($CACHE[$certificate->id][$userid])) {

        $context = context_module::instance($cm->id);
        $course = $DB->get_record('course', array('id' => $certificate->course));

        $CACHE[$certificate->id][$userid] = false;

        // Course time check.
        if (($certificate->requiredtime > 0) && !has_capability('mod/certificate:manage', $context, $userid)) {
            if (certificate_get_course_time($course->id) < ($certificate->requiredtime * 60)) {
                $a = new stdClass;
                $a->requiredtime = $certificate->requiredtime;
                $CACHE[$certificate->id][$userid] = get_string('requiredtimenotmet', 'certificate', $a);
                return $CACHE[$certificate->id][$userid];
            }
        }

        if ($certificate->lockoncoursecompletion && !has_capability('mod/certificate:manage', $context, $userid)) {
            $completioninfo = new completion_info($course);
            if (!$completioninfo->is_course_complete($userid)) {
                $CACHE[$certificate->id][$userid] = get_string('requiredcoursecompletion', 'certificate');
                return $CACHE[$certificate->id][$userid];
            }
        }

        // Conditions to view and generate certificate.
        // Mainly must check the conditional locks on the current instance.
        rebuild_course_cache($course->id);
        $modinfo = get_fast_modinfo($course);

        try {
            $cminfo = $modinfo->get_cm($cm->id);
            $condinfo = new \core_availability\info_module($cminfo);
            if (!$condinfo->is_available($information, false, $userid)) {
                $CACHE[$certificate->id][$userid] = get_string('needsmorework', 'certificate');
            }
        } catch(moodle_exception $e) {
            return false;
        }
    }

    return $CACHE[$certificate->id][$userid];
}

/**
 * When the user has received his sertificae, mark issues as being really delivered and
 * process to course chaining.
 */
function certificate_confirm_issue($user, $certificate, $cm) {
    global $DB;

    // mark as delivered

    $DB->set_field('certificate_issues', 'delivered', 1, array('certificateid' => $certificate->id, 'userid' => $user->id));
    $DB->set_field('certificate_issues', 'timedelivered', time(), array('certificateid' => $certificate->id, 'userid' => $user->id));
    process_chain($user, $certificate);
}

function process_chain($user, $certificate) {
    global $DB;

    // Process chaining if any.
    if ($linked = $DB->get_records('certificate_linked_courses', array('certificateid' => $certificate->id))) {
        // check no other mandatory requirements for each course. In case of we need 
        // to delay the new role assignation

        $chainok = true;

        foreach ($linked as $link) {
            $select = " mandatory = 1 AND courseid = ? AND certificateid <> ? ";
            $mandatoryreqs = $DB->get_records_select('certificate_linked_courses', $select, array($link->courseid, $certificate->id));

            foreach ($mandatoryreqs as $m) {
                $reqok = $DB->get_record('certificate_issues', array('userid' => $user->id, 'certificateid' => $m->certificateid, 'delivered' => 1));
                if (!$reqok) {
                    $chainok = false;
                    break;
                }
            }
        }

        // If chain is still ok, chain enrolment.
        if ($chainok) {
            $fromcourse = $DB->get_record('course', array('id' => $certificate->course));
            $coursecontext = context_course::instance($link->courseid);
            $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $link->courseid, 'status' => ENROL_INSTANCE_ENABLED));
            $enrolplugin = enrol_get_plugin('manual');
            $enrolplugin->enrol_user($enrol, $user->id, $link->roletobegiven, time(), 0, ENROL_USER_ACTIVE);

            // If required, propagate groups and memberships
            if (!empty($certificate->propagategroups)) {
                $fromgroups = groups_get_user_groups($certificate->course, $user->id);
                foreach ($fromgroups as $gpgid => $groups) {
                    if ($gpgid) {
                        // We are in a goruping, check and create if necessary.
                        $grouping = $DB->get_record('groupings', array('id' => $gpgid));
                        if (!$togrouping = $DB->get_record('groupings', array('courseid' => $link->courseid, 'name' => $grouping->name))) {
                            // No grouping in destination, create it.
                            $togrouping = clone($grouping);
                            unset($togrouping->id); // prepare for insertion.
                            $togrouping->courseid = $link->courseid;
                            $togrouping->idnumber = $formcourse->idnumber.'_'.$grouping->idnumber;
                            $togrouping->id = $DB->insert_record('groupings', $togrouping);
                        }
                    }

                    foreach ($groups as $gid => $group) {
                        $group = $DB->get_record('groups', array('id' => $gid));
                        if (!$togroup = $DB->get_record('groups', array('courseid' => $link->courseid, 'name' => $group->name))) {
                            // Group not existing, create it.
                            $togroup = clone($group);
                            unset($togroup->id); // prepare for insertion.
                            $togroup->courseid = $link->courseid;
                            $togroup->idnumber = $formcourse->idnumber.'_'.$group->idnumber;
                            $togroup->id = $DB->insert_record('groups', $togroup);

                            if ($gpgid && $togrouping->id) {
                                // We have a destination grouping, so attach the new group to it.
                                $grpgrping = new StdClass();
                                $grpgrping->groupingid = $togrouping->id;
                                $grpgrping->groupid = $togroup->id;
                                $grpgrping->timeadded = time();
                                $DB->insert_record('groupings_groups', $grpgrping);
                            }
                        }
                        // Finally add user to group.
                        groups_add_member($togroup->id, $user->id);
                    }
                }
            }
        }
    }
}

/**
 * Returns a list of issued certificates - sorted for report.
 *
 * @param int $certificateid
 * @param string $sort the sort order
 * @param bool $groupmode are we in group mode ?
 * @param stdClass $cm the course module
 * @param int $page offset
 * @param int $perpage total per page
 * @return stdClass the users
 */
function certificate_get_issues($certificateid, $sort="ci.timecreated ASC", $groupmode, $cm, $page = 0, $perpage = 0) {
    global $CFG, $DB;

    // get all users that can manage this certificate to exclude them from the report.
    $context = context_module::instance($cm->id);

    $conditionssql = '';
    $conditionsparams = array();
    if ($certmanagers = array_keys(get_users_by_capability($context, 'mod/certificate:manage', 'u.id'))) {
        list($sql, $params) = $DB->get_in_or_equal($certmanagers, SQL_PARAMS_NAMED, 'cert');
        $conditionssql .= "AND NOT u.id $sql \n";
        $conditionsparams += $params;
    }



    $restricttogroup = false;
    if ($groupmode) {
        $currentgroup = groups_get_activity_group($cm);
        if ($currentgroup) {
            $restricttogroup = true;
            $groupusers = array_keys(groups_get_members($currentgroup, 'u.*'));
            if (empty($groupusers)) {
                return array();
            }
        }
    }

    $restricttogrouping = false;

    // if groupmembersonly used, remove users who are not in any group
    if (!empty($CFG->enablegroupings) and $cm->groupmembersonly) {
        if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
            $restricttogrouping = true;
        } else {
            return array();
        }
    }

    if ($restricttogroup || $restricttogrouping) {
        if ($restricttogroup) {
            $allowedusers = $groupusers;
        } else if ($restricttogroup && $restricttogrouping) {
            $allowedusers = array_intersect($groupusers, $groupingusers);
        } else  {
            $allowedusers = $groupingusers;
        }

        list($sql, $params) = $DB->get_in_or_equal($allowedusers, SQL_PARAMS_NAMED, 'grp');
        $conditionssql .= "AND u.id $sql \n";
        $conditionsparams += $params;
    }


    $page = (int) $page;
    $perpage = (int) $perpage;

    // Setup pagination - when both $page and $perpage = 0, get all results
    if ($page || $perpage) {
        if ($page < 0) {
            $page = 0;
        }

        if ($perpage > CERT_MAX_PER_PAGE) {
            $perpage = CERT_MAX_PER_PAGE;
        } else if ($perpage < 1) {
            $perpage = CERT_PER_PAGE;
        }
    }

    // Get all the users that have certificates issued, should only be one issue per user for a certificate
    $allparams = $conditionsparams + array('certificateid' => $certificateid);

    $users = $DB->get_records_sql("SELECT u.*, ci.code, ci.timecreated
                                   FROM {user} u
                                   INNER JOIN {certificate_issues} ci
                                   ON u.id = ci.userid
                                   WHERE u.deleted = 0
                                   AND ci.certificateid = :certificateid
                                   $conditionssql
                                   ORDER BY {$sort}",
                                   $allparams,
                                   $page * $perpage,
                                   $perpage);

    return $users;
}

/**
 * Returns a list of previously issued certificates--used for reissue.
 *
 * @param int $certificateid
 * @return stdClass the attempts else false if none found
 */
function certificate_get_attempts($certificateid) {
    global $DB, $USER;

    $sql = "SELECT *
            FROM {certificate_issues} i
            WHERE certificateid = :certificateid
            AND userid = :userid";
    if ($issues = $DB->get_records_sql($sql, array('certificateid' => $certificateid, 'userid' => $USER->id))) {
        return $issues;
    }

    return false;
}

/**
 * Prints a table of previously issued certificates--used for reissue.
 *
 * @param stdClass $course
 * @param stdClass $certificate
 * @param stdClass $attempts
 * @return string the attempt table
 */
function certificate_print_attempts($course, $certificate, $attempts) {
    global $OUTPUT, $DB;

    echo $OUTPUT->heading(get_string('summaryofattempts', 'certificate'));

    $printconfig = unserialize(@$certificate->printconfig);

    // Prepare table header
    $table = new html_table();
    $table->class = 'generaltable';
    $table->head = array(get_string('issued', 'certificate'));
    $table->align = array('left');
    $table->attributes = array("style" => "width:20%; margin:auto");
    $gradecolumn = @$printconfig->printgrade;
    if ($gradecolumn) {
        $table->head[] = get_string('grade');
        $table->align[] = 'center';
        $table->size[] = '';
    }
    // One row for each attempt
    foreach ($attempts as $attempt) {
        $row = array();

        // prepare strings for time taken and date completed
        $datecompleted = userdate($attempt->timecreated);
        $row[] = $datecompleted;

        if ($gradecolumn) {
            $attemptgrade = certificate_get_grade($certificate, $course);
            $row[] = $attemptgrade;
        }

        $table->data[$attempt->id] = $row;
    }

    echo html_writer::table($table);
}

/**
 * Get the time the user has spent in the course
 *
 * @param int $courseid
 * @return int the total time spent in seconds
 */
function certificate_get_course_time($courseid) {
    global $CFG, $USER;

    set_time_limit(0);

    $totaltime = 0;
    $sql = "l.course = :courseid AND l.userid = :userid";
    if ($logs = get_logs($sql, array('courseid' => $courseid, 'userid' => $USER->id), 'l.time ASC', '', '', $totalcount)) {
        foreach ($logs as $log) {
            if (!isset($login)) {
                // For the first time $login is not set so the first log is also the first login
                $login = $log->time;
                $lasthit = $log->time;
                $totaltime = 0;
            }
            $delay = $log->time - $lasthit;
            if ($delay > ($CFG->sessiontimeout * 60)) {
                // The difference between the last log and the current log is more than
                // the timeout Register session value so that we have found a session!
                $login = $log->time;
            } else {
                $totaltime += $delay;
            }
            // Now the actual log became the previous log for the next cycle
            $lasthit = $log->time;
        }

        return $totaltime;
    }

    return 0;
}

/**
 * Get certificate types indexed and sorted by name for mod_form.
 *
 * @return array containing the certificate type
 */
function certificate_types() {
    $types = array();
    $names = get_list_of_plugins('mod/certificate/type');
    $sm = get_string_manager();
    foreach ($names as $name) {
        if ($sm->string_exists('type'.$name, 'certificate')) {
            $types[$name] = get_string('type'.$name, 'certificate');
        } else {
            $types[$name] = ucfirst($name);
        }
    }
    asort($types);
    return $types;
}

/**
 * Get images for mod_form.
 *
 * @param string $type the image type
 * @return array
 */
function certificate_get_images($type) {
    global $CFG, $DB;

    switch($type) {
        case CERT_IMAGE_BORDER :
            $path = "$CFG->dirroot/mod/certificate/pix/borders";
            $uploadpath = "$CFG->dataroot/mod/certificate/pix/borders";
            break;
        case CERT_IMAGE_SEAL :
            $path = "$CFG->dirroot/mod/certificate/pix/seals";
            $uploadpath = "$CFG->dataroot/mod/certificate/pix/seals";
            break;
        case CERT_IMAGE_SIGNATURE :
            $path = "$CFG->dirroot/mod/certificate/pix/signatures";
            $uploadpath = "$CFG->dataroot/mod/certificate/pix/signatures";
            break;
        case CERT_IMAGE_WATERMARK :
            $path = "$CFG->dirroot/mod/certificate/pix/watermarks";
            $uploadpath = "$CFG->dataroot/mod/certificate/pix/watermarks";
            break;
    }
    // If valid path
    if (!empty($path)) {
        $options = array();
        $options += certificate_scan_image_dir($path);
        $options += certificate_scan_image_dir($uploadpath);

        // Sort images
        ksort($options);

        // Add the 'no' option to the top of the array
        $options = array_merge(array('0' => get_string('no')), $options);

        return $options;
    } else {
        return array();
    }
}

/**
 * Helper function to return the suffix of the day of
 * the month, eg 'st' if it is the 1st of the month.
 *
 * @param int the day of the month
 * @return string the suffix.
 */
function certificate_get_ordinal_number_suffix($day) {
    if (!in_array(($day % 100), array(11, 12, 13))) {
        switch ($day % 10) {
            // Handle 1st, 2nd, 3rd
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
        }
    }
    return 'th';
}

/**
 * Returns the code to display on the certificate.
 *
 * @param stdClass $course
 * @param stdClass $certrecord
 * @return string the code
 */
function certificate_get_code($certificate, $certrecord) {
    
    $printconfig = unserialize($certificate->printconfig);
    
    if (!empty($printconfig->printnumber)) {
        return $certrecord->code;
    }

    return '';
}

/**
 * Alerts teachers by email of received certificates. First checks
 * whether the option to email teachers is set for this certificate.
 *
 * @param stdClass $course
 * @param stdClass $certificate
 * @param stdClass $certrecord
 * @param stdClass $cm course module
 */
function certificate_email_teachers($course, $certificate, $certrecord, $cm) {
    global $USER, $CFG, $DB;

    if ($certificate->emailteachers == 0) {          // No need to do anything
        return;
    }

    $user = $DB->get_record('user', array('id' => $certrecord->userid));

    if ($teachers = certificate_get_teachers($certificate, $user, $course, $cm)) {
        $strawarded = get_string('awarded', 'certificate');
        foreach ($teachers as $teacher) {
            $info = new stdClass;
            $info->student = fullname($USER);
            $info->course = format_string($course->fullname,true);
            $info->certificate = format_string($certificate->name,true);
            $info->url = $CFG->wwwroot.'/mod/certificate/report.php?id='.$cm->id;
            $from = $USER;
            $postsubject = $strawarded . ': ' . $info->student . ' -> ' . $certificate->name;
            $posttext = certificate_email_teachers_text($info);
            $posthtml = ($teacher->mailformat == 1) ? certificate_email_teachers_html($info) : '';

            @email_to_user($teacher, $from, $postsubject, $posttext, $posthtml);  // If it fails, oh well, too bad.
        }
    }
}

/**
 * Alerts others by email of received certificates. First checks
 * whether the option to email others is set for this certificate.
 * Uses the email_teachers info.
 * Code suggested by Eloy Lafuente
 *
 * @param stdClass $course
 * @param stdClass $certificate
 * @param stdClass $certrecord
 * @param stdClass $cm course module
 */
function certificate_email_others($course, $certificate, $certrecord, $cm) {
    global $USER, $CFG, $DB;

    if ($certificate->emailothers) {
       $others = explode(',', $certificate->emailothers);
        if ($others) {
            $strawarded = get_string('awarded', 'certificate');
            foreach ($others as $other) {
                $other = trim($other);
                if (validate_email($other)) {
                    $destination = new stdClass;
                    $destination->email = $other;
                    $info = new stdClass;
                    $info->student = fullname($USER);
                    $info->course = format_string($course->fullname, true);
                    $info->certificate = format_string($certificate->name, true);
                    $info->url = $CFG->wwwroot.'/mod/certificate/report.php?id='.$cm->id;
                    $from = $USER;
                    $postsubject = $strawarded . ': ' . $info->student . ' -> ' . $certificate->name;
                    $posttext = certificate_email_teachers_text($info);
                    $posthtml = certificate_email_teachers_html($info);

                    @email_to_user($destination, $from, $postsubject, $posttext, $posthtml);  // If it fails, oh well, too bad.
                }
            }
        }
    }
}

/**
 * Creates the text content for emails to teachers -- needs to be finished with cron
 *
 * @param $info object The info used by the 'emailteachermail' language string
 * @return string
 */
function certificate_email_teachers_text($info) {
    $posttext = get_string('emailteachermail', 'certificate', $info) . "\n";

    return $posttext;
}

/**
 * Creates the html content for emails to teachers
 *
 * @param $info object The info used by the 'emailteachermailhtml' language string
 * @return string
 */
function certificate_email_teachers_html($info) {
    $posthtml  = '<font face="sans-serif">';
    $posthtml .= '<p>' . get_string('emailteachermailhtml', 'certificate', $info) . '</p>';
    $posthtml .= '</font>';

    return $posthtml;
}

/**
 * Sends the student their issued certificate from moddata as an email
 * attachment.
 *
 * @param stdClass $course
 * @param stdClass $certificate
 * @param stdClass $certrecord
 * @param stdClass $context
 */
function certificate_email_student($course, $certificate, $certrecord, $context) {
    global $DB, $USER;

    // Get teachers
    if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
        '', '', '', '', false, true)) {
        $users = sort_by_roleassignment_authority($users, $context);
        $teacher = array_shift($users);
    }

    // If we haven't found a teacher yet, look for a non-editing teacher in this course.
    if (empty($teacher) && $users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
        '', '', '', '', false, true)) {
        $users = sort_by_roleassignment_authority($users, $context);
        $teacher = array_shift($users);
    }

    // Ok, no teachers, use administrator name
    if (empty($teacher)) {
        $teacher = fullname(get_admin());
    }

    $info = new stdClass;
    $info->username = fullname($USER);
    $info->certificate = format_string($certificate->name, true);
    $info->course = format_string($course->fullname, true);
    $from = fullname($teacher);
    $subject = $info->course . ': ' . $info->certificate;
    $message = get_string('emailstudenttext', 'certificate', $info) . "\n";

    // Make the HTML version more XHTML happy  (&amp;)
    $messagehtml = text_to_html(get_string('emailstudenttext', 'certificate', $info));

    // Remove full-stop at the end if it exists, to avoid "..pdf" being created and being filtered by clean_filename
    $certname = rtrim($certificate->name, '.');
    $filename = clean_filename("$certname.pdf");

    // Get hashed pathname
    $fs = get_file_storage();

    $component = 'mod_certificate';
    $filearea = 'issue';
    $filepath = '/';
    $files = $fs->get_area_files($context->id, $component, $filearea, $certrecord->id);
    foreach ($files as $f) {
        $filepathname = $f->get_contenthash();
    }
    $attachment = 'filedir/'.certificate_path_from_hash($filepathname).'/'.$filepathname;
    $attachname = $filename;

    return email_to_user($USER, $from, $subject, $message, $messagehtml, $attachment, $attachname);
}

/**
 * Retrieve certificate path from hash
 *
 * @param array $contenthash
 * @return string the path
 */
function certificate_path_from_hash($contenthash) {
    $l1 = $contenthash[0].$contenthash[1];
    $l2 = $contenthash[2].$contenthash[3];
    return "$l1/$l2";
}

/**
 * This function returns success or failure of file save
 *
 * @param string $pdf is the string contents of the pdf
 * @param int $certrecordid the certificate issue record id
 * @param string $filename pdf filename
 * @param int $contextid context id
 * @return bool return true if successful, false otherwise
 */
function certificate_save_pdf($pdf, $certrecordid, $filename, $contextid) {
    global $DB, $USER;

    if (empty($certrecordid)) {
        if (debugging()) {
            echo $OUTPUT->notification('No valid cert id. Aborting.');
        }
        return false;
    }

    if (empty($pdf)) {
        if (debugging()) {
            echo $OUTPUT->notification('No pdf content. Aborting.');
        }
        return false;
    }

    $fs = get_file_storage();

    // Prepare file record object
    $component = 'mod_certificate';
    $filearea = 'issue';
    $filepath = '/';
    $fileinfo = array(
        'contextid' => $contextid,   // ID of context
        'component' => $component,   // usually = table name
        'filearea'  => $filearea,     // usually = table name
        'itemid'    => $certrecordid,  // usually = ID of row in table
        'filepath'  => $filepath,     // any path beginning and ending in /
        'filename'  => $filename,    // any filename
        'mimetype'  => 'application/pdf',    // any filename
        'userid'    => $USER->id);

    // If the file exists, delete it and recreate it. This is to ensure that the
    // latest certificate is saved on the server. For example, the student's grade
    // may have been updated. This is a quick dirty hack.
    if ($fs->file_exists($contextid, $component, $filearea, $certrecordid, $filepath, $filename)) {
        $fs->delete_area_files($contextid, $component, $filearea, $certrecordid);
    }

    $fs->create_file_from_string($fileinfo, $pdf);

    return true;
}

/**
 * Produces a list of links to the issued certificates.  Used for report.
 *
 * @param stdClass $certificate
 * @param int $userid
 * @param stdClass $context
 * @return string return the user files
 */
function certificate_print_user_files($certificate, $userid, $contextid) {
    global $CFG, $DB, $OUTPUT;

    $output = '';

    $certrecord = $DB->get_record('certificate_issues', array('userid' => $userid, 'certificateid' => $certificate->id));
    $fs = get_file_storage();
    $browser = get_file_browser();

    $component = 'mod_certificate';
    $filearea = 'issue';
    $files = $fs->get_area_files($contextid, $component, $filearea, $certrecord->id);

    foreach ($files as $file) {
        $filename = $file->get_filename();
        $mimetype = $file->get_mimetype();
        $link = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$contextid.'/mod_certificate/issue/'.$certrecord->id.'/'.$filename);

        $output = '<img src="'.$OUTPUT->pix_url(file_mimetype_icon($file->get_mimetype())).'" height="16" width="16" alt="'.$file->get_mimetype().'" />&nbsp;'.
                  '<a href="'.$link.'" >'.s($filename).'</a>';

    }
    $output .= '<br />';
    $output = '<div class="files">'.$output.'</div>';

    return $output;
}

/**
 * Inserts preliminary user data when a certificate is viewed.
 * Prevents form from issuing a certificate upon browser refresh.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $certificate
 * @param stdClass $cm
 * @return stdClass the newly created certificate issue
 */
function certificate_get_issue($course, $user, $certificate, $cm) {
    global $DB;

    // Check if there is an issue already, should only ever be one
    if ($certissue = $DB->get_record('certificate_issues', array('userid' => $user->id, 'certificateid' => $certificate->id))) {
        return $certissue;
    }

    // Create new certificate issue record
    $certissue = new stdClass();
    $certissue->certificateid = $certificate->id;
    $certissue->userid = $user->id;
    $certissue->code = certificate_generate_code();
    $certissue->timecreated =  time();
    $certissue->id = $DB->insert_record('certificate_issues', $certissue);

    // Email to the teachers and anyone else
    certificate_email_teachers($course, $certificate, $certissue, $cm);
    certificate_email_others($course, $certificate, $certissue, $cm);

    return $certissue;
}

/**
 * Generates a 10-digit code of random letters and numbers.
 *
 * @return string
 */
function certificate_generate_code() {
    global $DB;

    $uniquecodefound = false;
    $code = random_string(10);
    while (!$uniquecodefound) {
        if (!$DB->record_exists('certificate_issues', array('code' => $code))) {
            $uniquecodefound = true;
        } else {
            $code = random_string(10);
        }
    }

    return $code;
}

/**
 * Get all the modules
 *
 * @return array
 */
function certificate_get_mods() {
    global $COURSE, $CFG, $DB;

    $strtopic = get_string("topic");
    $strweek = get_string("week");
    $strsection = get_string("section");

    // Collect modules data
    $modinfo = get_fast_modinfo($COURSE);
    $mods = $modinfo->get_cms();

    $modules = array();
    // Check what version we are running - really we should have separate branch for 2.4, but
    // having a branch called master and one called MOODLE_24_STABLE may be confusing. This
    // module will also be replaced in the future so hack will do. Here we get the course
    // sections and sort the modules as they appear in the course.
    if ($CFG->version >= '2012112900') {
        $sections = $modinfo->get_section_info_all();
    } else {
        $sections = get_all_sections($COURSE->id);
    }
    for ($i = 0; $i <= count($sections) - 1; $i++) {
        // should always be true
        if (isset($sections[$i])) {
            $section = $sections[$i];
            if ($section->sequence) {
                switch ($COURSE->format) {
                    case "topics":
                        $sectionlabel = $strtopic;
                    break;
                    case "weeks":
                        $sectionlabel = $strweek;
                    break;
                    default:
                        $sectionlabel = $strsection;
                }

                $sectionmods = explode(",", $section->sequence);
                foreach ($sectionmods as $sectionmod) {
                    if (empty($mods[$sectionmod])) {
                        continue;
                    }
                    $mod = $mods[$sectionmod];
                    $instance = $DB->get_record($mod->modname, array('id' => $mod->instance));
                    if ($grade_items = grade_get_grade_items_for_activity($mod)) {
                        $mod_item = grade_get_grades($COURSE->id, 'mod', $mod->modname, $mod->instance);
                        $item = reset($mod_item->items);
                        if (isset($item->grademax)){
                            $modules[$mod->id] = $sectionlabel.' '.$section->section.' : '.$instance->name;
                        }
                    }
                }
            }
        }
    }

    return $modules;
}