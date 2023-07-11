<?php

namespace frontend\controllers;

use backend\models\Bot;
use common\models\Job;
use common\models\UserCommunity;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;


/**
 * PlaylistController implements the CRUD actions for Playlist model.
 */
class AutobotController extends Controller {
    /**
     * @inheritDoc
     */
    public function behaviors() {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow'         => true,
                            'matchCallback' => function ($rule, $action) {
                                if (Yii::$app->user->isGuest || !in_array(Yii::$app->user->identity->group, [1, 2])) {
                                    throw new \yii\web\HttpException(404, 'Page not found.');
                                } else {
                                    return true;
                                }
                            }
                        ],
                    ],
                ],
                'verbs'  => [
                    'class'   => VerbFilter::class,
                    'actions' => [
                        'delete' => ['post'],
                    ],
                ],
            ]
        );
    }


    /**
     *
     * @return string
     */
    public function actionIndex() {
        $queryOff = Job::find();
        $queryOff->where(
            [
                'status' => 'Готово',
            ]
        );
        $queryOff->orFilterWhere(
            [
                'status' => 'Прервано',
            ]
        );

        $dataOffProvider = new ActiveDataProvider([
            'query'      => $queryOff,
            'pagination' => [
                'pageSize' => 50
            ],
            'sort'       => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        $queryOn = Job::find();
        $queryOn->andFilterWhere(
            [
                'not', ['status' => 'Готово'],
            ]
        );
        $queryOn->andFilterWhere(
            [
                'not', ['status' => 'Прервано'],
            ]
        );

        $dataOnProvider = new ActiveDataProvider([
            'query'      => $queryOn,
            'pagination' => [
                'pageSize' => 50
            ],
            'sort'       => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('index',
            [
                'dataOnProvider'  => $dataOnProvider,
                'dataOffProvider' => $dataOffProvider
            ]);
    }

    /**
     *
     * @return string
     */
    public function actionUpdate($id) {
        if (($model = Job::findOne(['id' => $id])) === null) {
            $this->redirect(['autobot/index']);
        }

        switch ($model->action) {
            case 'monitoringComments':
                return $this->actionMonitoringComments(json_decode($model->params, true));
            case 'monitoringBatch':
                return $this->actionMonitoringBatch(json_decode($model->params, true));
        }

        $this->redirect(['autobot/index']);
    }

    /**
     *
     * @return string
     */
    public function actionMonitoringBatch($params = null) {
        $model = new \frontend\models\RepostAndLikeAutobotForm;
        if ($this->request->isPost) {
            $model->load($this->request->post());

            if ($model->validate()) {
                $groups = array_unique(explode(PHP_EOL, $model->groupsList));

                try {
                    $postdata = http_build_query(
                        array(
                            'groupsList'       => $groups,
                            'interval'         => $model->interval,
                            'playlist'         => $model->playlist,
                            'taskName'         => $model->taskName,
                            'botsFilter'       => $model->botsFilter,
                            'botsCountLikes'   => $model->botsCountLikes,
                            'botsCountReposts' => $model->botsCountReposts,
                            'autoSubscribe'    => $model->autoSubscribe,
                            'toFavorite'       => $model->toFavorite,
                            'action'           => 'monitoringBatch'
                        )
                    );

                    if (self::SendPostToBackend("http://127.0.0.1:3333/bots/botaction", $postdata)) {
                        Yii::$app->session->setFlash('success', 'Задание для ботов отрпавлено на сервер');
                        $this->redirect(['autobot/index']);
                    }
                } catch (Throwable $th) {
                    Yii::$app->session->setFlash('error', $th->getMessage());
                }
            } else {
                $errors = $model->errors;
                Yii::$app->session->setFlash('error', $errors);
            }
        }

        if ($params !== null) {
            $model->load(self::prepareParams($params), '');
        }

        $query = UserCommunity::find()->with('community');
        $query->andFilterWhere([
            'created_by' => Yii::$app->user->id,
            'type'       => 'M'
        ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('monitoring-batch',
            [
                'bots'         => self::MakeGenderFilterOfBots(),
                'dataProvider' => $dataProvider,
                'model'        => $model
            ]);
    }


    /**
     * Функция возращает массив с количестом активных ботов всего 
     * и мужского и женского пола в отдельности.
     * Используется для передачи фильтра в формы View копонентов работы с ботами.
     * @return array массив фильтра
     */
    private static function MakeGenderFilterOfBots(): array {
        $botsCount   = Bot::find()->where(['not', ['status' => 'Blocked']]);
        $bots['all'] = $botsCount->andWhere(['active' => 1])->count();

        $botsCount = Bot::find()->where(['not', ['status' => 'Blocked']]);
        $bots['f'] = $botsCount->andWhere(['gender' => 'F', 'active' => 1])->count();

        $botsCount = Bot::find()->where(['not', ['status' => 'Blocked']]);
        $bots['m'] = $botsCount->andWhere(['gender' => 'M', 'active' => 1])->count();
        return $bots;
    }

    /**
     * Метод отправляет POST зарпос с данными формы в бэкенд
     * @param string $url адрес запроса
     * @param string $postdata результат функции http_build_query()
     * @return string сообщение HTTP ответа
     */
    private static function SendPostToBackend($url, $postdata) {
        $opts = array(
            'http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);
        return file_get_contents($url, false, $context);
    }

    private static function prepareParams(array $params): array {
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $params[$key] = implode(PHP_EOL, $value);
            }
        }
        return $params;
    }
}