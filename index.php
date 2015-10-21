<?php

// include 2D barcode class (search for installation path)
function makepath() {
    $array=func_get_args();
    return implode(DIRECTORY_SEPARATOR, $array);
}

require_once( makepath(dirname(__FILE__), 'tcpdf_barcodes_2d.php') );
require_once( makepath(dirname(__FILE__), 'tools', 'dir.php') );

// constants
if(array_key_exists('APPLICATION_ID', $_SERVER)) {
    # Google cloud!
    $tmp_dir = 'gs://com-larcity-static/temp/';
} else {
    $tmp_dir = makepath(dirname(__FILE__), 'temp') . DIRECTORY_SEPARATOR;
}
define('TMP_DIR', $tmp_dir);

$requested_code_type = empty($_GET['type']) ? 'PDF417' : strtoupper(trim(filter_var($_GET['type'], FILTER_SANITIZE_STRING)));

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
        if (!preg_match("/^\d+,\d+,\d+$/", $_GET['rgb'])) {
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

    switch ($requested_code_type) {
        case 'PDF417':
            $w = min(10, max(5, $size));
            $h = 2/5 * $w;
            $barcodeObj = new TCPDF2DBarcode($payload, $requested_code_type);
            $barcodeObj->getBarcodePNG($w, $h, [$red, $green, $blue]);
            break;
        
        case 'QR':
            /** Handle raw-looking QR **/
            break;

        case 'FANCYQR':
            /** @TODO handle fancy QR code creation * */
            $imagePadding=12; // @IMPORTANT - make an even number
            $std_wh = max([$w, $h]);
            $w = $size; $h = $size;
            $barcodeObj = new TCPDF2DBarcode($payload, 'QRCODE,H');
            $imgData = $barcodeObj->getBarcodePNGData($w, $h, [$red, $green, $blue]);
            $imgSize = getimagesizefromstring($imgData);
            // get png-8 image 
            $img = imagecreatetruecolor($imgSize[0]+$imagePadding,$imgSize[1]+$imagePadding);
            imagefill($img, 0, 0, imagecolorallocate($img,255,255,255));
            // write temp file
            $temp_filename = TMP_DIR . date('YmdHis') . '__TEMP.PNG';
            file_put_contents($temp_filename, $imgData);
            $imgsrc = imagecreatefrompng($temp_filename);
            // convert to png-24
            $margin = -1 * ($imagePadding/2);
            imagecopy($img,$imgsrc,0,0,$margin,$margin,$imgSize[0]+$imagePadding,$imgSize[1]+$imagePadding);            
            // config
            $matrixPointSize = min(max((int)$size, 1), 50);
            // blur factor
            $i = 50;
            $i = min(20, $matrixPointSize);
            $i = max(5, $i);
            while($i--) 
                imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
            imagefilter($img, IMG_FILTER_CONTRAST,-100);
            // set white to transparent
            imagecolortransparent($img, imagecolorallocate($img, 255, 255, 255));
            imagefilter($img, IMG_FILTER_COLORIZE, $red,$green,$blue);
            // show image
            header('Content-Type: image/png');
            imagepng($img, null);
            imagedestroy($img);
            break;
    }
} catch (Exception $ex) {
    set('status', $ex->getCode());
    set('status_msg', $ex->getMessage());
    respond($data);
}

