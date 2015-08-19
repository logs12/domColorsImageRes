<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 19.08.2015
 * Time: 10:49
 */
class Controller{

    /**
     * @var array - массив с разрешениями картинок для ресайза
     */
    private $size = array();

    /**
     * @var - количество цветов
     */
    private $cntColors;

    /**
     * @var - путь к текстовому айлу с картинками
     */
    private $filePathIn;

    /**
     * @var string - путь для сохранения несжатых файлов
     */
    private $filePathUncompressed = 'files/uncompressed/';
    /**
     * @var string - путь для сохранения сжатых файлов
     */
    private $filePathCompressed = 'files/compressed/';

    public function __construct($filePathIn,$size = array(), $cntColors){
        $this->size = $size;
        $this->cntColors = $cntColors;
        $this->filePathIn = $filePathIn;


    }

    /**
     * @return array
     */
    public function getFile(){
        $f = fopen($this->filePathIn, "r");
        // Читать построчно до конца файла
        $cnt = 0;
        while (!feof($f)) {
            ++$cnt;
            // Создать массив с запятой-разделителем
            $arrM = explode(",",fgets($f));
            // Записать ссылки (получить данные из массива)
        }
        fclose($f);
        //echo "<xmp>";var_dump($arrM);echo "</xmp>";

        foreach($arrM as $val)
        {
            $path_parts = pathinfo($val);
            $path = $this->filePathUncompressed.$path_parts['basename'];
            file_put_contents($path, file_get_contents($val));
            $arrPath[] = $path;
        }
        return $arrPath;
    }

    /**
     * поиск доминантных цветов
     * @param $arrPath - пути к картинкам
     * @return array - $arrColors - пути к картинкам, $hexAr - массив со всеми цветами картинк в hex
     */
    public function dominantColors($arrPath)
    {
        // подключение ф-й поиска доминантных цветов
        require_once('colors.php');
        $cnt = 0;
        foreach($arrPath as $val) {
            ++$cnt;
            $colors = getDominantColors($val, $this->cntColors);
            if ($colors === false)
                echo 'Ошибка';
            else {
                //echo "<xmp>colors = ";print_r($colors);echo "</xmp>";
                foreach($colors as $color){
                    $hex = $this->rgb2hex($color);
                }
                $hexAr[] = $hex;
                $arrColors[$cnt]['color'] = $hex;
                $arrColors[$cnt]['path'] = $val;
            }
        }
        return array($arrColors,$hexAr);
    }

    /**
     * метод конвертинга из rgb в hex
     * @param $rgb - массив rgb цветов
     * @return string - значение цвета в hex
     */
    private function rgb2hex($rgb){
        $hex = "#";
        $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

        return $hex; // returns the hex value including the number sign (#)

    }

    /**
     * @param $arrColors - пути к картинкам
     * @param $hexAr - массив со всеми цветами картинк в hex
     * @return array - отфильтрованный массив по доминантным цветам
     */
    public function delRepeatImageColors($arrColors,$hexAr){
        $arrOneHex = array_count_values($hexAr);
        echo "<xmp>arrRes = ";print_r($arrOneHex);echo "</xmp>";
        foreach ($arrOneHex as $key=>$val) {

            if ($val > 1)
            {

                foreach($arrColors as $k=>$v)
                {
                    if ($key == $v['color']) {}//$cnt++;//unlink($v['path']);
                    else {
                        $path_parts = pathinfo($v['path']);
                        $arrColors1[]=array(
                            'color' => $v['color'],
                            'path' => $v['path'],
                            'name' => $path_parts['filename'],
                            'ext' => $path_parts['extension']
                        );
                    }
                }
                foreach($arrColors as $k=>$v)
                {
                    if ($key == $v['color']) {
                        $path_parts = pathinfo($v['path']);
                        $arrColors2[]=array(
                            'color' => $v['color'],
                            'path' => $v['path'],
                            'name' => $path_parts['filename'],
                            'ext' => $path_parts['extension']
                        );
                        break;
                    }
                }
            }
        }
        foreach($arrColors as $k=>$v)
        {
            $path_parts = pathinfo($v['path']);
            $arrColors3[]=array(
                'color' => $v['color'],
                'path' => $v['path'],
                'name' => $path_parts['filename'],
                'ext' => $path_parts['extension']
            );
        }
        if (isset($arrColors1) && isset($arrColors2))
            $arrRes = array_merge($arrColors1,$arrColors2);
        else if(isset($arrColors1)) $arrRes = $arrColors1;
        else if(isset($arrColors2)) $arrRes = $arrColors2;
        else $arrRes = $arrColors3;
        return $arrRes;
    }

    /**
     * @param $arrRes - отфильтрованный массив по доминантным цветам
     * @return array - массив с путями к отресайзенным иображениям
     */
    public function recizeImg($arrRes)
    {
        // подключение класса ресайзинга
        require_once('imageWorker.class.php');
        foreach ($arrRes as $key=>$val)
        {
            $objIm = new ImageWorker($val['name'],$val['path'],$this->filePathCompressed,'');
            foreach($this->size as $k=>$v)
            {
               // echo "<xmp>";var_dump($k); echo "</xmp>";
                //echo "<xmp>";var_dump($v);echo "</xmp>";
                $arrResult[] = $objIm->resizeImage($v['width'],$v['height']);

            }
        }
        return $arrResult;
    }


}