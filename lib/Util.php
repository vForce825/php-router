<?php
namespace lib;
class Util {
    static function nullCheck($data, $nullCheckOpt) {
        if (array_values($nullCheckOpt) == $nullCheckOpt) {
            foreach ($nullCheckOpt as $item) {
                if (empty($data->$item)) return $item;
            }
        } else {
            foreach ($nullCheckOpt as $item => $description) {
                if (empty($data->$item)) return $description;
            }
        }
        return true;
    }

    static function inputNullCheck($data, $nullCheckOpt) {
        $result = self::nullCheck($data, $nullCheckOpt);
        if ($result === true) {
            return true;
        } else {
            Response::getInstance()->send(array('error' => "{$result} cannot be null!. Stoping service.")); exit;
        }
    }
}