<?php
require_once __DIR__ . '/autoloader.php';

use SVG\SVG;
use SVG\Nodes\Texts\SVGText;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookName = $_POST['bookname'] ?? 'Název knihy';
    $name = $_POST['name'] ?? 'Jméno';
    $lastname = $_POST['lastname'] ?? 'Příjmení';
    $gender = $_POST['gender'] ?? 'Her';

    $sentence = $gender === 'Her' 
        ? 'Presented with pride and recognition of her efforts and understanding.' 
        : 'Presented with pride and recognition of his efforts and understanding.';

    $months = [
        '01' => 'January',
        '02' => 'February',
        '03' => 'March',
        '04' => 'April',
        '05' => 'May',
        '06' => 'June',
        '07' => 'July',
        '08' => 'August',
        '09' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December',
    ];
    $currentMonthYear = $months[date('m')] . ' ' . date('Y');

    $svgFile = 'test.svg';
    if (!file_exists($svgFile)) {
        die('Soubor test.svg nebyl nalezen.');
    }

    $svgDocument = SVG::fromFile($svgFile);
    $root = $svgDocument->getDocument();

    function findTextNodeById($root, $id) {
        foreach ($root->getElementsByTagName('tspan') as $node) {
            if ($node->getAttribute('id') === $id) {
                return $node;
            }
        }
        return null;
    }

    $element14 = findTextNodeById($root, 'tspan14');
    if ($element14 instanceof SVGText) {
        $element14->setText($name . ' ' . $lastname);
    }

    $element15 = findTextNodeById($root, 'tspan15');
    if ($element15 instanceof SVGText) {
        $element15->setText($bookName);
    }

    $element39 = findTextNodeById($root, 'tspan39');
    if ($element39 instanceof SVGText) {
        $element39->setText($currentMonthYear);
    }

    $element11 = findTextNodeById($root, 'tspan11');
    if ($element11 instanceof SVGText) {
        $element11->setText($sentence);
    }

    $tmpDir = '/data/web/virtuals/193972/virtual/www/datatest/tmp/';
    if (!file_exists($tmpDir)) {
        mkdir($tmpDir, 0777, true);
    }

    $updatedSvgFile = $tmpDir . 'output.svg';
    file_put_contents($updatedSvgFile, $svgDocument->toXMLString());

    $pngFile = $tmpDir . 'output.png';
    $image = $svgDocument->toRasterImage(800, 600);
    imagepng($image, $pngFile);

    echo "PNG byl vytvořen: <a href='/datatest/tmp/output.png'>Stáhnout PNG</a>";
    exit();
}
?>
