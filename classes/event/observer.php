<?php
// FIXME Boilerplate
//

namespace local_notifycoursecomplete\event;

class observer {
    public static function course_completed(\core\event\base $event) {
	error_reporting(E_ALL);
    error_log(__FILE__ . ':' . __FUNCTION__ . " **1");
    }
}
