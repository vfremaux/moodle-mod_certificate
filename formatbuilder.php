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

require('../../config.php');
require_once($CFG->dirroot.'/mod/certificate/formatbuilderlib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID.
$action = optional_param('what', '', PARAM_ALPHA);
$preview = optional_param('preview', false, PARAM_BOOL);

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
require_capability('mod/certificate:manage', $context);

echo '<!DOCTYPE html><html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type">';

echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/certificate/js/jquery-1.8.2.min.js"></script>';
echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/certificate/js/formatbuilder.js"></script>';
echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/certificate/js/init.js"></script>';
switch ($certificate->certificatetype) {
    case 'A4_embedded': {
        $buildcss = 'A4';
        break;
    }
    case 'A4_non_embedded': {
        $buildcss = 'A4';
        break;
    }
    case 'letter_embedded': {
        $buildcss = 'letter';
        break;
    }
    case 'letter_non_embedded': {
        $buildcss = 'letter';
        break;
    }
}
echo '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/mod/certificate/css/common_builder.css" />';
echo '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/mod/certificate/css/'.$buildcss.'_'.$certificate->orientation.'_builder.css" />';
echo '</head>';

echo '<body>';
echo '<div id="mod-certificate-builder">';

$printconfig = unserialize(@$certificate->printconfig);

$defaults = certificate_print_defaults($certificate);

$fs = get_file_storage();
$cm = get_coursemodule_from_instance('certificate', $certificate->id);
$context = context_module::instance($cm->id);

echo certificate_builder_image($certificate, $cm, 'printborders', $fs, $defaults, array('z-order' => -10000));
echo certificate_builder_image($certificate, $cm, 'printwatermark', $fs, $defaults, array('z-order' => -100));
echo certificate_builder_image($certificate, $cm, 'printseal', $fs, $defaults, array('z-order' => -100));
echo certificate_builder_image($certificate, $cm, 'printsignature', $fs, $defaults, array('z-order' => -100));

echo certificate_builder_text($certificate, 'title', get_string('title', 'certificate'), $defaults);

if ($certificate->certifierid) {
    $authority = $DB->get_record('user', array('id' => $certificate->certifierid));
    echo certificate_builder_text($certificate, 'authority', fullname($authority), $defaults);
}

echo certificate_builder_text($certificate, 'user', '[['.get_string('user').']]', $defaults);

echo certificate_builder_text($certificate, 'statement', get_string('statement', 'certificate'), $defaults);

echo certificate_builder_text($certificate, 'coursename', $COURSE->fullname, $defaults);

echo certificate_builder_text($certificate, 'date', '[['.get_string('date').']]', $defaults);
echo certificate_builder_text($certificate, 'grade', '[['.get_string('grade').']]', $defaults);
echo certificate_builder_text($certificate, 'outcome', '[['.get_string('outcome', 'certificate').']]', $defaults);


if (!empty($certificate->customtext)) {
    echo certificate_builder_text($certificate, 'customtext', $certificate->customtext, $defaults);
}

if (!empty($printconfig->printhours)) {
    echo certificate_builder_text($certificate, 'printhours', $printconfig->printhours.' '.get_string('credithours', 'certificate'), $defaults);
}

if (!empty($printconfig->printcode)) {
    echo certificate_builder_text($certificate, 'code', '[['.get_string('code', 'certificate').']]', $defaults);
}

if (!empty($printconfig->printqrcode)) {
    echo certificate_builder_image($certificate, $cm, 'pix/qrcode', null, $defaults);
}

echo '</div>';
echo '<center>';
echo '<input type="button" name="go_save" value="'.get_string('savelayout', 'certificate').'" onclick="saveandclose(\''.$CFG->wwwroot.'\', '.$certificate->id.')" />';
echo '</center>';
echo '</body></html>';