<?php

namespace frontend\models;

use frontend\models\CommonBotModel;

/**
 * Avatar bot form
 */
class FormRepostAndLikeAutobot extends CommonBotModel {
    public $groupsList;
    public $botsCountLikes;
    public $botsCountReposts;
    public $playlist;
    public $toFavorite;
    public $autoSubscribe;

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return array_merge(
            [
                ['botsCount', 'default', 'value' => 0],

                ['groupsList', 'trim'],
                ['groupsList', 'string', 'max' => 5000],
                ['groupsList', 'required'],

                ['taskName', 'trim'],
                ['taskName', 'string', 'max' => 200],
                ['taskName', 'default', 'value' => 'Repost and like monitoring #' . random_int(0, 1000000)],

                ['botsCountReposts', 'integer', 'min' => 0],
                ['botsCountReposts', 'default', 'value' => 0],

                ['botsCountLikes', 'integer', 'min' => 0],
                ['botsCountLikes', 'default', 'value' => 0],

                ['toFavorite', 'integer', 'min' => 0],
                ['toFavorite', 'default', 'value' => 0],

                ['autoSubscribe', 'integer', 'min' => 0],
                ['autoSubscribe', 'default', 'value' => 0],

                ['playlist', 'match', 'pattern' => '/^https:\/\/vk\.com\//'],
                ['playlist', 'url', 'validSchemes' => ['https']],
                ['playlist', 'string', 'max' => 500],
            ],
            parent::rules());
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return array_merge(
            parent::attributeLabels(),
            [
                'groupsList'       => 'Список ссылок на группы',
                'toFavorite'       => 'Репостить в Избранное',
                'autoSubscribe'    => 'Автоматически вступать в группы',
                'botsCountReposts' => 'Ботов для репостов',
                'botsCountLikes'   => 'Ботов для лайков',
                'playlist'         => 'Ожидаемый плейлист',
            ]);
    }
}