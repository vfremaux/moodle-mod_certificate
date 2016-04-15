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
 * This page lists all the instances of certificate in a particular course
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die("You cannot use this script this way");
}

// Regenerate a certificate file on demand.
if ($action == 'regenerate') {

    if (!$ccode) {
        echo $OUTPUT->notification('Missing cert code');
        return;
    }
    
    require_sesskey();

    require_once($CFG->libdir.'/pdflib.php');

    $filesafe = clean_filename($certificate->name.'.pdf');

    $fs = get_file_storage();

    $certrecord = $DB->get_record('certificate_issues', array('code' => $ccode));
    $fs->delete_area_files($context->id, 'mod_certificate', 'issue', $certrecord->id);

    $user = $DB->get_record('user', array('id' => $certrecord->userid));

    // This creates the $pdf instance.
    // Load the specific certificate type.
    require($CFG->dirroot.'/mod/certificate/type/'.$certificate->certificatetype.'/certificate.php');
    $certname = rtrim($certificate->name, '.');
    $filename = clean_filename("$certname.pdf");
    $file_contents = $pdf->Output('', 'S');
    if ($certificate->savecert == 1) {
        certificate_save_pdf($file_contents, $certrecord->id, $filesafe, $context->id);
        if ($certificate->delivery == 2) {
            certificate_email_students($user, $course, $certificate, $certrecord);
        }
    }
    process_chain($user, $certificate);
}

if ($action == 'generateall') {
    foreach ($total as $u) {
        $errors = certificate_check_conditions($certificate, $cm, $u->id);
        if (empty($errors)) {
            if (!isset($userids)) {
                $userids = array();
            }
            $userids[] = $u->id;
        }
    }

    if (!empty($userids)){
        $action = 'generate';
    }
}

if ($action == 'generate') {
    if (!isset($userids)) {
        $userids = required_param_array('userids', PARAM_INT); // Gets an array of user ids to generate.
    }

    if (!empty($userids)) {

        require_once($CFG->libdir.'/pdflib.php');

        make_cache_directory('tcpdf');

        // load some usefull strings
        $strreviewcertificate = get_string('reviewcertificate', 'certificate');
        $strgetcertificate = get_string('getcertificate', 'certificate');
        $strgrade = get_string('grade', 'certificate');
        $strcoursegrade = get_string('coursegrade', 'certificate');
        $strcredithours = get_string('credithours', 'certificate');

        $filesafe = clean_filename($certificate->name.'.pdf');
        $totalcertifiedcount = 0;

        foreach ($userids as $uid) {
            $user = $DB->get_record('user', array('id' => $uid));
            $certrecord = certificate_get_issue($course, $user, $certificate, $cm);
            $totalcertifiedcount++;

            // This creates the $pdf instance.
            // Load the specific certificate type.
            require($CFG->dirroot.'/mod/certificate/type/'.$certificate->certificatetype.'/certificate.php');
            $certname = rtrim($certificate->name, '.');
            $filename = clean_filename("$certname.pdf");
            $file_contents = $pdf->Output('', 'S');
            if ($certificate->savecert == 1) {
                certificate_save_pdf($file_contents, $certrecord->id, $filesafe, $context->id);
                if ($certificate->delivery == 2) {
                    certificate_email_students($user, $course, $certificate, $certrecord);
                }
            }
            process_chain($user, $certificate);
        }
    }
}

if ($action == 'deletesingle') {
    // Administrators can delete individual certificates if were abusively generated.

    if (has_capability('moodle/site:config', context_system::instance())) {
    }

    if (!$ccode) {
        echo $OUTPUT->notification('Missing cert code');
        return;
    }

    $fs = get_file_storage();

    $certrecord = $DB->get_record('certificate_issues', array('code' => $ccode));
    $fs->delete_area_files($context->id, 'mod_certificate', 'issue', $certrecord->id);
    $DB->delete_records('certificate_issues', array('code' => $ccode));
}

// *** NOT YET PROPOSED IN GUI *****
if ($action == 'delete') {
    if (!has_capability('mod/certificate:deletecertificates', $context)) {
        print_error('errornocapabilitytodelete', 'certificate', new moodle_url('/course/view.php', array('id' => $course->id)));
    }
    $userids = required_param_array('userids', PARAM_INT); // gets an array of user ids to generate.
    if (!empty($userids)) {
        $userlist = implode(",", $userids);

        // Retrieve all rec ids.
        if ($recstodelete = $DB->get_records('certificate_issues', " userid IN ('$userlist') AND certificateid = ? ", array($certificate->id))) {
            foreach ($recstodelete as $rec) {
                $deleted[] = $rec->id;
            }
        }

        // Delete records.
        $DB->delete_records_select('certificate_issues', " userid IN ('$userlist') AND certificateid = ? ", array($certificate->id));
        $totalcertifiedcount--;

        $filesafe = clean_filename($certificate->name.'.pdf');
        $fs = get_file_storage();

        // Delete files if required.
        if (!empty($deleted)) {
            foreach ($deleted as $recid) {
                if ($certificate->savecert == 1) {
                    $fs->delete_area_files($context->id, 'mod_certificate', 'issue', $recid);
                }
            }
        }
    }
}