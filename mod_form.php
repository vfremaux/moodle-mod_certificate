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
 * Instance add/edit form
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/certificate/lib.php');
require_once($CFG->dirroot.'/mod/certificate/locallib.php');

class mod_certificate_mod_form extends moodleform_mod {

    var $instance;

    function definition() {
        global $CFG, $DB, $COURSE;

        $mform =& $this->_form;

        $this->instance = $DB->get_record('certificate', array('id' => $this->_instance));

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('certificatename', 'certificate'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'caption', get_string('certificatecaption', 'certificate'), array('size' => 128, 'maxlength' => 255));
        $mform->setType('caption', PARAM_CLEANHTML);

        $this->standard_intro_elements();

        // Issue options
        $mform->addElement('header', 'issueoptions', get_string('issueoptions', 'certificate'));
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        $mform->addElement('select', 'emailteachers', get_string('emailteachers', 'certificate'), $ynoptions);
        $mform->setDefault('emailteachers', 0);
        $mform->addHelpButton('emailteachers', 'emailteachers', 'certificate');

        $mform->addElement('text', 'emailothers', get_string('emailothers', 'certificate'), array('size'=>'40', 'maxsize'=>'200'));
        $mform->setType('emailothers', PARAM_TEXT);
        $mform->addHelpButton('emailothers', 'emailothers', 'certificate');

        $deliveryoptions = array( 0 => get_string('openbrowser', 'certificate'), 1 => get_string('download', 'certificate'), 2 => get_string('emailcertificate', 'certificate'));
        $mform->addElement('select', 'delivery', get_string('delivery', 'certificate'), $deliveryoptions);
        $mform->setDefault('delivery', 0);
        $mform->addHelpButton('delivery', 'delivery', 'certificate');

        $mform->addElement('select', 'savecert', get_string('savecert', 'certificate'), $ynoptions);
        $mform->setDefault('savecert', 0);
        $mform->addHelpButton('savecert', 'savecert', 'certificate');

        $reportfile = "$CFG->dirroot/certificates/index.php";
        if (file_exists($reportfile)) {
            $mform->addElement('select', 'reportcert', get_string('reportcert', 'certificate'), $ynoptions);
            $mform->setDefault('reportcert', 0);
            $mform->addHelpButton('reportcert', 'reportcert', 'certificate');
        }

        $this->linkablecourses = certificate_get_linkable_courses($this->instance);
        $this->assignableroles = get_assignable_roles(context_course::instance($COURSE->id));

        $authorities = array();
        $authorities[0] = get_string('noauthority', 'certificate');
        if ($authorities_candidates = get_users_by_capability(context_course::instance($COURSE->id), 'mod/certificate:isauthority', 'u.id,'.get_all_user_name_fields(true, 'u'), 'lastname,firstname')){
            foreach ($authorities_candidates as $ac) {
                $authorities[$ac->id] = fullname($ac);
            }
        }

        $mform->addElement('select', 'certifierid', get_string('certifierid', 'certificate'), $authorities);
        $mform->setDefault('setcertification', 0 + @$CFG->certificate_certification_authority); // choose the default system designed
        $mform->addHelpButton('certifierid', 'certifierid', 'certificate');

        $roleoptions = $this->assignableroles;
        $roleoptions['0'] = get_string('none', 'certificate');
        ksort($roleoptions);
        $mform->addElement('select', 'setcertification',get_string('setcertification', 'certificate'), $roleoptions);
        $mform->setDefault('setcertification', max(array_keys($roleoptions))); // choose the weaker role (further from admin role)
        $mform->addHelpButton('setcertification', 'setcertification', 'certificate');

        $contextoptions = certificate_get_possible_contexts();
        $mform->addElement('select', 'setcertificationcontext',get_string('setcertificationcontext', 'certificate'), $contextoptions);
        $mform->setDefault('setcertificationcontext', max(array_keys($contextoptions))); // choose the weaker context
        $mform->addHelpButton('setcertification', 'setcertification', 'certificate');

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'lockingoptions', get_string('lockingoptions', 'certificate'));
        
        $this->restrictoptions = array();
        $this->restrictoptions[0]  = get_string('no');
        for ($i = 100; $i > 0; $i--) {
            $this->restrictoptions[$i] = $i.'%';
        }

        $mform->addElement('text', 'requiredtime', get_string('coursetimereq', 'certificate'), array('size'=>'3'));
        $mform->setType('requiredtime', PARAM_INT);
        $mform->addHelpButton('requiredtime', 'coursetimereq', 'certificate');

        $validityoptions = array(
            '0' => get_string('unlimited', 'certificate'),
            '1' => get_string('oneday', 'certificate'),
            '7' => get_string('oneweek', 'certificate'),
            '30' => get_string('onemonth', 'certificate'),
            '90' => get_string('threemonths', 'certificate'),
            '180' => get_string('sixmonths', 'certificate'),
            '365' => get_string('oneyear', 'certificate'),
            '730' => get_string('twoyears', 'certificate'),
            '1095' => get_string('threeyears', 'certificate'),
            '1895' => get_string('fiveyears', 'certificate'),
            '3650' => get_string('tenyears', 'certificate'),
        );

        $mform->addElement('select', 'validitytime', get_string('validity', 'certificate'), $validityoptions);
        $mform->setDefault('validitytime', 0);
        $mform->addHelpButton('validitytime', 'validitytime', 'certificate');

        $completioninfo = new completion_info($COURSE);
        if ($completioninfo->is_enabled(null)) {
            $mform->addElement('checkbox', 'lockoncoursecompletion', get_string('lockoncoursecompletion', 'certificate'));
            $mform->setDefault('lockoncoursecompletion', 0);
            $mform->addHelpButton('lockoncoursecompletion', 'lockoncoursecompletion', 'certificate');
        }

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'coursechaining', get_string('coursechaining', 'certificate'));

        $this->linkedcourses = certificate_get_linked_courses($this->instance);

        $formgroup = array();
        $formgroup[] =& $mform->createElement('static', 'linkedcourselabel', 'Linked course', get_string('linkedcourse', 'certificate').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
        $formgroup[] =& $mform->createElement('static', 'linkedcoursemandatory', 'Mandatory', get_string('mandatoryreq', 'certificate').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
        $formgroup[] =& $mform->createElement('static', 'linkedcourserole', 'Role', get_string('rolereq', 'certificate'));
        $mform->addGroup($formgroup, 'courselabel', get_string('coursedependencies', 'certificate'), array(' '), false);
        $mform->addHelpButton('courselabel', 'chaining', 'certificate');

/// The linked course portion goes here, but is forced in in the 'definition_after_data' function so that we can get any elements added in the form and not overwrite them with what's in the database.

        $mform->addElement('submit', 'addcourse', get_string('addcourselabel', 'certificate'),
                           array('title' => get_string('addcoursetitle', 'certificate')));
        $mform->registerNoSubmitButton('addcourse');

        // Text Options
        $mform->addElement('header', 'textoptions', get_string('printoptions', 'certificate'));

        $modules = certificate_get_mods();
        $dateoptions = certificate_get_date_options() + $modules;
        $mform->addElement('select', 'printdate', get_string('printdate', 'certificate'), $dateoptions);
        $mform->setDefault('printdate', 'N');
        $mform->addHelpButton('printdate', 'printdate', 'certificate');

        $dateformatoptions = array( 1 => 'January 1, 2000', 2 => 'January 1st, 2000', 3 => '1 January 2000',
            4 => 'January 2000', 5 => get_string('userdateformat', 'certificate'));
        $mform->addElement('select', 'datefmt', get_string('datefmt', 'certificate'), $dateformatoptions);
        $mform->setDefault('datefmt', 0);
        $mform->addHelpButton('datefmt', 'datefmt', 'certificate');

        $gradeoptions = certificate_get_grade_options() + $modules;
        $mform->addElement('select', 'printgrade', get_string('printgrade', 'certificate'),$gradeoptions);
        $mform->setDefault('printgrade', 0);
        $mform->addHelpButton('printgrade', 'printgrade', 'certificate');

        $gradeformatoptions = array( 1 => get_string('gradepercent', 'certificate'), 2 => get_string('gradepoints', 'certificate'),
            3 => get_string('gradeletter', 'certificate'));
        $mform->addElement('select', 'gradefmt', get_string('gradefmt', 'certificate'), $gradeformatoptions);
        $mform->setDefault('gradefmt', 0);
        $mform->addHelpButton('gradefmt', 'gradefmt', 'certificate');

        $outcomeoptions = certificate_get_outcomes();
        $mform->addElement('select', 'printoutcome', get_string('printoutcome', 'certificate'),$outcomeoptions);
        $mform->setDefault('printoutcome', 0);
        $mform->addHelpButton('printoutcome', 'printoutcome', 'certificate');

        $mform->addElement('text', 'printhours', get_string('printhours', 'certificate'), array('size'=>'5', 'maxlength' => '255'));
        $mform->setType('printhours', PARAM_TEXT);
        $mform->addHelpButton('printhours', 'printhours', 'certificate');

        $mform->addElement('select', 'printteacher', get_string('printteacher', 'certificate'), $ynoptions);
        $mform->setDefault('printteacher', 0);
        $mform->addHelpButton('printteacher', 'printteacher', 'certificate');

        $mform->addElement('select', 'printcode', get_string('printcode', 'certificate'), $ynoptions);
        $mform->setDefault('printcode', 0);
        $mform->addHelpButton('printcode', 'printcode', 'certificate');

        $mform->addElement('select', 'printqrcode', get_string('printqrcode', 'certificate'), $ynoptions);
        $mform->setDefault('printqrcode', 0);
        $mform->addHelpButton('printqrcode', 'printqrcode', 'certificate');

        $mform->addElement('textarea', 'customtext', get_string('customtext', 'certificate'), array('cols'=>'40', 'rows'=>'4', 'wrap'=>'virtual'));
        $mform->setType('customtext', PARAM_RAW);
        $mform->addHelpButton('customtext', 'customtext', 'certificate');

        // Design Options
        $mform->addElement('header', 'designoptions', get_string('designoptions', 'certificate'));
        $mform->addElement('select', 'certificatetype', get_string('certificatetype', 'certificate'), certificate_types());
        $mform->setDefault('certificatetype', 'A4_non_embedded');
        $mform->addHelpButton('certificatetype', 'certificatetype', 'certificate');

        $orientation = array( 'L' => get_string('landscape', 'certificate'), 'P' => get_string('portrait', 'certificate'));
        $mform->addElement('select', 'orientation', get_string('orientation', 'certificate'), $orientation);
        $mform->setDefault('orientation', 'landscape');
        $mform->addHelpButton('orientation', 'orientation', 'certificate');

        /*
        $mform->addElement('select', 'borderstyle', get_string('borderstyle', 'certificate'), certificate_get_images(CERT_IMAGE_BORDER));
        $mform->setDefault('borderstyle', '0');
        $mform->addHelpButton('borderstyle', 'borderstyle', 'certificate');
        */

        /*
        $printframe = array( 0 => get_string('no'), 1 => get_string('borderblack', 'certificate'), 2 => get_string('borderbrown', 'certificate'),
            3 => get_string('borderblue', 'certificate'), 4 => get_string('bordergreen', 'certificate'));
        $mform->addElement('select', 'bordercolor', get_string('bordercolor', 'certificate'), $printframe);
        $mform->setDefault('bordercolor', '0');
        $mform->addHelpButton('bordercolor', 'bordercolor', 'certificate');
        */

        $group = array();
        $group[] = $mform->createElement('filepicker', 'printborders', get_string('printborders', 'certificate'), array('courseid' => $COURSE->id, 'accepted_types' => '.jpg'));
        $group[] = $mform->createElement('checkbox', 'clearprintborders', '', get_string('clearprintwmark', 'certificate'));
        $mform->addGroup($group, 'printbordersgroup', get_string('printborders', 'certificate'), '', array(''), false);

        $group = array();
        $group[] = $mform->createElement('filepicker', 'printwmark', get_string('printwmark', 'certificate'), array('courseid' => $COURSE->id, 'accepted_types' => '.jpg'));
        $group[] = $mform->createElement('checkbox', 'clearprintwmark', '', get_string('clearprintwmark', 'certificate'));
        $mform->addGroup($group, 'printwmarkgroup', get_string('printwmark', 'certificate'), '', array(''), false);

        $group = array();
        $group[] = $mform->createElement('filepicker', 'printsignature', get_string('printsignature', 'certificate'), array('courseid' => $COURSE->id, 'accepted_types' => '.jpg'));
        $group[] = $mform->createElement('checkbox', 'clearprintsignature', '', get_string('clearprintsignature', 'certificate'));
        $mform->addGroup($group, 'printsignaturegroup', get_string('printsignature', 'certificate'), '', array(''), false);

        $group = array();
        $group[] = $mform->createElement('filepicker', 'printseal', get_string('printseal', 'certificate'), array('courseid' => $COURSE->id, 'accepted_types' => '.xml'));
        $group[] = $mform->createElement('checkbox', 'clearprintseal', '', get_string('clearprintseal', 'certificate'));
        $mform->addGroup($group, 'printsealgroup', get_string('printseal', 'certificate'), '', array(''), false);

//-------------------------------------------------------------------------------
        // this needs groupspecifichtml block installed for providing group addressed content

        $mform->addElement('header', 'specialgroupoptions', get_string('specialgroupoptions', 'certificate'));

        $mform->addElement('checkbox', 'propagategroups', get_string('propagategroups', 'certificate'));
        if (!empty($config->defaultpropagategroups)) {
            $mform->setDefault('propagategroups', 1);
        }
        $mform->addHelpButton('propagategroups', 'propagategroups', 'certificate');

        if ($COURSE->groupmode != NOGROUPS && is_dir($CFG->dirroot.'/blocks/groupspecifichtml')) {
            $groupspecificoptions = certificate_get_groupspecific_block_instances();
            $mform->addElement('select', 'groupspecificcontent', get_string('groupspecificcontent', 'certificate'),$groupspecificoptions);
            $mform->setDefault('groupspecificcontent', 0);
            $mform->addHelpButton('groupspecificcontent', 'groupspecificcontent', 'certificate');
        }

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();

    }

    /**
     *
     */
    function set_data($defaults) {

        // Saves draft customization image files into definitive filearea.
        $instancefiles = array('printborders', 'printwmark', 'printseal', 'printsignature');

        // Extract print options and feed print defaults
        $printconfig = unserialize(@$defaults->printconfig);

        foreach($instancefiles as $if){
            $draftitemid = file_get_submitted_draft_itemid($if);
            $maxbytes = -1;
            $maxfiles = 1;
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_certificate', $if, 0, array('subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => $maxfiles));
            $groupname = $if.'group';
            $defaults->$groupname = array($if => $draftitemid);
        }

        parent::set_data($defaults);
    }

    /**
     * Some basic validation
     *
     * @param $data
     * @param $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check that the required time entered is valid
        if ((!is_number($data['requiredtime']) || $data['requiredtime'] < 0)) {
            $errors['requiredtime'] = get_string('requiredtimenotvalid', 'certificate');
        }

        return $errors;
    }


/**
 * Add the linked activities portion only after the entire form has been created. That way,
 * we can act on previous added values that haven't been committed to the database.
 * Check for an 'addlink' button. If the linked activities fields are all full, add an empty one.
 */
    function definition_after_data() {
        global $COURSE;

        // Start process core datas (conditions, etc.)..
        parent::definition_after_data();

        /// This gets called more than once, and there's no way to tell which time this is, so set a
        /// variable to make it as called so we only do this processing once.
        if (!empty($this->def_after_data_done)) {
            return;
        }
        $this->def_after_data_done = true;

        $mform    =& $this->_form;
        $fdata = $mform->getSubmitValues();

    /// Get the existing linked activities from the database, unless this form has resubmitted itself, in
    /// which case they will be in the form already.
        $linkids = array();
        $linkgrade = array();
        $linkentry = array();
        $courselinkids = array();
        $courselinkmandatory = array();
        $courselinkentry = array();
        $courselinkrole = array();
        
        if (empty($fdata)) {
            if ($linkedcourses = certificate_get_linked_courses($this->instance)){
                foreach ($linkedcourses as $cidx => $linkedcourse) {
                    $courselinkids[$cidx] = $linkedcourse->courseid;
                    $courselinkmandatory[$cidx] = $linkedcourse->mandatory;
                    $courselinkrole[$cidx] = $linkedcourse->roletobegiven;
                    $courselinkentry[$cidx] = $linkedcourse->id;
                }
            }
        } else {
            foreach ($fdata['courselinkid'] as $cidx => $linkid) {
                $courselinkids[$cidx] = $linkid;
                $courselinkrole[$cidx] = @$fdata['courselinkrole'][$idx];
                $courselinkmandatory[$cidx] = @$fdata['courselinkmandatory'][$idx]; // checkbox may not emit any value
            }
        }

        $i = 1;
        foreach ($courselinkids as $cidx => $linkid) {
            $formgroup = array();
            $formgroup[] =& $mform->createElement('select', 'courselinkid['.$cidx.']', '', $this->linkablecourses);
            $mform->setDefault('courselinkid['.$cidx.']', $linkid);
            $formgroup[] =& $mform->createElement('checkbox', 'courselinkmandatory['.$cidx.']');
            $mform->setDefault('courselinkmandatory['.$cidx.']', $courselinkmandatory[$cidx]);
            $formgroup[] =& $mform->createElement('select', 'courselinkrole['.$cidx.']', '', $this->assignableroles);
            $mform->setDefault('courselinkrole['.$cidx.']', $courselinkrole[$cidx]);

            $group =& $mform->createElement('group', 'courselab'.$cidx, $i, $formgroup, array(' '), false);
            $mform->insertElementBefore($group, 'addcourse');
            if (!empty($courselinkentry[$cidx])) {
                $mform->addElement('hidden', 'courselinkentry['.$cidx.']', $courselinkentry[$cidx]);
            }
            $i++;
        }

        // add a blank pod marked as -n
        $numlcourses = count($courselinkids);
        $formgroup = array();
        $formgroup[] =& $mform->createElement('select', 'courselinkid['.$numlcourses.']', '', $this->linkablecourses);
        $mform->setDefault('courselinkid['.$numlcourses.']', 0);
        $formgroup[] =& $mform->createElement('checkbox', 'courselinkmandatory['.$numlcourses.']');
        $mform->setDefault('courselinkmandatory['.$numlcourses.']', '');
        $formgroup[] =& $mform->createElement('select', 'courselinkrole['.$numlcourses.']', '', $this->assignableroles);
        $mform->setDefault('courselinkrole['.$numlcourses.']', max(array_keys($this->assignableroles))); // for security, do not preassign too high level role
        $group =& $mform->createElement('group', 'courselab'.$numlcourses, ($numlcourses+1), $formgroup, array(' '), false);
        $mform->insertElementBefore($group, 'addcourse');
    }

    // here the certificate will add is own extra rule to achieve itself.
    function add_completion_rules() {
        global $DB;

        $mform =& $this->_form;

        $group = array();
        $group[] =& $mform->createElement('checkbox', 'completiondelivered', '', get_string('completiondelivered', 'certificate'));
        $mform->setType('completiondelivered', PARAM_INT);
        $mform->addGroup($group, 'completiondeliveredgroup', get_string('completiondeliveredgroup', 'certificate'), array(' '), false);

        return array('completiondeliveredgroup');
   }

    function completion_rule_enabled($data) {
        return true;
    }

    function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        // $default_values['completiondelivered'] = @$default_values['completiondelivered'];
    }
}