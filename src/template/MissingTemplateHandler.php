<?php
/**
 * @author: dep
 * Date: 09.02.16
 */

namespace demmonico\template;

use demmonico\config\core\MissingEvent;
use demmonico\config\core\MissingHandler;


class MissingTemplateHandler extends MissingHandler
{
    /**
     * @inheritdoc
     */
    public static $fileStorage = 'missing_templates.php';

    /**
     * @inheritdoc
     */
    public static function parseAttributes(MissingEvent $event)
    {
        /**
         * @var MissingTemplateEvent $event
         */
        return [
            $event->key => [
                'key'   => $event->key,
                'template' => $event->template,
            ]
        ];
    }

}