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
 * Send course completion notifications task.
 *
 * @package     local_notifycoursecomplete
 * @copyright   2023 Leon Stringer <leon.stringer@ntlworld.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_notifycoursecomplete\task;

class send_notifications extends \core\task\scheduled_task {
    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendnotificationstask', 'local_notifycoursecomplete');
    }

    /**
     * Run task for sending course completion notifications.
     */
    public function execute() {
        global $DB;

        $records = $DB->get_records('local_notifycoursecomplete');
        $eventdata = new \core\message\message();
        $eventdata->component         = 'local_notifycoursecomplete';
        $eventdata->name              = 'teacherstudentcomplete';
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->notification      = 1;
        $eventdata->fullmessageformat = FORMAT_HTML;

        foreach ($records as $record) {
            $eventdata->courseid = $record->courseid;
            $eventdata->userto = $record->useridto;
            $eventdata->subject = $record->subject;
            $messageplaintext = html_to_text($record->fullmessagehtml);
            $eventdata->fullmessage = $messageplaintext;
            $eventdata->fullmessagehtml = $record->fullmessagehtml;
            $eventdata->smallmessage = $messageplaintext;
            message_send($eventdata);

            $DB->delete_records('local_notifycoursecomplete', array('id' => $record->id));
        }
    }
}
