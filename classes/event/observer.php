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

class observer {
    public static function course_completed(\core\event\base $event) {
        global $USER;

        $student = \core_user::get_user($event->relateduserid);
        $course = get_course($event->courseid);
        $context = \context_course::instance($course->id);

        $messagesubject = get_string('coursecompleted', 'local_notifycoursecomplete');
        $a = [
            'coursename' => get_course_display_name_for_list($course),
            'courselink' => (string) new \moodle_url('/course/view.php', ['id' => $course->id]),
            'studentname' => fullname($student),
            'studentlink' => (string) new \moodle_url('/user/view.php', ['id' => $event->userid, 'course' => $event->courseid]),
        ];

        $messagebody = get_string('coursecompletedmessage', 'local_notifycoursecomplete', $a);
        $messageplaintext = html_to_text($messagebody);

        $eventdata = new \core\message\message();
        $eventdata->courseid          = $course->id;
        $eventdata->component         = 'local_notifycoursecomplete';
        $eventdata->name              = 'teacherstudentcomplete';
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->notification      = 1;
        $eventdata->subject           = $messagesubject;
        $eventdata->fullmessage       = $messageplaintext;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = $messagebody;
        $eventdata->smallmessage      = $messageplaintext;
        $teachers = get_enrolled_users($context,
                    'local/notifycoursecomplete:receivenotification', 0, 'u.*',
                    null, 0, 0, true);

        $separategroups = ($course->groupmode == SEPARATEGROUPS);

        foreach ($teachers as $teacher) {
            $eventdata->userto = $teacher->id;

            // As groups_user_groups_visible() compares the target user with
            // the current $USER we must populate that global, stashing and
            // restoring the value before and after the call.
            $olduser = $USER;
            $USER = $teacher;

            // If the course does not have Group mode: Separate groups, or the
            // recipient has accessallgroups, or the recipient is in the same
            // group as the student then send message.
            if (!$separategroups || has_capability('moodle/site:accessallgroups', $context, $teacher) || groups_user_groups_visible($course, $student->id)) {
                message_send($eventdata);
            }

            $USER = $olduser;
        }
    }
}
