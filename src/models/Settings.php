<?php
/**
 * Image helpers plugin for Craft CMS 3.x
 *
 * Image helpers
 *
 * @link      http://craftsnippets.com
 * @copyright Copyright (c) 2020 Piotr Pogorzelski
 */

namespace craftsnippets\imagehelpers\models;

use craftsnippets\imagehelpers\ImageHelpers;

use Craft;
use craft\base\Model;

/**
 * @author    Piotr Pogorzelski
 * @package   ImageHelpers
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $useWebp = true;
    public $useImager = true;
    public $usePlaceholders = true;
    public $placeholderClass = 'is-placeholder';
    public $useImagerForSvg = false;
}
