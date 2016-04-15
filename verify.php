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
 * Verify an issued certificate by code
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Carlos Fonseca <carlos.alexandre@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('verify_form.php');
require_once('lib.php');

//optional_param('id', $USER->id, PARAM_INT);
$code = optional_param('code', null, PARAM_ALPHANUMEXT); // Issued code to be checked
$wsquery = optional_param('ws', false, PARAM_BOOL); // Is the call coming from a WS requirer (Smartphone).

$context = context_system::instance();
$PAGE->set_url('/mod/certificate/verify.php', array('code' => $code));
$PAGE->set_context($context);
$PAGE->set_title(get_string('certificateverification', 'certificate'));
$PAGE->set_heading(get_string('certificateverification', 'certificate'));
$PAGE->set_pagelayout('base');

if (!$wsquery) {

    $verifyform = new verify_form();

    if (!$data = $verifyform->get_data()) {
        if ($code) {
            $verifyform->set_data(array('code' => $code));
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('certificateverification', 'certificate'));
        $verifyform->display();
        echo $OUTPUT->footer();
        die;
    }
}

if (!$issuedcert = $DB->get_record('certificate_issues', array('code' => $code))) {
    if ($wsquery) {
        $answer['status'] = 'failed';
        echo json_encode($answer);
        die;
    }

    $tryotherstr = get_string('tryothercode', 'certificate');

    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('invalidcode', 'certificate'), 'certificate-invalid-code');
    echo '<p><center><a href="'.$CFG->wwwroot.'/mod/certificate/verify.php">'.$tryotherstr.'</a></center></p>';
    echo $OUTPUT->footer();
    die;
}

if ($user = $DB->get_record('user', array('id' => $issuedcert->userid))) {
    $username = fullname($user);
} else {
    $username = get_string('notavailable');
}

if (!$certificate = $DB->get_record('certificate', array('id' => $issuedcert->certificateid))) {
    if ($wsquery) {
        $answer['status'] = 'failed';
        echo json_encode($answer);
        die;
    }
    print_error('errorinvalidinstance', 'certificate');
}
$cm = get_coursemodule_from_instance('certificate', $certificate->id);
$modulecontext = context_module::instance($cm->id);

$course = $DB->get_record('course', array('id' => $certificate->course));

if (!$wsquery) {
    // Getting course name (it's in filenema <COURSE NAME>-<CERTIFICATE NAME>_<ISSUEID>.pdf.

    $tostr = get_string('awardedto', 'certificate');
    $datestr = get_string('issueddate', 'certificate');
    $codestr = get_string('code', 'certificate');
    $captionstr = get_string('certificatecaption', 'certificate');
    $courseinfostr = get_string('coursename', 'certificate');
    $validuntilstr = get_string('validuntil', 'certificate');
    $expiredstr = get_string('expiredon', 'certificate');
    $definitivestr = get_string('definitive', 'certificate');
    $certificatefilestr = get_string('certificatefile', 'certificate');
    $certificatefilenoaccessstr = get_string('certificatefilenoaccess', 'certificate');

    // Add to log.
    // add_to_log($context->instanceid, 'certificate', 'verify', "verify.php?code=$code", '$issuedcert->id');

    // Trigger module viewed event.
    $eventparams = array(
        'objectid' => $certificate->id,
        'context' => $context,
    );

    $event = \mod_certificate\event\course_module_verified::create($eventparams);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('certificate', $certificate);
    $event->trigger();

    echo $OUTPUT->header();

    echo get_string('certificateverifiedstate', 'certificate');

    $table = new html_table();
    $table->width = '95%';
    $table->attributes = array('class' => 'generaltable certificate-check');
    $table->tablealign = 'center';
    $table->head = array('', '');
    $table->align = array('right', 'left');
    $table->colclasses = array('param', 'value');

    $table->data[] = array($codestr.':', $issuedcert->code);
    $table->data[] = array($captionstr.':', $certificate->caption);
    $table->data[] = array($courseinfostr.':', '<h3>'.$course->fullname.'</h3><br/>'.$course->summary);
    $table->data[] = array($tostr.':', $username);
    $table->data[] = array($datestr.':', userdate($issuedcert->timecreated));
    $expiredate = $issuedcert->timecreated + $certificate->validitytime * DAYSECS;
    if ($certificate->validitytime) {
        $class = ($expiredate > time()) ? 'certificate-valid' : 'certificate-invalid' ;
        $table->data[] = array($expiredstr.':', '<div class="'.$class.'">'.userdate($issuedcert->timecreated + $certificate->validitytime * DAYSECS).'</div>');
    } else {
        $table->data[] = array($validuntilstr.':', '<div class="certificate-valid">'.$definitivestr.'</div>');
    }

    if ($certificate->savecert) {
        if (isloggedin()) {
            $table->data[] = array($certificatefilestr.':', certificate_print_user_files($certificate, $user->id, $modulecontext->id));
        } else {
            $table->data[] = array($certificatefilestr.':', $certificatefilenoaccessstr);
        }
    }

    echo html_writer::table($table);

    echo $OUTPUT->footer();
} else {
    $expiredate = $issuedcert->timecreated + $certificate->validitytime;
    $answer['name'] = $username;
    $answer['status'] = ($expiredate > time()) ? 'valid' : 'invalid';
    $answer['expiration'] = userdate($expiredate);
    $answer['issued'] = userdate($issuedcert->timecrreated);
    echo json_encode($answer);
    die;
}

