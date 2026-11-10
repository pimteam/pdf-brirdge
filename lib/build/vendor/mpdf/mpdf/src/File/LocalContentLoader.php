<?php

namespace PdfBridge\Mpdf\File;

class LocalContentLoader implements \PdfBridge\Mpdf\File\LocalContentLoaderInterface
{
    public function load($path)
    {
        return file_get_contents($path);
    }
}
