<?php

namespace Coinqvest\PaymentGateway\Api;

/**
 * Class CQLoggingService
 *
 * A logging service
 */
class CQLoggingService {

    /**
     * Writes to a log file and prepends current time stamp
     *
     * @param $data
     * @param $title
     */
    public static function write($data, $title = null) {

        $path = $_SERVER["DOCUMENT_ROOT"] . '/app/code/Coinqvest/PaymentGateway/Log/';
        $logFile = $path . 'Coinqvest.log';

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