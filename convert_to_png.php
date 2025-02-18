<?php

$svgFile = '/data/web/virtuals/193972/virtual/www/datatest/tmp/output.svg';


if (!file_exists($svgFile)) {
    die('SVG soubor nenalezen.');
}


try {
    $imagick = new Imagick();
    $imagick->readImage($svgFile);
    $imagick->setImageFormat('png');
    $imagick->setImageBackgroundColor(new ImagickPixel('white'));


    $outputPngFile = '/data/web/virtuals/193972/virtual/www/datatest/tmp/output.png';
    $imagick->writeImage($outputPngFile);


    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="output.png"');
    readfile($outputPngFile);


    unlink($outputPngFile);
    exit();
} catch (Exception $e) {
    die('Chyba při generování PNG: ' . $e->getMessage());
}
?>
