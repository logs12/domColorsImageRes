<?php
/**
 * Created by PhpStorm.
 * User: Vasily
 * Date: 18.08.2015
 * Time: 17:29
 */

// Функция, которая определяет "расстояние" между объектами
function getDistance($arr1, $arr2, $l) {
    // Определяется "расстояние машины такси" — так быстрее, но для более точного результата желательно использовать евклидому метрику
    $s = 0;
    for($i = 0; $i < $l; ++$i)
        $s += $arr1[$i] > $arr2[$i] ? ($arr1[$i] - $arr2[$i]) : ($arr2[$i] - $arr1[$i]);
    return $s;
}

/*
Сама функция, которая определяет доминирующие цвета
Значения, передаваемые функции:
	$url — путь к картинке на сервере
	$amount — количество цветов, которые надо определить
	$sort — сортировать ли цвета по убыванию встречаемости на картинке
	$maxPreSize — максимальный размер уменьшаемой картинки (подробнее чуть дальше)
	$epselon — минимальное суммарное отклонение, необходимое для выхода из цикла
*/
function getDominantColors($url, $amount = 1, $sort = true, $maxPreSize = 50, $epselon = 1) {
    // Определяем, существует ли указанный файл. Если нет, то выдаём ошибку.
    if(!file_exists($url))
        return false;
    // Определяем размер картинки, если это картинка.
    $size = getimagesize($url);
    if($size === false)
        return false;
    $width = $size[0];
    $height = $size[1];
    // Определяем формат изображения из заголовка файла и проверяем, есть ли функция, которая может это изображение открыть. Если нет, значит файл — не картинка, и выдаём ошибку.
    $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
    if($format == 'x-ms-bmp')
        $format = 'wbmp';
    $func = 'imagecreatefrom'.$format;
    if(!function_exists($func))
        return false;
    $bitmap = @$func($url);
    if(!$bitmap)
        return false;

    // Здесь мы уменьшаем исходную картинку, чтобы иметь дело не со всеми пикселями, а только с некоторыми. Это значительно увеличивает скорость алгоритма. Чем меньше размер — тем меньше точность.
    $newW = $width > $maxPreSize ? $maxPreSize : $width;
    $newH = $height > $maxPreSize ? $maxPreSize : $height;
    $bitmapNew = imagecreatetruecolor($newW, $newH);
    // Для большей точности надо использовать функцию imageCopyResambled, но она тратит очень много времени, поэтому используется быстрая и грубая функция imageCopyResized.
    imageCopyResized($bitmapNew, $bitmap, 0, 0, 0, 0, $newW, $newH, $width, $height);
    // Заносим цвета  всех пикселей в один массив
    $pixelsAmount = $newW * $newH;
    $pixels = Array();
    for($i = 0; $i < $newW; ++$i)
        for($j = 0; $j < $newH; ++$j) {
            $rgb = imagecolorat($bitmapNew, $i, $j);
            $pixels[] = Array(
                ($rgb >> 16) & 0xFF,
                ($rgb >> 8) & 0xFF,
                $rgb & 0xFF
            );
        }
    imagedestroy($bitmapNew);

    // Выбираем случайные пиксели для установки центроидов в них
    $clusters = Array();
    $pixelsChosen = Array();
    for($i = 0; $i < $amount; ++$i) {
        // Желательно не ставить несколько центроидов в одну точку
        do {
            $id = rand(0, $pixelsAmount - 1);
        } while(in_array($id, $pixelsChosen));
        $pixelsChosen[] = $id;
        $clusters[] = $pixels[$id];
    }

    $clustersPixels = Array();
    $clustersAmounts = Array();
    // Начинаем цикл
    do {
        // Обнуляем хранилище пикселей, принадлежащих текущему центроиду и их счётчик
        for($i = 0; $i < $amount; ++$i) {
            $clustersPixels[$i] = Array();
            $clustersAmounts[$i] = 0;
        }

        // Проходимся по всем пикселям и определяем ближайший к ним центроид
        for($i = 0; $i < $pixelsAmount; ++$i) {
            $distMin = -1;
            $id = 0;
            for($j = 0; $j < $amount; ++$j) {
                $dist = getDistance($pixels[$i], $clusters[$j], 3);
                if($distMin == -1 or $dist < $distMin) {
                    $distMin = $dist;
                    $id = $j;
                }
            }
            $clustersPixels[$id][] = $i;
            ++$clustersAmounts[$id];
        }

        // Перемещаем центроид в центр масс пикселей, принадлежащих ему, заодно вычисляем, насколько он сместился
        $diff = 0;
        for($i = 0; $i < $amount; ++$i) {
            if($clustersAmounts[$i] > 0) {
                $old = $clusters[$i];
                for($k = 0; $k < 3; ++$k) {
                    $clusters[$i][$k] = 0;
                    for($j = 0; $j < $clustersAmounts[$i]; ++$j)
                        $clusters[$i][$k] += $pixels[$clustersPixels[$i][$j]][$k];
                    $clusters[$i][$k] /= $clustersAmounts[$i];
                }
                // Будем сравнивать максимальное отклонение
                $dist = getDistance($old, $clusters[$i], 3);
                $diff = $diff > $dist ? $diff : $dist;
            }
        }
    } while($diff >= $epselon);

    // Сортировка получившихся кластеров (не помню как метод называется)
    if($sort and $amount > 1)
        for($i = 1; $i < $amount; ++$i)
            for($j = $i; $j >= 1 and $clustersAmounts[$j] > $clustersAmounts[$j - 1]; --$j) {
                $t = $clustersAmounts[$j - 1];
                $clustersAmounts[$j - 1] = $clustersAmounts[$j];
                $clustersAmounts[$j] = $t;

                $t = $clusters[$j - 1];
                $clusters[$j - 1] = $clusters[$j];
                $clusters[$j] = $t;
            }

    // Значение цвета — величина целая, поэтому её надо округлить
    for($i = 0; $i < $amount; ++$i)
        for($j = 0; $j < 3; ++$j)
            $clusters[$i][$j] = floor($clusters[$i][$j]);

    // Очищаем память
    imagedestroy($bitmap);

    // Возвращаем массив, который содержит доминирующие цвета. Каждый цвет — массив, состоящий из 3-х элементов — красный, зелёный и синий канал цвета.
    return $clusters;
}
?>