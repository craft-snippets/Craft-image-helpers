<?php
/**
 * Image helpers plugin for Craft CMS 3.x
 *
 * Image helpers
 *
 * @link      http://craftsnippets.com
 * @copyright Copyright (c) 2020 Piotr Pogorzelski
 */

namespace craftsnippets\imagehelpers\variables;

use craftsnippets\imagehelpers\ImageHelpers;

use Craft;
use craftsnippets\imagehelpers\services\ImageHelpersService as ImageHelpersServiceService;

use craft\helpers\Template;


/**
 * @author    Piotr Pogorzelski
 * @package   ImageHelpers
 * @since     1.0.0
 */
class ImageHelpersVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null $optional
     * @return string
     */
    public function picture($image, $transform = [], $attributes = null)
    {
        $sources = [
            [
                'transform' => is_null($transform) ? [] : $transform,
            ]
        ];
        $service = new ImageHelpersServiceService;
        return $service->getPicture($image, $sources, $attributes);
    }

    public function pictureMedia($image, $transforms, $common_setings = null, $attributes = null){
        $sources = [];
        foreach ($transforms as $media => $transform) {
            $sources[] = Array(
                'media' => $media,
                'transform' => !is_null($common_setings) ? array_merge($common_setings, $transform) : $transform,
            );
        }
        $service = new ImageHelpersServiceService;
        return $service->getPicture($image, $sources, $attributes);
    }

    public function pictureMax($image, $transforms, $common_setings = null, $attributes = null){
        ksort($transforms);
        $sources = [];
        foreach ($transforms as $media => $transform) {
            $sources[] = Array(
                'media' => '(max-width: ' . $media . 'px)',
                'transform' => !is_null($common_setings) ? array_merge($common_setings, $transform) : $transform,
            );
        }
        $service = new ImageHelpersServiceService;
        return $service->getPicture($image, $sources, $attributes);
    }

    public function pictureMin($image, $transforms, $common_setings = null, $attributes = null){
        krsort($transforms);
        $sources = [];
        foreach ($transforms as $media => $transform) {
            $sources[] = Array(
                'media' => '(min-width: ' . $media . 'px)',
                'transform' => !is_null($common_setings) ? array_merge($common_setings, $transform) : $transform,
            );
        }
        $service = new ImageHelpersServiceService;
        return $service->getPicture($image, $sources, $attributes);
    }

    public function placeholder(array $transform){
        $service = new ImageHelpersServiceService;
        return $service->getPlaceholder($transform);
    }

}
