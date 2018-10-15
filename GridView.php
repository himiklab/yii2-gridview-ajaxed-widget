<?php
/**
 * @link https://github.com/himiklab/yii2-gridview-ajaxed-widget
 * @copyright Copyright (c) 2014-2018 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\yii2\ajaxedgrid;

use Yii;
use yii\bootstrap\Modal;
use yii\grid\ActionColumn;
use yii\grid\GridView as BaseGridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/**
 * Improved Yii2 GridView widget with support ajax, pjax and modal (Bootstrap).
 *
 * @author HimikLab
 * @package himiklab\yii2\ajaxedgrid
 */
class GridView extends BaseGridView
{
    /** @var boolean */
    protected static $isWidgetFirst = true;

    /** @var integer Increase if you have problems. */
    public static $pjaxTimeout = 5000;

    /** @var boolean */
    public $readOnly = false;

    /** @var boolean Add ActionColumn. */
    public $actionColumnEnabled = true;

    /** @var boolean Place a ActionColumn to the left. */
    public $actionColumnLeft = false;

    /** @var string */
    public $actionColumnTemplate = '{update} {delete}';

    /** @var array @see ActionColumn::buttons. */
    public $actionColumnAdditionButtons = [];

    /** @var array Default Default buttons to add. */
    public $addButtons = ['Добавить'];

    /** @var string */
    public $addButtonsClass = 'btn btn-success';

    /** @var string|array */
    public $createRoute = 'create';

    /** @var string|array */
    public $viewRoute;

    /** @var string|array */
    public $updateRoute;

    /** @var string|array */
    public $deleteRoute;

    /** @var array Config for modal widget. */
    public $modalConfig = ['size' => Modal::SIZE_LARGE];

    public function init()
    {
        if (!$this->readOnly && $this->actionColumnEnabled) {
            $this->columns = $this->actionColumnLeft ?
                \array_merge([$this->prepareActionColumn()], $this->columns) :
                \array_merge($this->columns, [$this->prepareActionColumn()]);
        }

        parent::init();
    }

    public function run()
    {
        if ($this->readOnly) {
            parent::run();
            return;
        }

        $widgetId = $this->id;
        echo "<div id=\"{$widgetId}-ajaxed-grid\">" . PHP_EOL . '<p>' . PHP_EOL;
        foreach ($this->addButtons as $key => $value) {
            echo Html::a(
                \is_int($key) ? $value : $key,
                '#',
                [
                    'class' => $this->addButtonsClass,
                    'data-pjax' => '0',
                    'data-action' => 'modal-form',
                    'data-remote' => Url::toRoute(\is_int($key) ? $this->createRoute : $value)
                ]
            );
            echo '&nbsp;';
        }
        echo '</p>' . PHP_EOL;

        Pjax::begin(['id' => "{$widgetId}-pjax", 'timeout' => static::$pjaxTimeout]);
        parent::run();
        Pjax::end();

        Modal::begin(\array_merge($this->modalConfig, ['id' => "{$widgetId}-modal"]));
        Modal::end();
        echo '</div>' . PHP_EOL;

        $this->prepareJs();
    }

    /**
     * @param array $config
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function widget($config = [])
    {
        $config['class'] = \get_called_class();
        $widget = Yii::createObject($config);
        return $widget->run();
    }

    /**
     * @return array
     */
    protected function prepareActionColumn()
    {
        return [
            'class' => ActionColumn::className(),
            'template' => $this->actionColumnTemplate,
            'buttons' => \array_merge([
                'update' => function ($url, $model) {
                    /** @var $model \yii\db\ActiveRecord */
                    return Html::a(
                        '<span class="glyphicon glyphicon-pencil"></span>',
                        '#',
                        [
                            'title' => Yii::t('yii', 'Update'),
                            'data-pjax' => '0',
                            'data-action' => 'modal-form',
                            'data-remote' => $this->updateRoute ?
                                Url::toRoute(\array_merge(
                                    \is_array($this->updateRoute) ? $this->updateRoute : [$this->updateRoute],
                                    \is_array($model->primaryKey) ? $model->primaryKey : ['id' => $model->primaryKey]
                                ))
                                : $url
                        ]
                    );
                },
                'view' => function ($url, $model) {
                    /** @var $model \yii\db\ActiveRecord */
                    return Html::a(
                        '<span class="glyphicon glyphicon-eye-open"></span>',
                        '#',
                        [
                            'title' => Yii::t('yii', 'View'),
                            'data-pjax' => '0',
                            'data-action' => 'modal-form',
                            'data-remote' => $this->viewRoute ?
                                Url::toRoute(\array_merge(
                                    \is_array($this->viewRoute) ? $this->viewRoute : [$this->viewRoute],
                                    \is_array($model->primaryKey) ? $model->primaryKey : ['id' => $model->primaryKey]
                                ))
                                : $url
                        ]
                    );
                },
                'delete' => function ($url, $model) {
                    /** @var $model \yii\db\ActiveRecord */
                    return Html::a(
                        '<span class="glyphicon glyphicon-trash"></span>',
                        '#',
                        [
                            'title' => Yii::t('yii', 'Delete'),
                            'data-action' => 'post-request',
                            'data-remote' => $this->deleteRoute ?
                                Url::toRoute(\array_merge(
                                    \is_array($this->deleteRoute) ? $this->deleteRoute : [$this->deleteRoute],
                                    \is_array($model->primaryKey) ? $model->primaryKey : ['id' => $model->primaryKey]
                                ))
                                : $url,
                            'data-confirm-message' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                        ]
                    );
                },
            ], $this->actionColumnAdditionButtons),
        ];
    }

    protected function prepareJs()
    {
        $view = $this->getView();
        if (static::$isWidgetFirst) {
            $pjaxTimeout = static::$pjaxTimeout;
            $view->registerJs(<<<JS
if (jQuery.pjax.defaults.timeout < {$pjaxTimeout}) {
    jQuery.pjax.defaults.timeout = {$pjaxTimeout};
}
JS
            );

            $view->registerJs(<<<JS
"use strict";
jQuery.fn.modal.Constructor.prototype.enforceFocus = function () {
}; // select2 modal fix

// https://github.com/jeremybanks/b64-to-blob
const b64toBlob = function (b64Data, contentType, sliceSize) {
    contentType = contentType || "";
    sliceSize = sliceSize || 512;

    const byteCharacters = atob(b64Data);
    const byteArrays = [];

    for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
        const slice = byteCharacters.slice(offset, offset + sliceSize);
        const byteNumbers = new Array(slice.length);
        for (let i = 0; i < slice.length; i++) {
            byteNumbers[i] = slice.charCodeAt(i);
        }

        byteArrays.push(new Uint8Array(byteNumbers));
    }

    return new Blob(byteArrays, {type: contentType});
};

const widgetShow = function (widgetId) {
    const grid = jQuery("#" + widgetId + "-ajaxed-grid");
    const modal = jQuery("#" + widgetId + "-modal");
    const modalHide = function () {
        modal.modal("hide");
    };
    const gridReload = function () {
        jQuery.pjax.reload({container: "#" + widgetId + "-pjax"});
    };

    // modal form show
    grid.on("click", "a[data-action='modal-form']", function (e) {
        e.preventDefault();
        jQuery("div.modal-body", modal).load(jQuery(this).data("remote"));
        modal.modal("show");
    });

    // post grid action
    grid.on("click", "a[data-action='post-request']", function (e) {
        e.preventDefault();

        const button = jQuery(this);
        const confirmMessage = button.data("confirm-message");
        if (confirmMessage ? confirm(confirmMessage) : true) {
            jQuery.ajax({
                type: "POST",
                url: button.data("remote"),
                success: function (message) {
                    if (jQuery.trim(message)) {
                        messageAction(message);
                    }

                    gridReload();
                }
            });
        }
    });

    // submit form action
    modal.on("click", "button[type='submit']", function (e) {
        const form = jQuery("form", modal);
        if (form.attr("id") === "w0") {
            form.attr("id", widgetId + "-modal-form");
            form.yiiActiveForm();
        }

        const confirmMessage = jQuery(this).data("confirm-message");
        if (confirmMessage && e.originalEvent) {
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                modalHide();
            }
        }
    });

    // submit form action
    modal.on("beforeSubmit", "form", function () {
        const form = jQuery(this);
        if (form.find(".has-error").length) {
            return false;
        }

        jQuery("button[type='submit']", form).prop("disabled", true);
        const formSpinner = jQuery("#form-spinner", modal);
        if (formSpinner.length) {
            formSpinner.addClass("fa fa-2x fa-spinner fa-pulse");
        }

        jQuery.ajax({
            type: "POST",
            cache: false,
            url: form.attr("action"),
            data: new FormData(form[0]),
            processData: false,
            contentType: false,
            success: function (message) {
                if (jQuery.trim(message) && !messageAction(message)) {
                    jQuery(".modal-body", modal).html(message);
                    return;
                }

                modalHide();
                gridReload();
            },
            error: function () {
                modalHide();
            }
        });

        return false;
    });

    const messageAction = function (message) {
        if (message === "#reload") {
            window.location.href = window.location.href.replace("#", "");
        } else if (message.substring(0, 7) === "#alert ") {
            alert(message.substring(7));
            return true;
        } else if (message.substring(0, 10) === "#redirect ") {
            window.location.href = message.substring(10);
        } else if (message.substring(0, 6) === "#file ") {
            const fileNamePosition = message.indexOf(" ", message.indexOf(" ") + 1);
            const link = document.createElement("a");

            link.href = URL.createObjectURL(
                b64toBlob(message.substring(fileNamePosition + 1), "application/octet-stream")
            );
            link.style = "visibility:hidden";
            link.download = message.substring(6, fileNamePosition);

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            return true;
        }

        return false;
    };

    modal.on("hidden.bs.modal", modal, function () {
        jQuery(".modal-body", this).empty();
    });
};
JS
            );
        }

        $widgetId = $this->id;
        $view->registerJs("widgetShow(\"{$widgetId}\");");
        static::$isWidgetFirst = false;
    }
}
