<?php

const SEARCH_KEY = "19.00DOOR";

const SEARCH_KEY_COLUMN = 3;
const QTY_COLUMN = 1;
const LPX_COLUMN = 8;
const LPY_COLUMN = 7;
const DIRECTION_COLUMN = 13;

function getXYDirection($pts_file) {
    $ret = [];
    $row = 1;
    echo "-------- File Reading ($pts_file)--------- " . "<br/>";
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
        echo "-------- Get Info from ($pts_file)---------" . "<br/>";
    }
    return $ret;
}

function replaceXY($bpp_file, $x, $y, $qty, $direction) {
    $search_words = ["LPX", "LPY"];
    $replace_words = [$x, $y];

    $path_parts = pathinfo($bpp_file);
    $filename = $path_parts['filename'];

    $new_file = sprintf("%s_%d_%d_%d_%s.bpp", $filename, $x, $y, $qty, $direction);
    $write_handle = fopen($new_file, "w");
    echo "-------- File Reading ($bpp_file)--------- " . "<br/>";
    if (($handle = fopen($bpp_file, "r")) !== FALSE) {
        while (($one_row = fgets($handle)) !== FALSE) {
            $replace_row = str_replace($search_words, $replace_words, $one_row);
            // Write $replace_row to our opened file.
            if (fwrite($write_handle, $replace_row) === FALSE) {
                echo "Cannot write to file ($filename)\n";
            }
        }
        fclose($handle);
    }
    fclose($write_handle);    
    echo "-------- File Creating ($new_file)---------" . "<br/>";
}

function slugify($string){
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
}

$data = getXYDirection("Jean_repaired.PTS");

replaceXY("Monaco.bpp", $data[0]["lpx"], $data[0]["lpy"], $data[0]["qty"], $data[0]["slug"]);
