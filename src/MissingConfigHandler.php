<?php
/**
 * @author: dep
 * Date: 09.02.16
 */

namespace demmonico\config;

use demmonico\config\base\MissingEvent;
use demmonico\config\base\MissingHandler;


class MissingConfigHandler extends MissingHandler
{
    public static function parseAttributes(MissingEvent $event)
    {
        /**
         * @var MissingConfigEvent $event
         */
        return [
            $event->key => [
                'key'   => $event->key,
                'value' => $event->value,
                'type'  => $event->type,
            ]
        ];
    }

}