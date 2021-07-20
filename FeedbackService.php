<?php
namespace app\modules\api\v1\services;

use app\common\services\Service;
use app\modules\api\v1\repositories\FeedbackRepository;
use app\modules\api\v1\requests\FeedbackRequest;
use yii\db\ActiveRecord;

/**
 * Class FeedbackService
 *
 * @package app\services
 * @mixin FeedbackRepository
 * @param array $params
 * @return array
 */
class FeedbackService extends Service
{
    /**
     * @var FeedbackRepository
     */
    protected $repository;

    public function getRepositoryClass(): string
    {
        return FeedbackRepository::class;
    }

    public function getFeedback()
    {
        return $this->repository->getFeedback();
    }

    /**
     * Создание нового отзыва
     * @return bool|array|ActiveRecord
     */
    public function create(FeedbackRequest $params)
    {
        return $this->repository->create($params);
    }

    /**
     * Получение отзыва
     * @param $model
     * @return bool|array|ActiveRecord
     */
    public function getByType($model)
    {
        return $this->repository->getByType($model);
    }
}