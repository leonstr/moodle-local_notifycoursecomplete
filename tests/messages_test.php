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
 * @package     local_notifycoursecomplete
 * @copyright   2023 Leon Stringer <leon.stringer@ntlworld.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_notifycoursecomplete;

/**
 * PHPUnit tests for local_notifycoursecomplete.
 */
class messages_test extends \advanced_testcase {
    /**
     * Test setup.
     */
    public function setUp(): void {
        global $CFG;

        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_activity.php');
    }

    /**
     * Check non-editing teacher (no accessallgroups) receives notification.
     */
    public function test_no_groups() {
        global $DB;

        $this->resetAfterTest();

        // Create a course, add an activity, enrol a student and a non-editing
        // teacher.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id], ['completion' => 1]);
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        // Set completion criteria.
        $criteriadata = (object) [
            'id' => $course->id,
            'criteria_activity' => [$assign->cmid => 1],
        ];
        $criterion = new \completion_criteria_activity();
        $criterion->update_config($criteriadata);

        // Set messaging preferences for student and teacher.
        set_user_preference('message_provider_local_notifycoursecomplete_teacherstudentcomplete_enabled', 'email', $teacher);
        set_user_preference('message_provider_moodle_coursecompleted_enabled', 'none', $student);

        $sink = $this->redirectEmails();

        // Mark the user to complete the criteria.
        $cmassign = get_coursemodule_from_id('assign', $assign->cmid);
        $completion = new \completion_info($course);
        $completion->update_state($cmassign, COMPLETION_COMPLETE, $student->id);

        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));

        $message = reset($messages);
        $studentname = fullname($student);
        $needle = "Student $studentname [1] has completed the course {$course->fullname} [2]";
        $this->assertStringContainsString(quoted_printable_encode($needle), $message->body);
    }

    /**
     * In a course with group mode: separate groups, check non-editing teacher
     * (no accessallgroups) receives notification for student in same group.
     */
    public function test_same_group() {
        global $DB;

        $this->resetAfterTest();

        // Create a course, add an activity, enrol a student and a non-editing
        // teacher.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        // Update the course set the groupmode SEPARATEGROUPS and forced.
        update_course((object)array('id' => $course->id, 'groupmode' => SEPARATEGROUPS, 'groupmodeforce' => true));
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id], ['completion' => 1]);
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        // Create group, add student and teacher to it.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $student->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $teacher->id));

        // Set completion criteria.
        $criteriadata = (object) [
            'id' => $course->id,
            'criteria_activity' => [$assign->cmid => 1],
        ];
        $criterion = new \completion_criteria_activity();
        $criterion->update_config($criteriadata);

        // Set messaging preferences for student and teacher.
        set_user_preference('message_provider_local_notifycoursecomplete_teacherstudentcomplete_enabled', 'email', $teacher);
        set_user_preference('message_provider_moodle_coursecompleted_enabled', 'none', $student);

        $sink = $this->redirectEmails();

        // Mark the user to complete the criteria.
        $cmassign = get_coursemodule_from_id('assign', $assign->cmid);
        $completion = new \completion_info($course);
        $completion->update_state($cmassign, COMPLETION_COMPLETE, $student->id);

        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
    }

    /**
     * In a course with group mode: separate groups, check non-editing teacher
     * (no accessallgroups) does not receive notification for student in
     * different group.
     */
    public function test_different_group() {
        global $DB;

        $this->resetAfterTest();

        // Create a course, add an activity, enrol a student and a non-editing
        // teacher.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        // Update the course set the groupmode SEPARATEGROUPS and forced.
        update_course((object)array('id' => $course->id, 'groupmode' => SEPARATEGROUPS, 'groupmodeforce' => true));
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id], ['completion' => 1]);
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        // Create first group, add student to it.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $student->id));

        // Create second group, add teacher to it.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $teacher->id));

        // Set completion criteria.
        $criteriadata = (object) [
            'id' => $course->id,
            'criteria_activity' => [$assign->cmid => 1],
        ];
        $criterion = new \completion_criteria_activity();
        $criterion->update_config($criteriadata);

        // Set messaging preferences for student and teacher.
        set_user_preference('message_provider_local_notifycoursecomplete_teacherstudentcomplete_enabled', 'email', $teacher);
        set_user_preference('message_provider_moodle_coursecompleted_enabled', 'none', $student);

        $sink = $this->redirectEmails();

        // Mark the user to complete the criteria.
        $cmassign = get_coursemodule_from_id('assign', $assign->cmid);
        $completion = new \completion_info($course);
        $completion->update_state($cmassign, COMPLETION_COMPLETE, $student->id);

        $messages = $sink->get_messages();
        $this->assertEquals(0, count($messages));
    }
}
