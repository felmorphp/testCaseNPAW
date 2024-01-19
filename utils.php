<?php

/**
 * Try get ip from server vars values, if is not possible call to external service for get ip public
 * if ip is valid in end of process return ip , else return false
 * @return false|string
 */
function getIP(){
        $ip = null;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $valid = filter_var($ip, FILTER_VALIDATE_IP);
        if($valid===false){
            //if invalid or not ip public maybe we are in localhost , call external service for get public ip
            $ip = file_get_contents('https://api.ipify.org');
        }
        $valid = filter_var($ip, FILTER_VALIDATE_IP);
        if($valid!==false){
            return $ip;
        }else{
            return false;
        }
    }