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

include __DIR__ . '/../src/Mutex/Mutex.php';
include __DIR__ . '/../src/Base/Base.php';

use Mutex\Mutex;

$mutex = new Mutex('127.0.0.1', 7007);

$mutex->get('A', false);
$mutex->get('B', false);

if (isset($argv[1])) {
    if ($mutex->acquire('A')) {
        Base::msg('acquired A');
        sleep(10);

        if ($mutex->acquire('B')) {
            sleep(10);

            Base::msg('acquired B');
            $mutex->release('B');
        }
        $mutex->release('A');
    }
    Base::msg('end');
} else {
    if ($mutex->acquire('B')) {
        Base::msg('acquired B');
        sleep(10);

        if ($mutex->acquire('A')) {
            sleep(10);

            Base::msg('acquired A');
            $mutex->release('A');
        }
        $mutex->release('B');
    }
    Base::msg('end');
}