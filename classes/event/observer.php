<?php
// FIXME Boilerplate
//

namespace local_notifycoursecomplete\event;

class observer {
    public static function course_completed(\core\event\base $event) {
        $course = get_course($event->courseid);
        $context = \context_course::instance($course->id);

        $messagesubject = get_string('coursecompleted', 'local_notifycoursecomplete');
        $a = [
            'coursename' => get_course_display_name_for_list($course),
            'courselink' => (string) new \moodle_url('/course/view.php', array('id' => $course->id)),
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
        $users = get_enrolled_users($context, 'report/completion:view');

        foreach ($users as $user) {
            $eventdata->userto = $user->id;
            message_send($eventdata);
        }
    }
}
