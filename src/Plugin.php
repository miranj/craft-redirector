<?php
/**
 * Redirector plugin for Craft CMS
 *
 * Handles legacy URL redirects.
 *
 * @link      https://miranj.in/
 * @copyright Copyright (c) 2021 Miranj Design LLP
 */

namespace miranj\redirector;

use Craft;
use craft\base\Plugin as BasePlugin;


class Plugin extends BasePlugin
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        
        Craft::info(
            Craft::t(
                'redirector',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }
    
}
