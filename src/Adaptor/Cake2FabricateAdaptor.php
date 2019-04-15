<?php
namespace Cake2Fabricate\Adaptor;

use ClassRegistry;
use Fabricate\Adaptor\AbstractFabricateAdaptor;
use Fabricate\Model\FabricateModel;

class Cake2FabricateAdaptor extends AbstractFabricateAdaptor
{
    /**
     * @var bool
     */
    private $filter_key;
    /**
     * @var bool
     */
    private $auto_validate;

    public function __construct($filter_key = false, $auto_validate = false)
    {
        $this->filter_key = $filter_key;
        $this->auto_validate = $auto_validate;
    }

    public function getModel($modelName)
    {
        $model = new FabricateModel($modelName);

        $cakeModel = ClassRegistry::init(['class' => $modelName, 'testing' => true]);

        foreach ($cakeModel->schema() as $field => $fieldInfo) {
            $type = $fieldInfo['type'];
            unset($fieldInfo['type']);
            $model->addColumn($field, $type, $fieldInfo);
        }

        foreach ($cakeModel->getAssociated() as $aliasName => $associationType) {
            if ($associationType === 'belongsTo') {
                $model->belongsTo($aliasName, $cakeModel->belongsTo[$aliasName]['foreignKey'], $cakeModel->belongsTo[$aliasName]['className']);
            } elseif ($associationType === 'hasOne') {
                $model->hasOne($aliasName, $cakeModel->hasOne[$aliasName]['foreignKey'], $cakeModel->hasOne[$aliasName]['className']);
            } elseif ($associationType === 'hasMany') {
                $model->hasMany($aliasName, $cakeModel->hasMany[$aliasName]['foreignKey'], $cakeModel->hasMany[$aliasName]['className']);
            }
        }

        return $model;
    }

    public function create($modelName, $attributes, $recordCount)
    {
        $cakeModel = ClassRegistry::init(['class' => $modelName, 'testing' => true]);

        foreach ($attributes as $data) {
            $cakeModel->create($data, $this->filter_key);
            $cakeModel->saveAssociated(null, [
                'validate' => $this->auto_validate,
                'deep' => true,
            ]);
        }

        return $cakeModel;
    }

    public function build($modelName, $data)
    {
        $cakeModel = ClassRegistry::init(['class' => $modelName, 'testing' => true]);

        $cakeModel->create($data[0], $this->filter_key);

        return $cakeModel;
    }
}
