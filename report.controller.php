<?php

if (!defined('MOODLE_INTERNAL')) die("You cannot use this script this way");

if ($action == 'generateall'){

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
        
        require_once("$CFG->libdir/pdflib.php");

        make_cache_directory('tcpdf');

        // load some usefull strings
        $strreviewcertificate = get_string('reviewcertificate', 'certificate');
        $strgetcertificate = get_string('getcertificate', 'certificate');
        $strgrade = get_string('grade', 'certificate');
        $strcoursegrade = get_string('coursegrade', 'certificate');
        $strcredithours = get_string('credithours', 'certificate');

        $filesafe = clean_filename($certificate->name.'.pdf');

        foreach ($userids as $uid) {
            $user = $DB->get_record('user', array('id' => $uid));
            $certrecord = certificate_get_issue($course, $user, $certificate, $cm);
            $totalcertifiedcount++;

            // This creates the $pdf instance.
            // Load the specific certificate type.
            require("$CFG->dirroot/mod/certificate/type/$certificate->certificatetype/certificate.php");
            $certname = rtrim($certificate->name, '.');
            $filename = clean_filename("$certname.pdf");
            $file_contents = $pdf->Output('', 'S');
            if ($certificate->savecert == 1) {
                certificate_save_pdf($file_contents, $certrecord->id, $filesafe, $context->id);
                if ($certificate->delivery == 2) {
                    certificate_email_students($user, $course, $certificate, $certrecord);
                }
            }
        }
    }
}

if ($action == 'delete'){
    if (!has_capability('mod/certificate:deletecertificates', $context)) {
        print_error('errornocapabilitytodelete', 'certificate', $CFG->wwwroot.'/course/view.php?id='.$course->id);
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

        // delete records
        $DB->delete_records_select('certificate_issues', " userid IN ('$userlist') AND certificateid = ? ", array($certificate->id));
        $totalcertifiedcount--;

        $filesafe = clean_filename($certificate->name.'.pdf');
        $fs = get_file_storage();

        // delete files if required
        if (!empty($deleted)) {
            foreach ($deleted as $recid) {
                if ($certificate->savecert == 1) {
                    $fs->delete_area_files($context->id, 'mod_certificate', 'issue', $recid);
                }
            }
        }
    }
}