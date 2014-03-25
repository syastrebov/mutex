<?php

/**
 *
 * thread 1:
 *
 * lock A
 * lock B
 *
 * thread 2:
 *
 * lock B
 * lock A
 *
 */

include __DIR__ . '/../../../config/bootstrap.php';

use Mutex\Service\Mutex;
use Mutex\Service\Profiler;

try {
    $mutex = new Mutex('127.0.0.1', 7007);

    $mutex->get('A', false);
    $mutex->get('B', false);

    if (isset($argv[1])) {
        if ($mutex->acquire('A')) {
            Profiler::debugMessage('acquired A');
            sleep(10);

            if ($mutex->acquire('B')) {
                sleep(10);

                Profiler::debugMessage('acquired B');
                $mutex->release('B');
            }
            $mutex->release('A');
        }
        Profiler::debugMessage('end');
    } else {
        if ($mutex->acquire('B')) {
            Profiler::debugMessage('acquired B');
            sleep(10);

            if ($mutex->acquire('A')) {
                sleep(10);

                Profiler::debugMessage('acquired A');
                $mutex->release('A');
            }
            $mutex->release('B');
        }
        Profiler::debugMessage('end');
    }
} catch (Exception $e) {
    Profiler::debugMessage($e->getMessage());
}