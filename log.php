<?php
    function _log($data, $filename, $type, $origin) {
        $log_file = "/var/www/ipromo/data/www/seminar.icgr.ru/snm/log/$filename";
        $output = "$type (".date('m/d/Y h:i:s a', time())."),".PHP_EOL;

        $output .= "  Origin: ".PHP_EOL;
        $origin_cnt = 0;
        foreach ($origin as $line) {
            if ($origin_cnt >= 2) {
                break;
            }
            if ($origin_cnt > 0) {
                $output .= "    function = ".$line["function"];
                $output .= json_encode($line["args"]).", line = ".$line["line"];
                $output .= ", file = ". $line["file"].PHP_EOL;
            } else {
                $output .= "    function = ".$line["function"];
                $output .= ", line = ".$line["line"];
                $output .= ", file = ". $line["file"].PHP_EOL;
            }
            $origin_cnt++;
        }
        $output .= PHP_EOL;

        $output .= "  Data: ".PHP_EOL;
        $output .= "        ".str_replace(PHP_EOL, PHP_EOL."        ", print_r($data, true)).PHP_EOL;
        $output .= PHP_EOL;
        file_put_contents($log_file, $output, FILE_APPEND | LOCK_EX);
    }
    function log_info($data, $filename = "all.log", $type = "INFO") {
        _log($data, $filename, $type, debug_backtrace());
    }
    function log_error($data, $filename = "all.log", $type = "ERROR") {
        _log($data, $filename, $type, debug_backtrace());
    }
?>
