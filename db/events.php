<?php
/**
 * Created by Integrass.
 * User: Rajkumar
 * Date: 05/09/2022
 * Time: 3:10 PM
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array (
        'eventname' => '\core\event\user_loggedin',
        'callback'  => 'local_update_memuid_observer::update',
        'includefile' => '/local/update_memuid/classes/observer.php'
    )
);
