Ajaxed GridView Widget for Yii2
========================
Improved Yii2 GridView widget with support ajax, pjax and modal (Bootstrap).

[![Packagist](https://img.shields.io/packagist/dt/himiklab/yii2-gridview-ajaxed-widget.svg)]() [![Packagist](https://img.shields.io/packagist/v/himiklab/yii2-gridview-ajaxed-widget.svg)]()  [![license](https://img.shields.io/badge/License-MIT-yellow.svg)]()

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

* Either run

```
php composer.phar require --prefer-dist "himiklab/yii2-gridview-ajaxed-widget" "*"
```

or add

```json
"himiklab/yii2-gridview-ajaxed-widget" : "*"
```

to the require section of your application's `composer.json` file.

Usage
-----

```php
// index.php
use himiklab\yii2\ajaxedgrid\GridView;

GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'title',
        'author',
        'language',
        'visible:boolean',
    ],
    'jsErrorCallback' => 'function(jqXHR, textStatus) {console.log(jqXHR, textStatus, errorThrown)}',
]);
```

```php
// _form.php
    <?php $form = ActiveForm::begin(['id' => 'test-form']); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'author')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'language')->dropDownList($model::getAllLanguages()) ?>

    <?= $form->field($model, 'visible')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
```

```php
// controller
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Page::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $model = new Page();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return null;
        }

        return $this->renderAjax('_form', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return null;
            //return '#reload';
            //return '#alert OK!';
            //return '#redirect /';
            //return '#file document.txt ' . \base64_encode('document content');
        }

        return $this->renderAjax('_form', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
    }

    protected function findModel($id)
    {
        if (($model = Page::findOne($id)) === null) {
            throw new NotFoundHttpException('Page not found.');
        }

        return $model;
    }
```

It's all!
