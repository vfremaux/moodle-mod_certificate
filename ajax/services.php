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

require('../../../config.php');

$id = required_param('id', PARAM_INT);    // Course Module ID.
$action = optional_param('what', '', PARAM_ALPHA);

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

switch ($action) {
    case 'savelayout' :
        $certificate->layout = optional_param('layout', '', PARAM_RAW);
        $DB->update_record('certificate', $certificate);
}