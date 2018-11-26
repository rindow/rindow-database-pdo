<?php
namespace Rindow\Database\Pdo\Driver;

interface Driver
{
    public function errorCodeMapping($errorCode,$errorMessage);
    public function getName();
    public function getPlatform();
    public function getCursorOptions($resultListType,$resultListConcurrency,$resultListHoldability);
}
