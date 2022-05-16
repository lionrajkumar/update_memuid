<?php
/**
 * Created by Integrass.
 * User: Rajkumar
 * Date: 7/15/2019
 * Time: 6:49 PM
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/user/profile/lib.php");
require_once("$CFG->dirroot/blocks/safesport/api_functions.php");

class local_update_memuid_observer {
    public static function update(\core\event\base $event)
    {
        global $USER, $CFG;
        $userInfoData = profile_user_record($USER->id);

        $fields = ["email","firstname","lastname","confirmation_number","official_number"];
        $verifyData=[$USER->email, $USER->firstname, $USER->lastname, $userInfoData->confnos, $userInfoData->cepno];
        $searchUserData = (object)array_combine($fields,$verifyData);
        self::createLogs('UserFindInfo', 'User:' . json_encode($searchUserData));
        $userFromMemReg = fetchUserInfoFromMemReg($searchUserData);
        $userFromMemReg->profile_field_dob=date_format(date_create_from_format('m/d/Y', $userFromMemReg->profile_field_dob), 'Y-m-d');
        self::createLogs('UserLookupMyHqResp', 'User:' . json_encode($userFromMemReg));
        if (empty($userFromMemReg)) {
            return false;
        }

        if($userFromMemReg->profile_field_memberuniqueid) {
            $data = json_encode(array("username" => $CFG->safesportadmin, "password" => $CFG->safesportadmin_pswd, "force" => "true"));
            $token = getAdminAuthToken($data);
            $adminToken = $token->token;

            $findUserData = array("LastName" => $USER->lastname, "DOB" => $userFromMemReg->profile_field_dob);
            $userIdsArr = getUserWithLookup($findUserData, $adminToken);
            if(count($userIdsArr)==1){
                self::createLogs("ActiveAcc",$userIdsArr[0]);
                $idLookupRes = self::getSafesportUserInfo($userIdsArr[0],$adminToken);
                if($idLookupRes->Username != $userFromMemReg->profile_field_memberuniqueid){
                    $idLookupRes->Username = $userFromMemReg->profile_field_memberuniqueid;
                    $idLookupRes->CustomFields->String3 = $userFromMemReg->profile_field_memberuniqueid;
                    self::updateSafesportUserInfo($userIdsArr[0], $adminToken, json_encode($idLookupRes));
                }
            }
        }else {
            self::createLogs('profile_field_memberuniqueid', 'profile_field_memberuniqueid is empty');
        }
        return true;
    }

    public static function getSafesportUserInfo($userId,$adminToken){
        $curlHeadersOptions['CURLOPT_USERAGENT'] = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
        $curlHeadersOptions['CURLOPT_HTTPHEADER']=getAdminHeaders($adminToken);

        $curl=new curl();
        $url = getSafesportAdminDomains() . "/api/v1/users/" . $userId; //user info lookup with UserID
        $idLookupResp = $curl->get($url, [], $curlHeadersOptions);
        self::createLogs("getLookupResp",$idLookupResp);
        return json_decode($idLookupResp);
    }

    public static function updateSafesportUserInfo($userId, $adminToken, $idLookupRes){
        $curlHeadersOptions['CURLOPT_USERAGENT'] = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
        $curlHeadersOptions['CURLOPT_HTTPHEADER']=getAdminHeaders($adminToken);

        $curl=new curl();
        $url = getSafesportAdminDomains() . "/api/v1/users/" . $userId; //user info lookup with UserID
        $idLookupResp = $curl->put($url, $idLookupRes, $curlHeadersOptions);
        self::createLogs("updateUserResp",$idLookupResp);
        return json_decode($idLookupResp);
    }


    public static function createLogs($type, $newLogData){
        global $CFG, $USER;

        $userName = $USER->id.' '.fullname($USER, true);
        $usahfiledir = $CFG->dataroot.'/memuid_ssv/';
        if (!is_dir($usahfiledir)) {
            if (!mkdir($usahfiledir, 0777, true)) {
                throw new file_exception('storedfilecannotcreatefiledirs');
            }
        }
        $filename = date('Y-m-d')."-update_memuid_ssv.TXT";
        $logData = '['.$type.'] '.date('Y-m-d H:i:s ').$userName.' '.$newLogData.PHP_EOL;

        $fp = fopen($usahfiledir . '/' . $filename, 'a+');
        fwrite($fp, $logData);
        fclose($fp);
        chmod($usahfiledir.'/'.$filename, 0777);
    }
}