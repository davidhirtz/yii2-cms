<?php
namespace davidhirtz\yii2\cms\widgets;

use davidhirtz\yii2\cms\models\Asset;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Class AssetsView.
 * @package davidhirtz\yii2\cms\widgets
 */
class AssetsView extends Widget
{
    /**
     * @var Asset[]
     */
    public $assets;

    /**
     * @var string
     */
    public $viewFile='_assets';

    /**
     * @var array
     */
    public $viewPath;

    /**
     * @var int
     */
    public $start;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var array containing css class as key and related asset type as value.
     */
    public $breakpoints=[];

    /**
     * @var array containing asset type as key and an array of shared types as value.
     */
    public $shared=[];

    /**
     * @var array
     */
    public $options=[];

    /**
     * @var string slider class that will be applied if there is more than one item.
     */
    public $sliderClass;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if(!$this->shared && $this->breakpoints)
        {
            foreach($this->breakpoints as $breakpoint)
            {
                $this->shared=$this->shared ? array_intersect($this->shared, $breakpoint) : $breakpoint;
            }
        }

        parent::init();
    }

    /**
     * @return string
     */
    public function run()
    {
        $isShared=true;
        $breakpoints=[];

        if($this->breakpoints)
        {
            foreach($this->assets as $asset)
            {
                foreach($this->breakpoints as $cssClass=>$types)
                {
                    if(in_array($asset->type, $types))
                    {
                        $breakpoints[$cssClass][]=$asset;
                    }

                    if($isShared && !in_array($asset->type, $this->shared))
                    {
                        $isShared=false;
                    }
                }
            }
        }

        if($isShared)
        {
            $breakpoints=[$this->assets];
        }

        foreach($breakpoints as $cssClass=>$files)
        {
            if($this->start!==null || $this->limit!==null)
            {
                $files=array_slice($files, (int)$this->start, $this->limit);
            }

            if($files)
            {
                $content=Yii::$app->getView()->render($this->viewFile, [
                    'files'=>$files,
                ]);

                $options=$this->options;

                if(is_string($cssClass))
                {
                    Html::addCssClass($options, $cssClass);
                }

                if(count($files)>1 && $this->sliderClass)
                {
                    Html::addCssClass($options, $this->sliderClass);
                }

                return Html::tag('div', $content, $options);
            }
        }

        return '';
    }

    /**
     * @return array|string
     */
    public function getViewPath()
    {
        return $this->viewPath === null ? Yii::$app->controller->getViewPath() : $this->viewPath;
    }
}