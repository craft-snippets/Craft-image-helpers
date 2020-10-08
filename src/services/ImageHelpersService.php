<?php
/**
 * Image helpers plugin for Craft CMS 3.x
 *
 * Image helpers
 *
 * @link      http://craftsnippets.com
 * @copyright Copyright (c) 2020 Piotr Pogorzelski
 */

namespace craftsnippets\imagehelpers\services;

use craftsnippets\imagehelpers\ImageHelpers;

use Craft;
use craft\base\Component;
use aelvan\imager\Imager;
use craft\helpers\Template;
use craft\helpers\Html;


/**
 * @author    Piotr Pogorzelski
 * @package   ImageHelpers
 * @since     1.0.0
 */
class ImageHelpersService extends Component
{


    private function serverSupportsWebp(){

        $extension = mb_strtolower(Craft::$app->getConfig()->getGeneral()->imageDriver);

        if ($extension === 'gd') {
            $imageDriver = 'gd';
        } else if ($extension === 'imagick') {
            $imageDriver = 'imagick';
        } else { // autodetect
            $imageDriver = Craft::$app->images->getIsGd() ? 'gd' : 'imagick';
        }

        if ($imageDriver === 'gd' && \function_exists('imagewebp')) {
            return true;
        }

        if ($imageDriver === 'imagick' && (\count(\Imagick::queryFormats('WEBP')) > 0)) {
            return true;
        }

        return false;

    }

    private function canTransformImager($image){
        $settings = ImageHelpers::$plugin->getSettings(); 
        // imager has problems with svg
        if($image->getMimeType() == 'image/svg+xml' && $settings->useImagerForSvg == false){
            return false;
        }
        if($settings->useImager == false){
            return false;
        }
        return true;
    }

    private function getTransformUrl($image, $transformSettings){
        
        // imager settings kept in transform settings
        $imager_settings = [];
        if(isset($transformSettings['filenamePattern'])){
            $imager_settings['filenamePattern'] = $transformSettings['filenamePattern'];
        }

        // remove not-standard settings
        unset($transformSettings['useWebp']);
        unset($transformSettings['filenamePattern']);


        // choose transform method
        $settings = ImageHelpers::$plugin->getSettings(); 
        if(!empty($transformSettings)){
            if(Craft::$app->getPlugins()->isPluginEnabled('imager') && $this->canTransformImager($image)){
                $url = \aelvan\imager\Imager::$plugin->imager->transformImage($image, $transformSettings, [], $imager_settings);
            }elseif(Craft::$app->getPlugins()->isPluginEnabled('imager-x') && $this->canTransformImager($image)){
                $url = \spacecatninja\imagerx\Imagerx::$plugin->imagerx->transformImage($image, $transformSettings, [], $imager_settings);
            }else{
                $url = $image->getUrl($transformSettings);
            }
        // if no transform settings, show image directly without transform
        }else{
            $url = $image->url;
        }
        return $url;
    }


    public function getPlaceholderUrl($transform)
    {
        if(isset($transform['width']) || isset($transform['height'])){

            // if only width or height provided, create square
            if(!isset($transform['width'])){
                $transform['width'] = $transform['height'];
            }
            if(!isset($transform['height'])){
                $transform['height'] = $transform['width'];
            }        
            $html = 'data:image/svg+xml;charset=utf-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="'.$transform['width'].'" height="'.$transform['height'].'"/>');
            return Template::raw($html);
        }
    }

    public function getPlaceholder($transform){
        $settings = ImageHelpers::$plugin->getSettings(); 
        $src = $this->getPlaceholderUrl($transform);
        $html = Html::tag('img', '', [
                        'src' => $src,
                        'class' => $settings['placeholderClass'],
        ]);
        return Template::raw($html); 
    }



    public function getPicture($image = null, $sources = [], $attributes)
    {

        $settings = ImageHelpers::$plugin->getSettings(); 
        $html_string = '';

        if(is_null($image) && $settings->usePlaceholders){
            $html_string .= $this->getPlaceholderSourcesMarkup($sources, $attributes);
        }elseif(!is_null($image)){
            $html_string .= $this->getSourcesMarkup($image, $sources, $attributes);
        }

        // return picture
        if(!empty($html_string)){
            $picture = Html::tag('picture', $html_string); 
            return Template::raw($picture);
        }

    }

    protected function canAddWebpSource($image, $transform){

        $settings = ImageHelpers::$plugin->getSettings(); 

        // if image is webp already and we dont want to transform it into other format
        if($image->getMimeType() == 'image/webp' && !isset($transform['format'])){
            return false;
        }

        // if we want only web transform
        if(isset($transform['format']) && $transform['format'] == 'webp'){
            return false;
        }

        // if we explictly state in trsnaform settings that we dont want to transform it
        if(isset($transform['useWebp']) && $transform['useWebp'] == false){
            return false;
        }

        // if global settings allow it, server supports webp and iamge is not svg 
        if($settings->useWebp && $this->serverSupportsWebp() && $image->getMimeType() != 'image/svg+xml'){
            return true;
        }

        return false;
    }


    protected function getSourcesMarkup($image, $sources = [], $attributes){

        $settings = ImageHelpers::$plugin->getSettings(); 
        $html_string = '';

        foreach($sources as $source){

            // if we dont want source empty
            if(!is_null($source['transform'])){
                // webp version
                if($this->canAddWebpSource($image, $source['transform'])){
                    $settings_webp = array_merge($source['transform'], ['format' => 'webp']);
                    $html_string .= "\n";
                    $html_string .= Html::tag('source', '', [
                        'media' => $source['media'] ?? null,
                        'srcset' => $this->getTransformUrl($image, $settings_webp),
                        'type' => 'image/webp',
                    ]);
                }

                // regular version
                $html_string .= "\n";
                $html_string .= Html::tag('source', '', [
                    'media' => $source['media'] ?? null,
                    'srcset' => $this->getTransformUrl($image, $source['transform']),
                    'type' => isset($source['transform']['format']) ? 'image/'.$source['transform']['format'] : $image->getMimeType(),
                ]); 
            // if empty source
            }else{
                $html_string .= "\n";
                $html_string .= Html::tag('source', '', [
                    'media' => $source['media'] ?? null,
                    'srcset' => $this->getPlaceholderUrl(['width' => 0, 'height' => 0]),
                ]);                     
            }
        }

        // fallback - first transform
        $fallback_transform = reset($sources)['transform'];

        if(!is_null($fallback_transform)){
            $fallback_src = $this->getTransformUrl($image, $fallback_transform);
         }else{
            $fallback_src = $this->getPlaceholderUrl(['width' => 0, 'height' => 0]);
         }

        $fallback_attributes = [
            'src' => $fallback_src,
        ];

        // add provided attributes
        if(!is_null($attributes)){
            $fallback_attributes = array_merge($fallback_attributes, $attributes);
        }
        $html_string .= "\n";
        $html_string .= Html::tag('img', '', $fallback_attributes); 
        $html_string .= "\n";

        return $html_string;

    }


    protected function getPlaceholderSourcesMarkup($sources = [], $attributes){

            $settings = ImageHelpers::$plugin->getSettings(); 
            $html_string = '';

            // sources
            foreach($sources as $source){
                $html_string .= "\n";
                $html_string .= Html::tag('source', '', [
                    'media' => $source['media'] ?? null,
                    'srcset' => $this->getPlaceholderUrl($source['transform']),
                ]);
            }

            // fallback - first transform
            $fallback_transform = reset($sources)['transform'];

            // add provided attributes
            $fallback_attributes = [
                'srcset' => $this->getPlaceholderUrl($fallback_transform),
            ];
            if(!is_null($attributes)){
                $fallback_attributes = array_merge($fallback_attributes, $attributes);
            }
            // add placeholder class
            if(isset($fallback_attributes['class'])){
                if(is_array($fallback_attributes['class'])){
                    $fallback_attributes['class'][] = $settings['placeholderClass'];
                }elseif(is_string($fallback_attributes['class'])){
                    $fallback_attributes['class'] = [$fallback_attributes['class'], $settings['placeholderClass']];
                }
            }else{
                $fallback_attributes['class'] = $settings['placeholderClass'];
            }

            $html_string .= "\n";
            $html_string .= Html::tag('img', '', $fallback_attributes);
            $html_string .= "\n";

            return Template::raw($html_string);
    }
}
