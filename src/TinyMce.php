<?php
/**
 * @copyright Copyright (c) 2013-2015 2amigOS! Consulting Group LLC
 * @link http://2amigos.us
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace powerkernel\tinymce;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 *
 * TinyMCE renders a tinyMCE js plugin for WYSIWYG editing.
 *
 * @author Antonio Ramirez <amigo.cobos@gmail.com>
 * @link http://www.ramirezcobos.com/
 * @link http://www.2amigos.us/
 */
class TinyMce extends InputWidget
{
    /**
     * @var string the language to use. Defaults to null (en).
     */
    public $language;
    /**
     * @var array the options for the TinyMCE JS plugin.
     * Please refer to the TinyMCE JS plugin Web page for possible options.
     * @see http://www.tinymce.com/wiki.php/Configuration
     */
    public $clientOptions = [
        'menubar'=> false,
        //'content_css'=>'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
        'image_title'=>'true',
        'image_caption'=>false,
        'image_advtab'=>false,
        'image_class_list'=>[
            ['title'=>'Responsive', 'value'=>'img-responsive'],
            ['title'=>'Rounded', 'value'=>'img-responsive img-rounded'],
            ['title'=>'Thumbnail', 'value'=>'img-responsive img-thumbnail']
        ],
        'plugins' => [
            "advlist autolink lists link charmap print preview anchor",
            "searchreplace visualblocks code fullscreen",
            "insertdatetime media table contextmenu paste image"
        ],
        'toolbar' => "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | removeformat code"
    ];
    /**
     * @var bool whether to set the on change event for the editor. This is required to be able to validate data.
     * @see https://github.com/2amigos/yii2-tinymce-widget/issues/7
     */
    public $triggerSaveOnBeforeValidateForm = true;

    public $options=['rows' => 18];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
        $bootstrapBaseUrl=Yii::$app->assetManager->getBundle('yii\bootstrap\BootstrapAsset')->baseUrl;
        $this->clientOptions['content_css']=$bootstrapBaseUrl.'/css/bootstrap.css';
        if(empty($this->language)){
            $this->language=$this->getTinyMCELang(Yii::$app->language);
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }
        $this->registerClientScript();
    }

    /**
     * Registers tinyMCE js plugin
     */
    protected function registerClientScript()
    {
        $js = [];
        $view = $this->getView();

        TinyMceAsset::register($view);

        $id = $this->options['id'];

        $this->clientOptions['selector'] = "#$id";
        // @codeCoverageIgnoreStart
        if ($this->language !== null) {
            $langFile = "langs/{$this->language}.js";
            $langAssetBundle = TinyMceLangAsset::register($view);
            $langAssetBundle->js[] = $langFile;
            $this->clientOptions['language_url'] = $langAssetBundle->baseUrl . "/{$langFile}";
        }
        // @codeCoverageIgnoreEnd

        $options = Json::encode($this->clientOptions);

        $js[] = "tinymce.init($options);";
        if ($this->triggerSaveOnBeforeValidateForm) {
            $js[] = "$('#{$id}').parents('form').on('beforeValidate', function() { tinymce.triggerSave(); });";
        }
        $view->registerJs(implode("\n", $js));
    }

    /**
     * @param $lang
     * @return mixed|null
     */
    protected function getTinyMCELang($lang)
    {
        $lang = str_ireplace('-', '_', $lang);
        $path = Yii::getAlias('@vendor') . '/powerkernel/yii2-tinymce/src/assets/langs';
        $files = scandir($path);
        $availableLang = [];
        foreach ($files as $file) {
            if (preg_match('/([\w\W_]+)\.js/i', $file, $name)) {
                $availableLang[] = $name[1];
            }
        }

        if (in_array($lang, $availableLang)) {
            return $lang;
        }
        return null;

    }
}
