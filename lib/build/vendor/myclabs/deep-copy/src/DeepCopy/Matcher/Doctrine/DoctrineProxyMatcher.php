<?php

namespace PdfBridge\DeepCopy\Matcher\Doctrine;

use PdfBridge\DeepCopy\Matcher\Matcher;
use PdfBridge\Doctrine\Persistence\Proxy;
/**
 * @final
 */
class DoctrineProxyMatcher implements Matcher
{
    /**
     * Matches a Doctrine Proxy class.
     *
     * {@inheritdoc}
     */
    public function matches($object, $property)
    {
        return $object instanceof Proxy;
    }
}
