<?php

/**
 * PHP-Erlang erl
 * Сервис блокировок для обработки критических секций
 *
 * @category erl
 * @package  erl
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/erl
 */

namespace ErlMutex\Test\Service;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
use DateTime;

/**
 * Class MapTest
 * @package ErlMutex\Test\Service
 */
class MapTest  extends \PHPUnit_Framework_TestCase
{
    /**
     * Сравнение двух моделей на идеинтичность
     */
    public function testCompareTraces()
    {
        $trace1 = new ProfilerStackModel(
            'request_1',
            md5('request_1'),
            'file_1',
            1,
            'class_1',
            'method_1',
            'key_1',
            'action_1',
            'response_1',
            new DateTime()
        );

        $trace2 = new ProfilerStackModel(
            'request_1',
            md5('request_1'),
            'file_1',
            1,
            'class_1',
            'method_1',
            'key_1',
            'action_1',
            'response_1',
            new DateTime()
        );

        $this->assertTrue($trace1->getModelHash() === $trace2->getModelHash());
    }
}