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
 * Deprecated certificate functions.
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Prepare to be print the date -- defaults to time.
 *
 * @deprecated since certificate version 2012052501
 * @param stdClass $certificate
 * @param stdClass $course
 * @return string the date
 */
function certificate_generate_date($certificate, $course) {
    debugging('certificate_generate_date is deprecated, please use certificate_get_date instead which will
               return a date in a human readable format.', DEBUG_DEVELOPER);

    global $DB;

    // Set certificate date to current time, can be overwritten later
    $date = time();

    $printconfig = unserialize($certificate->printconfig);

    if ($printconfig->printdate == '2') {
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
    } elseif ($printconfig->printdate > 2) {
        if ($modinfo = certificate_get_mod_grade($course, $printconfig->printdate, $userid)) {
            $date = $modinfo->dategraded;
        }
    }

    return $date;
}

