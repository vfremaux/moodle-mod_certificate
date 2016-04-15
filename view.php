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
 * Handles viewing a certificate
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/certificate/deprecatedlib.php');
require_once($CFG->dirroot.'/mod/certificate/lib.php');
require_once($CFG->libdir.'/pdflib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID.
$action = optional_param('what', '', PARAM_ALPHA);
$edit = optional_param('edit', -1, PARAM_BOOL);

if (!$cm = $DB->get_record('course_modules', array('id' => $id))) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

if (!$certificate = $DB->get_record('certificate', array('id' => $cm->instance))) {
    print_error('course module is incorrect');
}

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/certificate:view', $context);

// Log update.
// add_to_log($course->id, 'certificate', 'view', "view.php?id=$cm->id", $certificate->id, $cm->id);

// Trigger module viewed event.
$eventparams = array(
    'objectid' => $certificate->id,
    'context' => $context,
);

$event = \mod_certificate\event\course_module_viewed::create($eventparams);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('certificate', $certificate);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Initialize $PAGE.

$PAGE->set_url('/mod/certificate/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($certificate->name));
$PAGE->set_heading(format_string($course->fullname));

// Set the context
$context = context_module::instance($cm->id);

if (($edit != -1) and $PAGE->user_allowed_editing()) {
     $USER->editing = $edit;
}

// Add block editing button.
if ($PAGE->user_allowed_editing()) {
    $editvalue = $PAGE->user_is_editing() ? 'off' : 'on';
    $strsubmit = $PAGE->user_is_editing() ? get_string('blockseditoff') : get_string('blocksediton');
    $url = new moodle_url('/mod/certificate/view.php', array('id' => $cm->id, 'edit' => $editvalue));
    $PAGE->set_button($OUTPUT->single_button($url, $strsubmit));
}

// Check if the user can view the certificate.
if ($certificate->requiredtime && !has_capability('mod/certificate:manage', $context)) {
    if (certificate_get_course_time($course->id) < ($certificate->requiredtime * 60)) {
        $a = new stdClass;
        $a->requiredtime = $certificate->requiredtime;
        echo $OUTPUT->notification(get_string('requiredtimenotmet', 'certificate', $a), new moodle_url('/course/view.php', array('id' => $course->id)));
        die;
    }
}

if ($certificate->lockoncoursecompletion && !has_capability('mod/certificate:manage', $context)) {
    $completioninfo = new completion_info($course);
    if (!$completioninfo->is_course_complete($USER->id)) {
        echo $OUTPUT->notification(get_string('requiredcoursecompletion', 'certificate'), new moodle_url('/course/view.php', array('id' => $course->id)));
    }
}

// Create new certificate record, or return existing record.
$certrecord = certificate_get_issue($course, $USER, $certificate, $cm);

if ($certrecord && !has_any_capability(array('mod/certificate:manage', 'mod/certificate:getown'), $context)) {
    /*
     * student can not access to his certificate because not allowed
     * probably the certificate needs to be delivered by another person
     */
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('certification', 'certificate')); 
    echo $OUTPUT->box(get_string('certificationmatchednotdeliverable', 'certificate'), 'certificate-notice-box'); 
    echo $OUTPUT->footer();
    die;
}

// Create a directory that is writeable so that TCPDF can create temp images.
// In 2.2 onwards the function make_cache_directory was introduced, use that,
// otherwise we will use make_upload_directory.
make_cache_directory('tcpdf');

// Load the specific certificate type.
$user = $USER; // see for self
require($CFG->dirroot.'/mod/certificate/type/'.$certificate->certificatetype.'/certificate.php');

if (empty($action)) { // Not displaying PDF
    echo $OUTPUT->header();

    // Find out current groups mode.
    groups_print_activity_menu($cm, new moodle_url('/mod/certificate/view.php', array('id' => $cm->id)));
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

    if (!empty($certificate->intro)) {
        echo $OUTPUT->box(format_module_intro('certificate', $certificate, $cm->id), 'generalbox', 'intro');
    }

    if ($attempts = certificate_get_attempts($certificate->id)) {
        echo certificate_print_attempts($course, $certificate, $attempts);
    }

    if ($certificate->delivery == 0) {
        $str = get_string('openwindow', 'certificate');
    } elseif ($certificate->delivery == 1) {
        $str = get_string('opendownload', 'certificate');
    } elseif ($certificate->delivery == 2) {
        $str = get_string('openemail', 'certificate');
    }

    echo html_writer::tag('p', $str, array('style' => 'text-align:center'));
    $linkname = get_string('getcertificate', 'certificate');

    $link = new moodle_url('/mod/certificate/view.php', array('id' => $cm->id, 'what' => 'get'));
    $button = new single_button($link, $linkname);
    $button->add_action(new popup_action('click', $link, 'view'.$cm->id, array('height' => 600, 'width' => 800)));

    $coursecontext = context_course::instance($COURSE->id);

    if (has_capability('mod/certificate:getown', $context, $USER, false)) {
        $linkname = get_string('getcertificate', 'certificate');
        $link = new moodle_url('/mod/certificate/view.php', array('id' => $cm->id, 'what' => 'get'));
        $button = new single_button($link, $linkname);
        $button->add_action(new popup_action('click', $link, 'view'.$cm->id, array('height' => 600, 'width' => 800)));
        echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));
        $confirm = true;
    } elseif (has_capability('mod/certificate:addinstance', $coursecontext)) {
        $linkname = get_string('gettestcertificate', 'certificate');
        $link = new moodle_url('/mod/certificate/view.php', array('id' => $cm->id, 'what' => 'get'));
        $button = new single_button($link, $linkname);
        $button->add_action(new popup_action('click', $link, 'view'.$cm->id, array('height' => 600, 'width' => 800)));
        echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));

        if (has_capability('mod/certificate:manage', $context)) {
            $numusers = count(certificate_get_issues($certificate->id, 'ci.timecreated ASC', $groupmode, $cm));
            $linkname = get_string('viewcertificateviews', 'certificate', $numusers);
            $link = new moodle_url('/mod/certificate/report.php', array('id' => $cm->id));
            $button = new single_button($link, $linkname);
            echo '<div style="text-align:center"><a href="'.$link.'">'.$OUTPUT->render($button).'</a></div>';

            /*
            // Not ready.
            $linkname = get_string('editcertificatelayout', 'certificate');
            $link = new moodle_url('/mod/certificate/formatbuilder.php', array('id' => $cm->id));
            $button = new single_button($link, $linkname);
            $button->add_action(new popup_action('click', $link, 'view'.$cm->id, array('height' => 600, 'width' => 800)));
            echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));
            */
        }
    }

    echo $OUTPUT->footer();

    exit;
} else { // Output to pdf

    // Trigger module viewed event.
    $eventparams = array(
        'objectid' => $certificate->id,
        'context' => $context,
    );

    $event = \mod_certificate\event\course_module_issued::create($eventparams);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('certificate', $certificate);
    $event->trigger();

    // Remove full-stop at the end if it exists, to avoid "..pdf" being created and being filtered by clean_filename
    $certname = rtrim($certificate->name, '.');
    $filename = clean_filename("$certname.pdf");
    certificate_confirm_issue($user, $certificate, $cm);
    if ($certificate->savecert == 1) {
        // PDF contents are now in $file_contents as a string
       $file_contents = $pdf->Output('', 'S');
       certificate_save_pdf($file_contents, $certrecord->id, $filename, $context->id);
    }
    if ($certificate->delivery == 0) {
        $pdf->Output($filename, 'I'); // open in browser
    } elseif ($certificate->delivery == 1) {
        $pdf->Output($filename, 'D'); // force download when create
    } elseif ($certificate->delivery == 2) {
        certificate_email_student($course, $certificate, $certrecord, $context);
        $pdf->Output($filename, 'I'); // open in browser
        $pdf->Output('', 'S'); // send
    }
}
