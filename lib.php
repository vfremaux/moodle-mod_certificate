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

/**
 * Certificate module core interaction API
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/querylib.php');
require_once($CFG->dirroot.'/lib/conditionlib.php');
require_once($CFG->dirroot.'/mod/certificate/printlib.php');
require_once($CFG->dirroot.'/mod/certificate/locallib.php');

/** The border image folder */
define('CERT_IMAGE_BORDER', 'borders');
/** The watermark image folder */
define('CERT_IMAGE_WATERMARK', 'watermarks');
/** The signature image folder */
define('CERT_IMAGE_SIGNATURE', 'signatures');
/** The seal image folder */
define('CERT_IMAGE_SEAL', 'seals');

define('CERT_PER_PAGE', 30);
define('CERT_MAX_PER_PAGE', 200);

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function certificate_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}


/**
 * Add certificate instance.
 *
 * @param stdClass $certificate
 * @return int new certificate instance id
 */
function certificate_add_instance($certificate) {
    global $DB;

    // Create the certificate.
    $certificate->timecreated = time();
    $certificate->timemodified = $certificate->timecreated;

    if (empty($certificate->lockoncoursecompletion)) {
        $certificate->lockoncoursecompletion = 0;
    }

    // Compact print options
    $printconfig = new StdClass;
    $printconfig->printhours = $certificate->printhours;
    $printconfig->printoutcome = $certificate->printoutcome;
    $printconfig->printdate = $certificate->printdate;
    $printconfig->printteacher = $certificate->printteacher;
    $printconfig->printcode = $certificate->printcode;
    $printconfig->printqrcode = $certificate->printqrcode;
    $printconfig->printgrade = $certificate->printgrade;

    $certificate->printconfig = serialize($printconfig);

    $certificate->id = $DB->insert_record('certificate', $certificate);

    if (isset($certificate->courselinkid) and is_array($certificate->courselinkid)) {
        foreach ($certificate->courselinkid as $key => $linkid) {
            if ($linkid > 0) {
                $clm = new StdClass();
                $clm->certificateid = $certificate->id;
                $clm->courseid = $linkid;
                $clm->mandatory = 0 + @$certificate->courselinkmandatory[$key];
                $clm->roletobegiven = $certificate->courselinkrole[$key];
                // $clm->timemodified = $certificate->timemodified;
                $retval = $DB->insert_record('certificate_linked_courses', $clm) and $retval;
            }
        }
    }

    // Saves certificate images.
    $context = context_module::instance($certificate->coursemodule);
    $instancefiles = array('printborders', 'printwmark', 'printseal', 'printsignature');

    foreach ($instancefiles as $if) {
        $draftitemid = 0 + @$certificate->$if;
        file_save_draft_area_files($draftitemid, $context->id, 'mod_certificate', $if, 0);
    }

    return $certificate->id;
}

/**
 * Update certificate instance.
 *
 * @param stdClass $certificate
 * @return bool true
 */
function certificate_update_instance($certificate) {
    global $DB;


    $certificate->courselinkentry = @$_REQUEST['courselinkentry']; // again this weird situation
    // of Quickform loosing params on form bounces

    if (empty($certificate->lockoncoursecompletion)) {
        $certificate->lockoncoursecompletion = 0;
    }

    // Update the certificate.
    $certificate->timemodified = time();
    $certificate->id = $certificate->instance;

    if (isset($certificate->courselinkid) and is_array($certificate->courselinkid)) {
        foreach ($certificate->courselinkid as $key => $linkid) {
            if (isset($certificate->courselinkentry[$key])) {
                if ($linkid > 0) {
                    $clc = new StdClass;
                    $clc->id = $certificate->courselinkentry[$key];
                    $clc->certificateid = $certificate->id;
                    $clc->courseid = $linkid;
                    $clc->mandatory = 0 + @$certificate->courselinkmandatory[$key];
                    $clc->roletobegiven = $certificate->courselinkrole[$key];
                    // $clm->timemodified = $certificate->timemodified;
                    $retval = $DB->update_record('certificate_linked_courses', $clc) and $retval;
                } else {
                    $retval = $DB->delete_records('certificate_linked_courses', array('id' => $certificate->courselinkentry[$key])) and $retval;
                }
            } else if ($linkid > 0) {
                $clc = new StdClass;
                $clc->certificateid = $certificate->id;
                $clc->courseid = $linkid;
                $clc->mandatory = 0 + @$certificate->courselinkmandatory[$key];
                $clc->roletobegiven = $certificate->courselinkrole[$key];
                // $clc->timemodified = $certificate->timemodified;
                if (!$oldone = $DB->get_record('certificate_linked_courses', array('courseid' => $linkid, 'certificateid' => $certificate->id))){
                    $retval = $DB->insert_record('certificate_linked_courses', $clc) and $retval;
                } else {
                    $clc->id = $oldone->id;
                    $DB->update_record('certificate_linked_courses', $clc);
                }
            }
        }
    }

    // compact print options
    $printconfig = new StdClass;
    $printconfig->printhours = $certificate->printhours;
    $printconfig->printoutcome = $certificate->printoutcome;
    $printconfig->printdate = $certificate->printdate;
    $printconfig->printteacher = $certificate->printteacher;
    $printconfig->printcode = $certificate->printcode;
    $printconfig->printqrcode = $certificate->printqrcode;
    $printconfig->printgrade = $certificate->printgrade;

    $certificate->printconfig = serialize($printconfig);

    // Saves certificate images.
    $context = context_module::instance($certificate->coursemodule);
    $instancefiles = array('printborders', 'printwmark', 'printseal', 'printsignature');

    $fs = get_file_storage();

    foreach ($instancefiles as $if) {
        $groupname = $if.'group';
        $draftidarr = (array) $certificate->$groupname;
        $draftitemid = $draftidarr[$if];
        $clearif = 'clear'.$if;
        if (!empty($draftidarr[$clearif])) {
            // Delete existing zone
            $fs->delete_area_files($context->id, 'mod_certificate', $if, 0);
        } else {
            file_save_draft_area_files($draftitemid, $context->id, 'mod_certificate', $if, 0);
        }
    }

    return $DB->update_record('certificate', $certificate);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool true if successful
 */
function certificate_delete_instance($id) {
    global $DB;

    // Ensure the certificate exists
    if (!$certificate = $DB->get_record('certificate', array('id' => $id))) {
        return false;
    }

    // Prepare file record object
    if (!$cm = get_coursemodule_from_instance('certificate', $id)) {
        return false;
    }

    $result = true;
    $DB->delete_records('certificate_issues', array('certificateid' => $id));
    if (!$DB->delete_records('certificate', array('id' => $id))) {
        $result = false;
    }

    // Delete any files associated with the certificate
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    return $result;
}

/**
 * This function makes a last post process of the cminfo information
 * for module info caching in memory when course displays. Here we
 * can tweek some information to force cminfo behave like some label kind
 * @see : Page format use the pageitem.php strategy for dealing with the 
 * content display rules.
 * @todo : reevaluate strategy. this may still be used for improving standard formats.
 */
function certificate_cm_info_dynamic(&$cminfo) {
    global $DB, $PAGE, $CFG, $COURSE, $USER;

    // Apply role restriction here.
    if ($certificate = $DB->get_record('certificate', array('id' => $cminfo->instance))) {
        if ($certificate->lockoncoursecompletion && !has_capability('mod/certificate:manage', $cminfo->context)) {
            $completioninfo = new completion_info($COURSE);
            if (!$completioninfo->is_course_complete($USER->id)) {
                $cminfo->set_no_view_link();
                $cminfo->set_content('');
                $cminfo->set_user_visible(false);
                return;
            }
        }
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all posts from the specified certificate
 * and clean up any related data.
 *
 * Written by Jean-Michel Vedrine
 *
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function certificate_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'certificate');
    $status = array();

    if (!empty($data->reset_certificate)) {
        $sql = "SELECT cert.id
                FROM {certificate} cert
                WHERE cert.course = :courseid";
        $DB->delete_records_select('certificate_issues', "certificateid IN ($sql)", array('courseid' => $data->courseid));
        $status[] = array('component' => $componentstr, 'item' => get_string('certificateremoved', 'certificate'), 'error' => false);
    }

    // Updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('certificate', array('timeopen', 'timeclose'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    return $status;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the certificate.
 *
 * Written by Jean-Michel Vedrine
 *
 * @param $mform form passed by reference
 */
function certificate_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'certificateheader', get_string('modulenameplural', 'certificate'));
    $mform->addElement('advcheckbox', 'reset_certificate', get_string('deletissuedcertificates', 'certificate'));
}

/**
 * Course reset form defaults.
 *
 * Written by Jean-Michel Vedrine
 *
 * @param stdClass $course
 * @return array
 */
function certificate_reset_course_form_defaults($course) {
    return array('reset_certificate' => 1);
}

/**
 * Returns information about received certificate.
 * Used for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $certificate
 * @return stdClass the user outline object
 */
function certificate_user_outline($course, $user, $mod, $certificate) {
    global $DB;

    $result = new stdClass;
    if ($issue = $DB->get_record('certificate_issues', array('certificateid' => $certificate->id, 'userid' => $user->id))) {
        $result->info = get_string('issued', 'certificate');
        $result->time = $issue->timecreated;
    } else {
        $result->info = get_string('notissued', 'certificate');
    }

    return $result;
}

/**
 * Returns information about received certificate.
 * Used for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $page
 * @return string the user complete information
 */
function certificate_user_complete($course, $user, $mod, $certificate) {
   global $DB, $OUTPUT;

   if ($issue = $DB->get_record('certificate_issues', array('certificateid' => $certificate->id, 'userid' => $user->id))) {
        echo $OUTPUT->box_start();
        echo get_string('issued', 'certificate') . ": ";
        echo userdate($issue->timecreated);
        certificate_print_user_files($certificate->id, $user->id);
        echo '<br />';
        echo $OUTPUT->box_end();
    } else {
        print_string('notissuedyet', 'certificate');
    }
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of certificate.
 *
 * @param int $certificateid
 * @return stdClass list of participants
 */
function certificate_get_participants($certificateid) {
    global $DB;

    $sql = "SELECT DISTINCT u.id, u.id
            FROM {user} u, {certificate_issues} a
            WHERE a.certificateid = :certificateid
            AND u.id = a.userid";
    return  $DB->get_records_sql($sql, array('certificateid' => $certificateid));
}

/**
 * Function to be run periodically according to the moodle cron
 * TODO:This needs to be done
 */
function certificate_cron () {
    return true;
}

/**
 * Returns a list of teachers by group
 * for sending email alerts to teachers
 *
 * @param stdClass $certificate
 * @param stdClass $user
 * @param stdClass $course
 * @param stdClass $cm
 * @return array the teacher array
 */
function certificate_get_teachers($certificate, $user, $course, $cm) {
    global $USER, $DB;

    $context = context_module::instance($cm->id);
    $potteachers = get_users_by_capability($context, 'mod/certificate:manage',
        '', '', '', '', '', '', false, false);
    if (empty($potteachers)) {
        return array();
    }
    $teachers = array();
    if (groups_get_activity_groupmode($cm, $course) == SEPARATEGROUPS) {   // Separate groups are being used
        if ($groups = groups_get_all_groups($course->id, $user->id)) {  // Try to find all groups
            foreach ($groups as $group) {
                foreach ($potteachers as $t) {
                    if ($t->id == $user->id) {
                        continue; // do not send self
                    }
                    if (groups_is_member($group->id, $t->id)) {
                        $teachers[$t->id] = $t;
                    }
                }
            }
        } else {
            // user not in group, try to find teachers without group
            foreach ($potteachers as $t) {
                if ($t->id == $USER->id) {
                    continue; // do not send self
                }
                if (!groups_get_all_groups($course->id, $t->id)) { //ugly hack
                    $teachers[$t->id] = $t;
                }
            }
        }
    } else {
        foreach ($potteachers as $t) {
            if ($t->id == $USER->id) {
                continue; // do not send self
            }
            $teachers[$t->id] = $t;
        }
    }

    return $teachers;
}

/**
 * Serves certificate issues and other files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool|nothing false if file not found, does not return anything if found - just send the file
 */
function certificate_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if (!$certificate = $DB->get_record('certificate', array('id' => $cm->instance))) {
        return false;
    }

    require_login($course, false, $cm);

    require_once($CFG->libdir.'/filelib.php');

    $fs = get_file_storage();

    if ($filearea === 'issue') {
        $certrecord = (int)array_shift($args);

        if (!$certrecord = $DB->get_record('certificate_issues', array('id' => $certrecord))) {
            return false;
        }

        if ($USER->id != $certrecord->userid and !has_capability('mod/certificate:manage', $context)) {
            return false;
        }

        $relativepath = implode('/', $args);
        $fullpath = "/{$context->id}/mod_certificate/issue/$certrecord->id/$relativepath";

        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    } else {
        if (!in_array($filearea, array('printseal', 'printborders', 'printwatermark', 'printsignature'))) {
            return false;
        }

        $relativepath = implode('/', $args);
        $fullpath = "/{$context->id}/mod_certificate/{$filearea}{$certrecord->id}/$relativepath";

        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    }
}

/**
 * Search through all the modules for grade data for mod_form.
 *
 * @return array
 */
function certificate_get_grade_options() {
    $gradeoptions['0'] = get_string('no');
    $gradeoptions['1'] = get_string('coursegrade', 'certificate');

    return $gradeoptions;
}

/**
 * Search through all the modules for grade dates for mod_form.
 *
 * @return array
 */
function certificate_get_date_options() {
    $dateoptions['0'] = get_string('no');
    $dateoptions['1'] = get_string('issueddate', 'certificate');
    $dateoptions['2'] = get_string('completiondate', 'certificate');

    return $dateoptions;
}

/**
 * Get the course outcomes for for mod_form print outcome.
 *
 * @return array
 */
function certificate_get_outcomes() {
    global $COURSE, $DB;

    // get all outcomes in course
    $grade_seq = new grade_tree($COURSE->id, false, true, '', false);
    if ($grade_items = $grade_seq->items) {
        // list of item for menu
        $printoutcome = array();
        foreach ($grade_items as $grade_item) {
            if (isset($grade_item->outcomeid)){
                $itemmodule = $grade_item->itemmodule;
                $printoutcome[$grade_item->id] = $itemmodule . ': ' . $grade_item->get_name();
            }
        }
    }
    if (isset($printoutcome)) {
        $outcomeoptions['0'] = get_string('no');
        foreach ($printoutcome as $key => $value) {
            $outcomeoptions[$key] = $value;
        }
    } else {
        $outcomeoptions['0'] = get_string('nooutcomes', 'certificate');
    }

    return $outcomeoptions;
}

/**
 * Used for course participation report (in case certificate is added).
 *
 * @return array
 */
function certificate_get_view_actions() {
    return array('view', 'view all', 'view report');
}

/**
 * Used for course participation report (in case certificate is added).
 *
 * @return array
 */
function certificate_get_post_actions() {
    return array('received');
}

/**
 * Prepare to print an activity grade.
 *
 * @param stdClass $course
 * @param int $moduleid
 * @param int $userid
 * @return stdClass|bool return the mod object if it exists, false otherwise
 */
function certificate_get_mod_grade($course, $moduleid, $userid) {
    global $DB;

    $cm = $DB->get_record('course_modules', array('id' => $moduleid));
    $module = $DB->get_record('modules', array('id' => $cm->module));

    if ($grade_item = grade_get_grades($course->id, 'mod', $module->name, $cm->instance, $userid)) {
        $item = new grade_item();
        $itemproperties = reset($grade_item->items);
        foreach ($itemproperties as $key => $value) {
            $item->$key = $value;
        }
        $modinfo = new stdClass;
        $modinfo->name = utf8_decode($DB->get_field($module->name, 'name', array('id' => $cm->instance)));
        $grade = $item->grades[$userid]->grade;
        $item->gradetype = GRADE_TYPE_VALUE;
        $item->courseid = $course->id;

        $modinfo->points = grade_format_gradevalue($grade, $item, true, GRADE_DISPLAY_TYPE_REAL, $decimals = 2);
        $modinfo->percentage = grade_format_gradevalue($grade, $item, true, GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals = 2);
        $modinfo->letter = grade_format_gradevalue($grade, $item, true, GRADE_DISPLAY_TYPE_LETTER, $decimals = 0);

        if ($grade) {
            $modinfo->dategraded = $item->grades[$userid]->dategraded;
        } else {
            $modinfo->dategraded = time();
        }
        return $modinfo;
    }

    return false;
}

/**
 * Returns the date to display for the certificate.
 *
 * @param stdClass $certificate
 * @param stdClass $certrecord
 * @param stdClass $course
 * @param int $userid
 * @return string the date
 */
function certificate_get_date($certificate, $certrecord, $course, $userid = null) {
    global $DB, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $printconfig = unserialize(@$certificate->printconfig);

    // Set certificate date to current time, can be overwritten later
    $date = $certrecord->timecreated;

    if (@$printconfig->printdate == '2') {
        // Get the enrolment end date
        $sql = "SELECT MAX(c.timecompleted) as timecompleted
                FROM {course_completions} c
                WHERE c.userid = :userid
                AND c.course = :courseid";
        if ($timecompleted = $DB->get_record_sql($sql, array('userid' => $userid, 'courseid' => $course->id))) {
            if (!empty($timecompleted->timecompleted)) {
                $date = $timecompleted->timecompleted;
            }
        }
    } else if (@$printconfig->printdate > 2) {
        if ($modinfo = certificate_get_mod_grade($course, $printconfig->printdate, $userid)) {
            $date = $modinfo->dategraded;
        }
    }
    if (@$printconfig->printdate > 0) {
        if ($certificate->datefmt == 1) {
            $certificatedate = userdate($date, '%B %d, %Y');
        } else if ($certificate->datefmt == 2) {
            $suffix = certificate_get_ordinal_number_suffix(userdate($date, '%d'));
            $certificatedate = userdate($date, '%B %d' . $suffix . ', %Y');
        } else if ($certificate->datefmt == 3) {
            $certificatedate = userdate($date, '%d %B %Y');
        } else if ($certificate->datefmt == 4) {
            $certificatedate = userdate($date, '%B %Y');
        } else if ($certificate->datefmt == 5) {
            $certificatedate = userdate($date, get_string('strftimedate', 'langconfig'));
        }

        return $certificatedate;
    }

    return '';
}

/**
 * Returns the grade to display for the certificate.
 *
 * @param stdClass $certificate
 * @param stdClass $course
 * @param int $userid
 * @return string the grade result
 */
function certificate_get_grade($certificate, $course, $userid = null) {
    global $USER, $DB;

    $printconfig = unserialize($certificate->printconfig);

    if (empty($userid)) {
        $userid = $USER->id;
    }

    if (@$printconfig->printgrade > 0) {
        if ($printconfig->printgrade == 1) {
            if ($course_item = grade_item::fetch_course_item($course->id)) {
                // String used
                $strcoursegrade = get_string('coursegrade', 'certificate');

                $grade = new grade_grade(array('itemid' => $course_item->id, 'userid' => $userid));
                $course_item->gradetype = GRADE_TYPE_VALUE;
                $coursegrade = new stdClass;
                $coursegrade->points = grade_format_gradevalue($grade->finalgrade, $course_item, true, GRADE_DISPLAY_TYPE_REAL, $decimals = 2);
                $coursegrade->percentage = grade_format_gradevalue($grade->finalgrade, $course_item, true, GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals = 2);
                $coursegrade->letter = grade_format_gradevalue($grade->finalgrade, $course_item, true, GRADE_DISPLAY_TYPE_LETTER, $decimals = 0);

                if ($certificate->gradefmt == 1) {
                    $grade = $strcoursegrade . ':  ' . $coursegrade->percentage;
                } else if ($certificate->gradefmt == 2) {
                    $grade = $strcoursegrade . ':  ' . $coursegrade->points;
                } else if ($certificate->gradefmt == 3) {
                    $grade = $strcoursegrade . ':  ' . $coursegrade->letter;
                }

                return $grade;
            }
        } else { // Print the mod grade
            if ($modinfo = certificate_get_mod_grade($course, $printconfig->printgrade, $userid)) {
                // String used
                $strgrade = get_string('grade', 'certificate');
                if ($certificate->gradefmt == 1) {
                    $grade = $modinfo->name . ' ' . $strgrade . ': ' . $modinfo->percentage;
                } else if ($certificate->gradefmt == 2) {
                    $grade = $modinfo->name . ' ' . $strgrade . ': ' . $modinfo->points;
                } else if ($certificate->gradefmt == 3) {
                    $grade = $modinfo->name . ' ' . $strgrade . ': ' . $modinfo->letter;
                }

                return $grade;
            }
        }
    }

    return '';
}

/**
 * Returns the outcome to display on the certificate
 *
 * @param stdClass $certificate
 * @param stdClass $course
 * @return string the outcome
 */
function certificate_get_outcome($certificate, $course) {
    global $USER, $DB;

    $printconfig = unserialize($certificate->printconfig);

    if (@$printconfig->printoutcome > 0) {
        if ($grade_item = new grade_item(array('id' => $printconfig->printoutcome))) {
            $outcomeinfo = new stdClass;
            $outcomeinfo->name = $grade_item->get_name();
            $outcome = new grade_grade(array('itemid' => $grade_item->id, 'userid' => $USER->id));
            $outcomeinfo->grade = grade_format_gradevalue($outcome->finalgrade, $grade_item, true, GRADE_DISPLAY_TYPE_REAL);

            return $outcomeinfo->name . ': ' . $outcomeinfo->grade;
        }
    }

    return '';
}

function certificate_is_available_time($linked_acts, $courseid, $userid = 0) {
    global $USER, $DB;

    if (!$userid) {
        $userid = $USER->id;
    }
    $message = '';
    require_once('timinglib.php');
    $tlcoursetime = tl_get_course_time($courseid, $userid);
    foreach ($linked_acts as $key => $activity) {
        if ($activity->linkid == CERTCOURSETIMEID) {
            if (($activity->linkgrade != 0) &&
                (($tlcoursetime/60) < $activity->linkgrade)) {
                return false;
            }
        }
    }
    return true;
}

/**
 * Scans directory for valid images
 *
 * @param string the path
 * @return array
 */
function certificate_scan_image_dir($path) {
    // Array to store the images
    $options = array();

    // Start to scan directory
    if (is_dir($path)) {
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if (strpos($file, '.png', 1) || strpos($file, '.jpg', 1) ) {
                    $i = strpos($file, '.');
                    if ($i > 1) {
                        // Set the name
                        $options[$file] = substr($file, 0, $i);
                    }
                }
            }
            closedir($handle);
        }
    }

    return $options;
}

/**
 * Obtains the automatic completion state for this certificate 
 *
 * @global object
 * @global object
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function certificate_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get certificate details
    if (!($certificate = $DB->get_record('certificate', array('id' => $cm->instance)))) {
        throw new Exception("Can't find certificate {$cm->instance}");
    }

    $result = $type; // Default return value
    
    // completion condition 1 : being delivered to user

    if ($certificate->completiondelivered) {
    }

    return $result;
}

function certificate_get_string($identifier, $subplugin, $a = '', $lang = ''){
    global $CFG;
    
    static $typestrings = array();
    
    if (empty($typestrings[$subplugin])){
    
        if (empty($lang)) $lang = current_language();
        
        if (file_exists($CFG->dirroot.'/mod/certificate/type/'.$subplugin.'/lang/en/'.$subplugin.'.php')){
            include $CFG->dirroot.'/mod/certificate/type/'.$subplugin.'/lang/en/'.$subplugin.'.php';
        } else {
            debugging('English lang file must exist', DEBUG_DEVELOPER);
        }
    
        // override with lang file if exists
        if (file_exists($CFG->dirroot.'/mod/certificate/type/'.$subplugin.'/lang/'.$lang.'/'.$subplugin.'.php')){
            include $CFG->dirroot.'/mod/certificate/type/'.$subplugin.'/lang/'.$lang.'/'.$subplugin.'.php';
        }
        $typestrings[$subplugin] = $string;
    }
    
    if (array_key_exists($identifier, $typestrings[$subplugin])){
        $result = $typestrings[$subplugin][$identifier];
        if ($a !== NULL) {
            if (is_object($a) or is_array($a)) {
                $a = (array)$a;
                $search = array();
                $replace = array();
                foreach ($a as $key => $value) {
                    if (is_int($key)) {
                        // we do not support numeric keys - sorry!
                        continue;
                    }
                    $search[]  = '{$a->'.$key.'}';
                    $replace[] = (string)$value;
                }
                if ($search) {
                    $result = str_replace($search, $replace, $result);
                }
            } else {
                $result = str_replace('{$a}', (string)$a, $result);
            }
        }
        // Debugging feature lets you display string identifier and component
        if (!empty($CFG->debugstringids) || optional_param('strings', 0, PARAM_INT)) {
            $result .= ' {' . $identifier . '/' . $subplugin . '}';
        }
        return $result;
    }

    if (!empty($CFG->debugstringids) && optional_param('strings', 0, PARAM_INT)) {
        return "[[$identifier/$subplugin]]";
    } else {
        return "[[$identifier]]";
    }
}
