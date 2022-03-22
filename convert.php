<?php

const SEARCH_KEY = "19.00DOOR";
const SEARCH_KEY_COLUMN = 3;
const QTY_COLUMN = 1;
const LPX_COLUMN = 8;
const LPY_COLUMN = 7;
const LPZ = 19;
const DIRECTION_COLUMN = 13;
const OUTPUT_DIR = "output";
const INPUT_DIR = "input";

function getXYDirection($pts_file) {
    $ret = [];
    $row = 1;
    echo "---- File Reading ($pts_file) ----" . "<br/>";
    if (($handle = fopen($pts_file, "r")) !== FALSE) {
        while (($one_row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $num = count($one_row);
            if ($num >= SEARCH_KEY_COLUMN - 1) {
                if (strpos($one_row[SEARCH_KEY_COLUMN - 1], SEARCH_KEY) !== FALSE) {   // found
                    $qty = $one_row[QTY_COLUMN - 1];
                    $lpx = $one_row[LPX_COLUMN - 1];
                    $lpy = $one_row[LPY_COLUMN - 1];
                    $direction = $one_row[DIRECTION_COLUMN - 1];
                    $direction_slug = slugify($one_row[DIRECTION_COLUMN - 1]);
                    $ret[] = [
                        "qty" => trim($qty), 
                        "lpx" => trim($lpx), 
                        "lpy" => trim($lpy), 
                        "direction" => trim($direction), 
                        "slug" => trim($direction_slug)
                    ];
                }
            }
        }
        fclose($handle);
        echo "---- Get Info from ($pts_file) ----" . "<br/>";
    }
    return $ret;
}

function replaceXY($bpp_file, $data) {
    $search_words = ["LPX", "LPY", "LPZ"];
    
    $path_parts = pathinfo($bpp_file);
    $filename = $path_parts['filename'];
    echo "-------- File Reading ($bpp_file) --------- " . "<br/>";
    if (($handle = fopen($bpp_file, "r")) === FALSE) {
        echo "Cannot open file ($bpp_file)\n";
        return false;
    }

    foreach($data as $one_data) {
        fseek($handle,0);
        $x = $one_data['lpx'];
        $y = $one_data['lpy'];
        $qty = $one_data['qty'];
        $slug = $one_data['slug'];

        $pattern_x = "/PAN=LPX\|(\d)+\|\|/";
        $replace_x = "PAN=LPX|$x||";
        
        $pattern_y = "/PAN=LPY\|(\d)+\|\|/";
        $replace_y = "PAN=LPY|$y||";
        
        $qty = $one_data["qty"];
        $slug = $one_data["slug"];
        $replace_words = [$x, $y, LPZ];

        $new_file = OUTPUT_DIR . DIRECTORY_SEPARATOR . sprintf("%s_%d_%d_%d_%s.bpp", $filename, $x, $y, $qty, $slug);
        if(!file_exists(dirname($new_file)))
            mkdir(dirname($new_file), 0777, true);
        $write_handle = fopen($new_file, "w");
        while (($one_row = fgets($handle)) !== FALSE) {
            $replace_x_row = preg_replace($pattern_x, $replace_x, $one_row);
            $replace_y_row = preg_replace($pattern_y, $replace_y, $replace_x_row);
            // Write replace string to our opened file.
            if (fwrite($write_handle, $replace_y_row) === FALSE) {
                echo "Cannot write to file ($filename)\n";
            }
        }
        fclose($write_handle);    
        echo "---------------- File Writing ($new_file) -----------------" . "<br/>";
    }
    
    fclose($handle);
}

function slugify($string){
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
}

function getBPPfiles($in_dir) {
    $bpp_files = glob($in_dir . DIRECTORY_SEPARATOR . "*.bpp");
    return $bpp_files;
}

function getPTSfile($in_dir) {
    $pts_files = glob($in_dir . DIRECTORY_SEPARATOR . "*.PTS");
    return $pts_files;
}

function start() {
    $pts_files = getPTSfile(INPUT_DIR);
    $bpp_files = getBPPfiles(INPUT_DIR);
    foreach($pts_files as $pts_file) {
        $data = getXYDirection($pts_file);
        foreach($bpp_files as $bpp_file)
            replaceXY($bpp_file, $data);
    }
}

start();