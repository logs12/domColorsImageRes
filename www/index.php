<!DOCTYPE html>
<html lang="ru" >
<head>
    <title>Page</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php
    include_once('classes/Controller.php');
    // массив с разрешениями картинок для ресайза
    $size = array(
        '1' => array(
            'width' => '600',
            'height' => '300',
        ),
        '2' => array(
            'width' => '400',
            'height' => '500',
        )
    );
    // количество доминантных цветов
    $cntColors = 1;
    // путь к файлику с исходными урлами картинок
    $filePathIn = "urlImage.txt";

    $obj = new Controller($filePathIn,$size,$cntColors);
    $arrPath = $obj->getFile();
    list($arrColors,$hexAr) = $obj->dominantColors($arrPath);
    //echo "<xmp>hexAr = ";print_r($hexAr);echo "</xmp>";
    //echo "<xmp>arrColors = ";print_r($arrColors);echo "</xmp>";
    $arRes = $obj->delRepeatImageColors($arrColors,$hexAr);
    //echo "<xmp>arRes = ";var_dump($arRes);echo "</xmp>";
    $arrResult = $obj->recizeImg($arRes);
    //echo "<xmp>";print_r($arrResult);echo "</xmp>";
    echo json_encode($arrResult);

    ?>
</body>
</html>