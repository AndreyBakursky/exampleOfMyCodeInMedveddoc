<?php
namespace app\modules\api\v1\repositories;

use app\common\repositories\Repository;
use app\helpers\AjaxResponseHelper;
use app\helpers\HelpersGlobals;
use app\models\NewFeedback;
use app\modules\api\v1\requests\FeedbackRequest;

class FeedbackRepository extends Repository
{
    public function getClass()
    {
        return NewFeedback::class;
    }

    public function getFeedback()
    {
        return NewFeedback::find()->with('user')->orderBy(['id' => SORT_ASC])->all();
    }

    /**
     * add feedback
     * @param FeedbackRequest $params
     * @return array
     */
    public function create(FeedbackRequest $params)
    {

        $params = $params->getAttributes();

        $model = new NewFeedback();
        $model->load(array_merge($params, array_merge($params, ['approve' => NewFeedback::FEEDBACK_APPROVE_FALSE])), '');

        if ($model->validate()) {
            $model->save();
            return AjaxResponseHelper::success();
        } else {
            return AjaxResponseHelper::fail(HelpersGlobals::getErrorString($model->getErrors()));
        }
    }

    /**
     * get feedback
     * @param $model
     * @return array
     */
    public function getByType($model) {
        $feedback = $model->type;
        $query = NewFeedback::find()
            ->select([
                'nf.id', 'nf.user_id', 'nf.type', 'nf.text', 'nf.status', 'nf.doctor_id',
                'nf.department_id', 'nf.rating', 'nf.phone', 'nf.name', 'nf.approve'
            ])
            ->alias('nf')
            ->where('type=:feedback', [':feedback' => $feedback])
            ->andWhere(['approve' => NewFeedback::FEEDBACK_APPROVE_TRUE]);
        if ($model->departmentId) {
            $departmentId = $model->departmentId;
            $query->andWhere('department_id =: departmentId', [':departmentId' => $departmentId]);
        }
        if ($model->doctorId) {
            $doctorId = $model->doctorId;
            $query->innerJoin('clinic_department AS cd', 'cd.id = nf.doctor_department_id');
            $query->addSelect('cd.name AS doctor_department_name');
            $query->andWhere('doctor_id =: doctorId', [':doctorId' => $doctorId]);
        }
        $result = $query->asArray()->all();
        foreach ($result as $key => $value) {
            $result[$key]['phone'] = substr_replace($result[$key]['phone'], '****', 4, 4);
        }
        return $result;
    }
}