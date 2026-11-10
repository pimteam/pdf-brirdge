<?php

namespace PdfBridge\Mpdf\PsrLogAwareTrait;

use PdfBridge\Psr\Log\LoggerInterface;
trait PsrLogAwareTrait
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
