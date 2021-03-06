<?php
// This file is part of Moodle - http://moodle.org/
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
 * Block import_remote_course
 *
 * Display a list of courses to be imported from a remote Moodle system
 * Using a local/remote_backup_provider plugin (dependency)
 *
 * @package    block_import_remote_course
 * @copyright  Nadav Kavalerchik <nadavkav@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_import_remote_course extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_import_remote_course');
    }

    function get_content() {
        global $COURSE, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        if ($this->page->course->id == SITEID) {
            $this->context->text = "Only available in a course";
            return $this->content;
        }

        // user/index.php expect course context, so get one if page has module context.
        $currentcontext = $this->page->context->get_course_context(false);

        $this->content = '';
        if (empty($currentcontext)) {
            return $this->content;
        }

        // Get course list from system settings.
        // todo: use system-level block settings
//        if (!empty($this->config->text)) {
//            $courselist = explode(',',$this->config->text);
//        }
        $remotecourselist = get_config('import_remote_course', 'remotecourselist');
        if (empty($remotecourselist)) {
            $this->content->text = get_string('noavailablecourses', 'block_import_remote_course');
            return $this->content;
        } else {
            $courselist = explode(',', $remotecourselist);
        }

        $form = '<form action="'.$CFG->wwwroot.'/blocks/import_remote_course/import_remote_course.php" type="get">';
        $form .= get_string('choosecourse').' <select id="choosecourse" name="remotecourseid">';
        foreach ($courselist as $course) {
            list($courseid, $coursename) = explode('=', $course);
            $form .= "<option value='$courseid'>$coursename</option>";
        }
        $form .= '</select>';
        $form .= '<input type="hidden" name="destcourseid" value="'.$COURSE->id.'">';
        $form .= '<input type="hidden" name="sessionid" value="'.session_id().'">';
        $form .= '<input type="submit" value="'.get_string("restore").'">';
        $form .= '</form>';

        // If we have more then one (probably the "news forum") module in the course,
        // Display a warrening, and prevent restore.
        $coursemodulescount = count(get_fast_modinfo($COURSE->id)->cms);
        if ($coursemodulescount > 1) {
            $this->content->text = get_string('courseisnotempty', 'block_import_remote_course');
        } else {
            $this->content->text .= $form;
        }

        return $this->content;
    }

    // Block is available only on course pages.
    public function applicable_formats() {
        return array('all' => false,
                     'course-view' => true);
    }

    // Block can appear only once, in a course.
    public function instance_allow_multiple() {
          return false;
    }

    // Block has course-level config.
    // todo: migrate to site level.
    function has_config() {
        return true;
    }
}
