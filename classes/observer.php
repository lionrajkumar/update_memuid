<?php
/**
 * Created by Integrass.
 * User: Rajkumar
 * Date: 7/15/2019
 * Time: 6:49 PM
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/user/profile/lib.php");

class local_update_memuid_observer {
    public static function update(\core\event\base $event)
    {
        global $USER;
    }
}