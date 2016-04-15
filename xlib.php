<?php

require_once $CFG->dirroot.'/mod/certificate/lib.php';

function certificate_get_user_certificates($course, $userid) {
    global $DB, $USER;

    $module = $DB->get_record('modules', array('name' => 'certificate'));

    $sql = "
        SELECT DISTINCT
            ce.*,
            c.shortname,
            c.fullname,
            cm.id as cmid
        FROM
            {course_modules} cm,
            {course} c,
            {certificate} ce
        WHERE
            cm.module = ? AND
            cm.instance = ce.id AND
            ce.course = c.id AND
            c.id = ?
        GROUP BY
            ce.id
        ORDER BY
            c.shortname
    ";

    if ($instances = $DB->get_records_sql($sql, array($module->id, $course->id))) {

        foreach ($instances as $key => $instance) {

            $context = context_module::instance($instance->cmid);

            // Rip off all those you ($USER) do not have manager access in
            if (!has_capability('mod/certificate:manage', $context)) {
                unset($instances[$key]);
            }

            // Rip off those where user is not ready. Care that certificate_check_conditions() is negative logic
            $cm = $DB->get_record('course_modules', array('id' => $instance->cmid));
            $instances[$key]->issued = $DB->record_exists('certificate_issues', array('certificateid' => $instance->id, 'userid' => $userid));
            if (!$instances[$key]->issued && certificate_check_conditions($instance, $cm, $userid)) {
                unset($instances[$key]);
            }
        }
        return $instances;
    }

    return array();

}