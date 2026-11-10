<?php

namespace PdfBridge;

if (!\function_exists('PdfBridge\dd')) {
    function dd(...$args)
    {
        if (\function_exists('PdfBridge\dump')) {
            dump(...$args);
        } else {
            \var_dump(...$args);
        }
        die;
    }
}
