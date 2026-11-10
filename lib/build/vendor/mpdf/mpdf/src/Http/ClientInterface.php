<?php

namespace PdfBridge\Mpdf\Http;

use PdfBridge\Psr\Http\Message\RequestInterface;
interface ClientInterface
{
    public function sendRequest(RequestInterface $request);
}
