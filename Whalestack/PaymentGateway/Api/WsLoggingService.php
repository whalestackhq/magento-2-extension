<?php

namespace Whalestack\PaymentGateway\Api;

/**
 * Class WsLoggingService
 *
 * A logging service
 */
class WsLoggingService {

    /**
     * Writes to a log file and prepends current time stamp
     *
     * @param $data
     * @param $title
     */
    public static function write($data, $title = null) {

        $path = $_SERVER["DOCUMENT_ROOT"] . '/app/code/Whalestack/PaymentGateway/Log/';
        $logFile = $path . 'Whalestack.log';

        if (!file_exists($logFile) && !is_writable($logFile)) {
            return;
        }

        $type = file_exists($logFile) ? 'a' : 'w';
        $file = fopen($logFile, $type);

//        if (!is_null($title)) {
            fputs($file, date('r', time()) . ' ====' . $title . '====' . PHP_EOL);
//        }

        fputs($file, date('r', time()) . ' ' . print_r($data, true) . PHP_EOL);
        fclose($file);

    }

}