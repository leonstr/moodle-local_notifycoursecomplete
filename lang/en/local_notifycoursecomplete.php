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
 * Plugin strings are defined here.
 *
 * @package     local_notifycoursecomplete
 * @category    string
 * @copyright   2023 Leon Stringer <leon.stringer@ntlworld.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['coursecompletedmessage'] = '<p>Student <a href="{$a->studentlink}">{$a->studentname}</a> has completed the course <a href="{$a->courselink}">{$a->coursename}</a>.</p>';
$string['coursecompletedsubject'] = 'Course completed';
$string['messageprovider:teacherstudentcomplete'] = 'Teacher\'s student completed course';
$string['notifycoursecomplete:receivenotification'] = 'Receive completion notification';
$string['pluginname'] = 'Notify teacher course completed';
$string['sendnotificationstask'] = 'Send course completion notifications to teachers';
