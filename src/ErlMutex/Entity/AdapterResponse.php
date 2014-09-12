<?php

namespace ErlMutex\Entity;

/**
 * Ответ адаптера
 *
 * Class AdapterResponse
 * @package ErlMutex\Entity
 */
class AdapterResponse
{
    /**
     * @var mixed
     */
    private $return;

    /**
     * @var mixed
     */
    private $response;

    /**
     * @param mixed $return
     * @param mixed $response
     */
    public function __construct($return, $response)
    {

    }
}