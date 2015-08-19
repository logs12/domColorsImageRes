<?php
/**
 * Класс нормальзующий картинку по заданному разрешению
 */
class ImageWorker {
    public $fileName = ''; // имя изменяемого файла
    public $dir = '';  // расположения набора картинок
    public $dirUpload = ''; // папка для выгрузки (должна быть создана)
    public $fileDirUploadUri = ''; // папка для выгрузки (должна быть создана)

    function __construct($filename,$fileDir, $fileDirUpload,$fileDirUploadUri)
    {
        $this->fileName = $filename;
        $this->dirUpload = $fileDirUpload;
        $this->dir = $fileDir;
        $this->fileDirUploadUri = $fileDirUploadUri;
    }

    /**
     * @param $new_width
     * @param $new_height
     * @return string - path to save image
     */
    public function resizeImage($new_width,$new_height)
    {

        if(!list($width, $height) = getimagesize($this->dir)) return "Unsupported picture type!";
        $type = strtolower(substr(strrchr($this->dir,"."),1));
        if($type == 'jpeg') $type = 'jpg';
        switch ($type)
        {
            case 'jpg':
            {
                // возвращает идентификатор изображения, представляющий черное изображение заданного размера
                $image_p = imagecreatetruecolor($new_width, $new_height) or die('Невозможно инициализировать GD поток');
                //var_dump($this->dir);
                $image = imagecreatefromjpeg($this->dir);
                //var_dump($image);
                // Копирование и изменение размера изображения с ресемплированием
                imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                $newFilePath = $this->dirUpload.$this->fileName.'-'.$new_width.'x'.$new_height.'.'.$type;
                $newFilePathUri = $this->fileDirUploadUri.$this->fileName.'-'.$new_width.'x'.$new_height.'.'.$type;
                //var_dump($newFilePath);
                // Выводит изображение в браузер или пишет в файл
                imagejpeg($image_p,$newFilePath);
                break;
            }

            case 'png':
            {
                $image_p = imagecreatetruecolor($new_width, $new_height) or die('Невозможно инициализировать GD поток');
                $image = imagecreatefrompng($this->dir);
                imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                $newFilePath = $this->dirUpload.$this->fileName.'-'.$new_width.'x'.$new_height.'.'.$type;
                $newFilePathUri = $this->fileDirUploadUri.$this->fileName.'-'.$new_width.'x'.$new_height.'.'.$type;
                imagepng($image_p,$newFilePath);
                break;
            }
            case 'gif':
            {
                $images = new Imagick($this->dir);
                $images = $images->coalesceImages();

                //var_dump($images);
                //ресайзим каждый кадр в цикле
                do {
                    $images->scaleImage($new_width, $new_height);
                } while ($images->nextImage());

                //оптимизируем слои
                $images->optimizeImageLayers();

                //освобождаем память
                $images = $images->deconstructImages();

                $newFilePath = $this->dirUpload.$this->fileName.'-'.$new_width.'x'.$new_height.'.'.$type;
                $newFilePathUri = $this->fileDirUploadUri.$this->fileName.'-'.$new_width.'x'.$new_height.'.'.$type;
                $images->writeImages($newFilePath, true);

                break;
            }
        }
        return $newFilePath;
    }
}