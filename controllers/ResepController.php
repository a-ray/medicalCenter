<?php

namespace app\controllers;

use Yii;
use app\models\Resep;
use app\models\ResepSearch;
use app\models\Obat;
use app\models\Model;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\RefResep;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\widgets\ActiveForm;
use yii\data\ActiveDataProvider;

// use yii\bootstrap\Modal;

/**
 * ResepController implements the CRUD actions for Resep model.
 */
class ResepController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Resep models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ResepSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Resep model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $id_res = $this->id;
        // $modelRef = RefResep::findOne(app\models\RefResep->$id_resep);
        // $modelRef = new RefResep();
        $ref = RefResep::find(['id_resep' => $id_res])->all();

        // $modelRef = [];
        // foreach ($ref as $i => $val) {
        //     $modelRef[$i] = RefResep::findOne($val->id);
        // }
        // echo '<pre>';
        // var_dump($modelRef);
        // echo '</pre>';
        // exit();
        $dataProvider = new ActiveDataProvider([
             'query' => RefResep::find()->where(['id_resep' => $id]),
             'pagination' => false,
         ]);
        return $this->render('view', [
            'model' => $this->findModel($id),
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Resep model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Resep();
        $modelRef = [new RefResep];
        $data = ArrayHelper::map(Obat::find()->select(['id_obat', 'nama_obat'] )->all(), 'id_obat', 'nama_obat');

        if ($model->load(Yii::$app->request->post())) {
            $model->created_at = time();
            $model->status = 0;
            $modelRef = Model::createMultiple(RefResep::classname());
            Model::loadMultiple($modelRef, \Yii::$app->request->post());

            // ajax validation
            // if (Yii::$app->request->isAjax) {
            //     Yii::$app->response->format = Response::FORMAT_JSON;
            //     return ArrayHelper::merge(
            //         ActiveForm::validateMultiple($modelRef),
            //         ActiveForm::validate($model)
            //     );
            // }

            // validate all models
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelRef, [
              'obat',
              'dosis',
              ]) && $valid;

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        foreach ($modelRef as $modelRefs) {
                            $modelRefs->id_resep = $model->id_resep;
                            if (! ($flag = $modelRefs->save(false))) {
                                $transaction->rollBack();
                                break;
                            }
                        }
                    }
                    if ($flag) {
                        $transaction->commit();
                        return $this->redirect(['view', 'id' => $model->id_resep]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                    return $this->render('create', [
                        'model' => $model,
                        'modelRef' => $modelRef,
                        'data' => $data,
                    ]);
                }
            }
        } else {
          return $this->render('create', [
              'model' => $model,
              'modelRef' => (empty($modelRef)) ? [new RefResep] : $modelRef,
              'data' => $data,
          ]);
        }
    }

    /**
     * Updates an existing Resep model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    // public function actionUpdate($id)
    // {
    //     $model = $this->findModel($id);
    //
    //     if ($model->load(Yii::$app->request->post()) && $model->save()) {
    //         return $this->redirect(['view', 'id' => $model->id_resep]);
    //     } else {
    //         return $this->render('update', [
    //             'model' => $model,
    //         ]);
    //     }
    // }

    /**
     * Deletes an existing Resep model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Resep model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Resep the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Resep::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
