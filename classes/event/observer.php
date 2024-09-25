<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Event observer.
 *
 * @package     local_notifycoursecomplete
 * @copyright   2023 Leon Stringer <leon.stringer@ntlworld.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_notifycoursecomplete\event;

/**
 * Event observer.
 *
 * @package     local_notifycoursecomplete
 * @copyright   2023 Leon Stringer <leon.stringer@ntlworld.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * When a participant completes a course determine if anyone should be
     * notified and queue a notification if so.
     * @param \core\event\course_completed $event Event triggered by
     * participant completing course.
     */
    public static function course_completed(\core\event\course_completed $event) {
        global $USER, $DB;

        $student = \core_user::get_user($event->relateduserid);
        $course = get_course($event->courseid);
        $context = \context_course::instance($course->id);

        $record = new \stdClass();
        $record->courseid = $course->id;
        $record->timecreated = time();

        $teachers = get_enrolled_users($context,
                    'local/notifycoursecomplete:receivenotification', 0, 'u.*',
                    null, 0, 0, true);

        $a = [
            'coursename' => get_course_display_name_for_list($course),
            'courselink' => (string) new \moodle_url('/course/view.php',
                            ['id' => $course->id]),
            'studentname' => fullname($student),
            'studentlink' => (string) new \moodle_url('/user/view.php',
                             ['id' => $student->id, 'course' => $course->id]),
        ];

        $stringman = get_string_manager();
        $separategroups = ($course->groupmode == SEPARATEGROUPS);

        foreach ($teachers as $teacher) {

            // As groups_user_groups_visible() compares the target user with
            // the current $USER we must populate that global, stashing and
            // restoring the value before and after the call.
            $olduser = $USER;
            $USER = $teacher;

            // If the course does not have Group mode: Separate groups, or the
            // recipient has accessallgroups, or the recipient is in the same
            // group as the student then send message.
            if (!$separategroups
                    || has_capability('moodle/site:accessallgroups', $context, $teacher)
                    || groups_user_groups_visible($course, $student->id)) {
                $record->useridto = $teacher->id;
                $record->subject = $stringman->get_string('coursecompleted',
                            'local_notifycoursecomplete',
                            null, $teacher->lang);
                $record->fullmessagehtml = $stringman->get_string(
                            'coursecompletedmessage',
                            'local_notifycoursecomplete',
                            $a, $teacher->lang);
                $DB->insert_record('local_notifycoursecomplete', $record);
            }

            $USER = $olduser;
        }
    }
}
