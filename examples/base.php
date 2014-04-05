<?php

include __DIR__ . '/../test/bootstrap.php';

use ErlMutex\Service\Mutex;
use ErlMutex\Service\Profiler;
use ErlMutex\Exception\Exception;
use ErlMutex\Service\Storage\ProfilerStorageDummy;

try {
    $outputDir = __DIR__ . '/profiler_output';
    if (!is_dir($outputDir)) {
        @mkdir($outputDir) or die('Не удалось создать директорию ' . $outputDir);
    }

    $mutex = new Mutex('127.0.0.1', 7007);
    $mutex
        ->establishConnection()
        ->setProfiler(new Profiler(__FILE__))
        ->getProfiler()
        ->setStorage(ProfilerStorageDummy::getInstance())
        ->setMapOutputLocation($outputDir);

    if (!$mutex->isAlive()) {
        throw new Exception('Не удалось подключиться к сервису');
    }

    $mutex->get('key1', false);
    if ($mutex->acquire()) {
        sleep(10);
        $mutex->release();
    }

    $mutex->get('key1', false);
    $mutex->get('key2', false);

    if ($mutex->acquire('key1')) {
        if ($mutex->acquire('key2')) {
            $mutex->release();
        }
        $mutex->release();
    }

    $mutex->getProfiler()->generateHtmlMapOutput();

} catch (Exception $e) {
    Profiler::debugMessage('Error:' . $e->getMessage());
}


