<?php

define('O_FORMAT_PNG', 'PNG');
define('O_FORMAT_SVG', 'SVG');

// include 2D barcode class (search for installation path)
function makepath() {
    $array = func_get_args();
    return implode(DIRECTORY_SEPARATOR, $array);
}

function check_for_gd() {
    if (extension_loaded('gd') && function_exists('gd_info')) {
        // echo "PHP GD library is enabled in your system.";
        return true;
    } else {
        echo "PHP GD library is not enabled on your system.";
        die();
    }
}

require_once( makepath(dirname(__FILE__), 'tcpdf_barcodes_1d.php'));
require_once( makepath(dirname(__FILE__), 'tcpdf_barcodes_2d.php') );
require_once( makepath(dirname(__FILE__), 'tools', 'dir.php') );

// constants
if (array_key_exists('APPLICATION_ID', $_SERVER)) {
    # Google cloud!
    $tmp_dir = 'gs://com-larcity-static/temp/';
} else {
    $tmp_dir = makepath(dirname(__FILE__), 'temp') . DIRECTORY_SEPARATOR;
}
define('TMP_DIR', $tmp_dir);

// defaults to FANCYQR
$requested_code_type = empty($_GET['type']) ? 'FANCYQR' : strtoupper(trim(filter_var($_GET['type'], FILTER_SANITIZE_STRING)));

$data = [];
$text_data = '';

function set($key, $value) {
    $data[$key] = $value;
}

function json($key) {
    return empty($data[$key]) ? null : $data[$key];
}

function respond($data, $contentType = 'text/plain') {
    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
    $http_response_code = empty($data['status']) ? 200 : intval($data['status']);
    $status_message = empty($data['status_msg']) ? 'OK' : $data['status_msg'];
    header($protocol . ' ' . $http_response_code . ' ' . $status_message);
    header("Content-type: {$contentType}");
    if (preg_match("/image\/*", $contentType)) {
        // do not print anything - handled by library classes
        echo $data;
    } else {
        switch ($contentType) {
            case 'text/plain':
            case 'text/csv':
                echo $data;
                break;

            default:
                echo json_encode($data);
                break;
        }
    }
}

try {
    // size parameters 
    $size = empty($_GET['size']) ? 5 : filter_var($_GET['size'], FILTER_SANITIZE_NUMBER_INT);
    /*
      $w = empty($_GET['w']) ? 5 : filter_var($_GET['w'], FILTER_SANITIZE_NUMBER_INT);
      $h = empty($_GET['h']) ? 2 : filter_var($_GET['h'], FILTER_SANITIZE_NUMBER_INT);
     */
    if (!empty($_GET['rgb'])) {
        $rgb_string = str_replace('|', ',', $_GET['rgb']);
        if (!preg_match("/^\d+,\d+,\d+$/", $rgb_string)) {
            $rgb_string = '0,0,0';
        }
    } else {
        $rgb_string = '0,0,0';
    }
    $rgb_array = explode(',', $rgb_string);
    $red = $rgb_array[0];
    $green = $rgb_array[1];
    $blue = $rgb_array[2];
    $payload = empty($_GET['data']) ? 'https://larcity.com' : $_GET['data'];
    // figure out format for output image
    $format = empty($_GET['format']) ? O_FORMAT_PNG : strtoupper($_GET['format']);
    if (!in_array(trim($format), [O_FORMAT_PNG, O_FORMAT_SVG])) {
        $format = O_FORMAT_PNG;
    }

    // Check for GD library
    check_for_gd();

    switch ($requested_code_type) {
        case 'PDF417':
            $w = min(10, max(5, $size));
            $h = 2 / 5 * $w;
            $barcodeObj = new TCPDF2DBarcode($payload, $requested_code_type);
            $barcodeObj->getBarcodePNG($w, $h, [$red, $green, $blue]);
            break;

        case 'DATAMATRIX':
            $w = min(10, max(5, $size));
            $h = $w;
            // set the barcode content and type
            $barcodeobj = new TCPDF2DBarcode($payload, $requested_code_type);
            if ($format === O_FORMAT_SVG):
                /** @TODO support figuring out color names or validating for them in GET `color` * */
                $barcodeobj->getBarcodeSVG($w, $h, 'black');
            else:
                $barcodeobj->getBarcodePNG($w, $h, [$red, $green, $blue]);
            endif;
            break;

        case 'QR':
            /** Handle raw-looking QR * */
            $w = min(10, max(5, $size));
            $h = $w;
            // set the barcode content and type
            $barcodeobj = new TCPDF2DBarcode($payload, 'QRCODE,H');
            if ($format === O_FORMAT_SVG):
                /** @TODO support figuring out color names or validating for them in GET `color` * */
                $barcodeobj->getBarcodeSVG($w, $h, 'black');
            else:
                $barcodeobj->getBarcodePNG($w, $h, [$red, $green, $blue]);
            endif;
            break;

        case 'BARCODE':
        case '2D':
            /* Size is requirement
            $w = min(10, max(5, $size));
            $h = $w;
            */
            $barcodeobj = new TCPDFBarcode($payload, 'C128');
            if ($format === O_FORMAT_SVG):
                // for now, force height
                $h = 30;
                /** @TODO support figuring out color names or validating for them in GET `color` * */
                $barcodeobj->getBarcodeSVG(2, 30, 'black');
            else:
                $barcodeobj->getBarcodePNG(2, 30, [$red, $green, $blue]);
            endif;
            break;

        case 'FANCYQR':
            /** @TODO handle fancy QR code creation - output in PNG ONLY! */
            $imagePadding = 12; // @IMPORTANT - make an even number
            $std_wh = max([$w, $h]);
            $w = $size;
            $h = $size;
            $barcodeObj = new TCPDF2DBarcode($payload, 'QRCODE,H');
            $imgData = $barcodeObj->getBarcodePNGData($w, $h, [$red, $green, $blue]);
            $imgSize = getimagesizefromstring($imgData);
            // get png-8 image 
            $img = imagecreatetruecolor($imgSize[0] + $imagePadding, $imgSize[1] + $imagePadding);
            imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
            // write temp file
            $temp_filename = TMP_DIR . date('YmdHis') . '__TEMP.PNG';
            file_put_contents($temp_filename, $imgData);
            $imgsrc = imagecreatefrompng($temp_filename);
            // convert to png-24
            $margin = -1 * ($imagePadding / 2);
            imagecopy($img, $imgsrc, 0, 0, $margin, $margin, $imgSize[0] + $imagePadding, $imgSize[1] + $imagePadding);
            // config
            $matrixPointSize = min(max((int) $size, 1), 50);
            // blur factor
            $i = 50;
            $i = min(20, $matrixPointSize);
            $i = max(5, $i);
            while ($i--)
                imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
            imagefilter($img, IMG_FILTER_CONTRAST, -100);
            // set white to transparent
            imagecolortransparent($img, imagecolorallocate($img, 255, 255, 255));
            /** alpha => 0 - 127 from opaque to transparent * */
            $alpha = 0;
            imagefilter($img, IMG_FILTER_COLORIZE, $red, $green, $blue, $alpha);
            $final_imgfile = TMP_DIR . date("Ymd_His_") . '__' . rand(1000, 2000) . '.png';
            // show image
            header('Content-Type: image/png');
            /*
              // save file
              imagepng($img, $final_imgfile);
              echo file_get_contents($final_imgfile);
             */
            // do not save file
            imagepng($img, NULL);
            imagedestroy($img);
            unlink($temp_filename);
            break;
    }
} catch (Exception $ex) {
    set('status', $ex->getCode());
    set('status_msg', $ex->getMessage());
    respond($data);
}

