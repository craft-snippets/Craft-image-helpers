<?php
/**
 * Image helpers plugin for Craft CMS 3.x
 *
 * Image helpers
 *
 * @link      http://craftsnippets.com
 * @copyright Copyright (c) 2020 Piotr Pogorzelski
 */

namespace craftsnippets\imagehelpers;

use craftsnippets\imagehelpers\services\ImageHelpersService as ImageHelpersServiceService;
use craftsnippets\imagehelpers\variables\ImageHelpersVariable;
use craftsnippets\imagehelpers\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

/**
 * Class ImageHelpers
 *
 * @author    Piotr Pogorzelski
 * @package   ImageHelpers
 * @since     1.0.0
 *
 * @property  ImageHelpersServiceService $imageHelpersService
 */
class ImageHelpers extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var ImageHelpers
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('imageHelpers', ImageHelpersVariable::class);
            }
        );

        Craft::info(
            Craft::t(
                'image-helpers',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }


}
