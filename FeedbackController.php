<?php
namespace app\modules\api\v1\controllers;

use app\common\services\NotificationService;
use app\common\services\TelegramService;
use app\helpers\AjaxResponseHelper;
use app\helpers\HelpersGlobals;
use app\modules\api\v1\requests\FeedbackRemoveRequest;
use app\modules\api\v1\requests\FeedbackRequest;
use app\modules\api\v1\requests\FeedbackUpdateRequest;
use app\modules\api\v1\requests\GetFeedbackRequest;
use app\modules\api\v1\services\FeedbackService;
use Yii;

class FeedbackController extends BaseApiController
{
    /**
     * @OA\POST(
     *     path="/api/feedback/add-feedback",
     *     summary="Добавить отзыв",
     *     tags={"Feedback"},
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={
     *                      "type",
     *                      "name",
     *                  },
     *                  @OA\Property(
     *                      property="type",
     *                      type="string",
     *                      description="Тип отзыва (doctor/clinic)"
     *                  ),
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      description="Имя пользователя"
     *                  ),
     *                  @OA\Property(
     *                      property="text",
     *                      type="string",
     *                      description="Текст отзыва"
     *                  ),
     *                  @OA\Property(
     *                      property="doctor_id",
     *                      type="int",
     *                      description="Id врача"
     *                  ),
     *                  @OA\Property(
     *                      property="department_id",
     *                      type="int",
     *                      description="ID клиники"
     *                  ),
     *                  @OA\Property(
     *                      property="rating",
     *                      type="int",
     *                      description="Рейтинг"
     *                  ),
     *                  @OA\Property(
     *                      property="email",
     *                      type="string",
     *                      description="Электронная почта"
     *                  ),
     *                  @OA\Property(
     *                      property="phone",
     *                      type="string",
     *                      description="Телефон пользователя"
     *                  ),
     *              )
     *          )
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="Success operation"
     *     ),
     *     @OA\Response(
     *         response= 400,
     *         description="Bad Request"
     *     ),
     * )
     * @throws \yii\base\InvalidConfigException
     */

    public function actionAddFeedback()
    {
        $request = Yii::$app->request;

        $model = new FeedbackRequest();
        $model->load(Yii::$app->request->post(), '');

        if ($model->validate()) {

            /** @var FeedbackService $service */
            $result = Yii::createObject(FeedbackService::class);
            $result->create($model);

            /** Отправляем сообщение на email */
            /** @var NotificationService $service */
            NotificationService::newFeedback(
                [
                    'name' => $model->name,
                    'phone' => $model->phone,
                    'department' => $model->getDepartment($model->department_id),
                    'doctorName' => $model->getDoctor($model->doctor_id),
                    'rating' => $model->rating,
                    'text' => $model->text,
                    'email' => $model->email
                ]
            );

            /** Отправляем сообщение в Telegram */
            /** @var TelegramService $service */
            $feedback = Yii::createObject(TelegramService::class);
            $feedback->newFeed(
                [
                    'name' => $model->name,
                    'phone' => $model->phone,
                    'department' => $model->getDepartment($model->department_id),
                    'doctorName' => $model->getDoctor($model->doctor_id),
                    'doctorDepartment' => $model->getDepartment($model->doctor_department_id),
                    'rating' => $model->rating,
                    'text' => $model->text,
                    'email' => $model->email,
                    'client_ip' => $request->getUserIP(),
                    'client_user_agent' => $request->getUserAgent(),
                    'client_user_referer' => $request->getReferrer()
                ]
            );
            return AjaxResponseHelper::success(['data' => $result]);
        } else {
            return AjaxResponseHelper::fail(HelpersGlobals::getErrorString($model->getErrors()));
        }
    }

    /**
     * @OA\Get(
     *     path="/api/feedback/get-feedback",
     *     summary="Получить список отзывов по врачам или клиникам",
     *     tags={"Feedback"},
     *     @OA\Parameter(
     *          parameter="type",
     *          name="type",
     *          in="query",
     *          required=true,
     *          description="Получить отзывы по типу",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          parameter="doctorId",
     *          name="doctorId",
     *          in="query",
     *          required=false,
     *          description="Получить отзывы по доктору",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *      @OA\Parameter(
     *          parameter="deparmentId",
     *          name="departmentId",
     *          in="query",
     *          required=false,
     *          description="Получить отзывы по клинике",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success operation"
     *     ),
     *     @OA\Response(
     *         response= 400,
     *         description="Feedback and/or type not found",
     *     ),
     * )
     */

    public function actionGetFeedback()
    {
        $model = new GetFeedbackRequest();
        $model->load(Yii::$app->request->get(), '');
        if ($model->validate()) {
            /** @var FeedbackService $service */
            $feedback = Yii::createObject(FeedbackService::class);
            $result = $feedback->getByType($model);
            if (!empty($result))
                return AjaxResponseHelper::success(['data' => $result]);
            else
                return AjaxResponseHelper::fail('Feedback and/or type not found');
        } else {
            return AjaxResponseHelper::fail(HelpersGlobals::getErrorString($model->getErrors()));
        }
    }

    /**
     * @OA\POST(
     *     path="/api/feedback/feedback-remove",
     *     summary="Удалить отзыв по id",
     *     tags={"Feedback"},
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={
     *                      "feedbackId",
     *                  },
     *                  @OA\Property(
     *                      property="feedbackId",
     *                      type="integer",
     *                      description="ID отзыва"
     *                  ),
     *              )
     *          )
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="Success operation
     *          'Отзыв успешно удален!'"
     *     ),
     *     @OA\Response(
     *         response= 400,
     *         description="Bad Request",
     *     ),
     * )
     */
    public function actionFeedbackRemove()
    {
        $request = Yii::$app->request;

        $model = new FeedbackRemoveRequest();
        $model->load($request->post(),'');

        if ($model->validate()) {
            $model->remove($model->feedbackId);
            return AjaxResponseHelper::success(['status' => 'Отзыв успешно удален!']);
        } else {
            return AjaxResponseHelper::fail(HelpersGlobals::getErrorString($model->getErrors()));
        }
    }

    /**
     * @OA\POST(
     *     path="/api/feedback/feedback-update",
     *     summary="Обновить отзыв (имя отправителя, рейтинг и текст отзыва) по id",
     *     tags={"Feedback"},
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={
     *                      "feedbackId",
     *                  },
     *                  @OA\Property(
     *                      property="feedbackId",
     *                      type="integer",
     *                      description="ID отзыва"
     *                  ),
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      description="Имя отправителя"
     *                  ),
     *                  @OA\Property(
     *                      property="rating",
     *                      type="integer",
     *                      description="Рейтинг отзыва"
     *                  ),
     *                  @OA\Property(
     *                      property="text",
     *                      type="string",
     *                      description="Текст отзыва"
     *                  ),
     *              )
     *          )
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="Success operation
     *          'Отзыв успешно обновлен!'"
     *     ),
     *     @OA\Response(
     *         response= 400,
     *         description="Bad Request",
     *     ),
     * )
     */
    public function actionFeedbackUpdate()
    {
        $request = Yii::$app->request;

        $model = new FeedbackUpdateRequest();
        $model->load($request->post(), '');

        if ($model->validate()) {
            $model->update(
                $model->feedbackId,
                $model->name,
                $model->rating,
                $model->text
            );
            return AjaxResponseHelper::success(['status' => 'Отзыв успешно обновлен!']);
        } else {
            return AjaxResponseHelper::fail(HelpersGlobals::getErrorString($model->getErrors()));
        }
    }
}