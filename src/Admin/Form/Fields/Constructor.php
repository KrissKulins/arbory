<?php

namespace Arbory\Base\Admin\Form\Fields;

use Arbory\Base\Admin\Constructor\BlockInterface;
use Arbory\Base\Admin\Constructor\Models\ConstructorBlock;
use Arbory\Base\Admin\Constructor\Registry;
use Arbory\Base\Admin\Form\Fields\Renderer\ConstructorFieldRenderer;
use Closure;
use Arbory\Base\Admin\Form\Fields\Concerns\HasRelationships;
use Arbory\Base\Admin\Form\FieldSet;
use Arbory\Base\Admin\Form\Fields\Renderer\NestedFieldRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

/**
 * @package Arbory\Base\Admin\Form\Fields
 */
class Constructor extends AbstractRelationField implements NestedFieldInterface
{
    const BLOCK_NAME = 'name';
    const BLOCK_CONTENT = 'content';


    use HasRelationships;

    /**
     * @var string
     */
    protected $orderBy;

    protected $rendererClass = ConstructorFieldRenderer::class;

    protected $style = 'nested';

    protected $isSortable = false;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * AbstractRelationField constructor.
     *
     * @param string        $name
     * @param Registry|null $registry
     */
    public function __construct($name, ?Registry $registry = null)
    {
        $this->registry = $registry ?: app(Registry::class);

        parent::__construct($name);
    }

    /**
     * @return \Arbory\Base\Admin\Constructor\BlockInterface[]|\Illuminate\Support\Collection
     */
    public function getTypes()
    {
        return $this->registry->all();
    }

    /**
     * @return Registry|\Illuminate\Foundation\Application|mixed
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * @return bool
     */
    public function canAddRelationItem()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canSortRelationItems()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canRemoveRelationItems()
    {
        return true;
    }

    /**
     * @param ConstructorBlock $model
     * @param                  $index
     *
     * @return FieldSet
     */
    public function getRelationFieldSet(ConstructorBlock $model, $index)
    {
        $blockName  = $model->name;
        $block = $this->resolveBlockByName($blockName);

        if($block === null) {
            throw new \LogicException("Block '{$blockName}' not found");
        }

        $fieldSet = new FieldSet($model, $this->getNameSpacedName() . '.' . $index);

        $fieldSet->hidden($model->getKeyName())
                 ->setValue($model->getKey());

        $fieldSet->hidden(static::BLOCK_NAME)
                 ->setValue($blockName);

        $fieldSet->hidden($model->content()->getMorphType())
                 ->setValue(get_class($model->content()->getModel()));

        if ($this->isSortable() && $this->getOrderBy()) {
            $fieldSet->hidden($this->getOrderBy())
                     ->setValue($model->{$this->getOrderBy()});
        }

        $fieldSet->hasOne(
            'content', Closure::fromCallable([$block, 'fields'])
        );

        return $fieldSet;
    }

    /**
     * @param Request $request
     */
    public function beforeModelSave(Request $request)
    {

    }

    /**
     * @param Request $request
     */
    public function afterModelSave(Request $request)
    {
        $items = (array)$request->input($this->getNameSpacedName(), []);

        foreach ($items as $index => $item) {
            $relatedModel = $this->findRelatedModel($item);

            if (filter_var(array_get($item, '_destroy'), FILTER_VALIDATE_BOOLEAN)) {
                $relatedModel->delete();

                $relatedModel->content()->delete();

                continue;
            }

            $relation = $this->getRelation();

            if (!$relation instanceof MorphMany) {
                throw new \LogicException("Unknown relation used");
            }

            $relatedModel->setAttribute($relation->getForeignKeyName(), $this->getModel()->getKey());
            $relatedModel->fill(array_only($item, $relatedModel->getFillable()));
            $relatedModel->setAttribute($relation->getMorphType(), $relation->getMorphClass());

            $blockName     = array_get($item, static::BLOCK_NAME);
            $blockResource = array_get($item, $relatedModel->content()->getMorphType());
            $block         = $this->resolveBlockByName($blockName);

            if (!$block) {
                throw new \LogicException("Unknown block '{$blockName}'");
            }

            if ($blockResource !== $block->resource()) {
                throw new \LogicException("Invalid resource for '{$blockName}'");
            }

            $relatedFieldSet = $this->getRelationFieldSet(
                $relatedModel,
                $index
            );

            foreach($relatedFieldSet->getFields() as $field) {
                if( $this->isContentField($field) ) {
                    $block->beforeModelSave($request, $field);
                } else {
                    $field->beforeModelSave($request);
                }
            }

            $relatedModel->save();

            foreach($relatedFieldSet->getFields() as $field) {
                if( $this->isContentField($field) ) {
                    $block->afterModelSave($request, $field);
                } else {
                    $field->afterModelSave($request);
                }
            }
        }
    }


    /**
     * @param $variables
     *
     * @return ConstructorBlock
     */
    private function findRelatedModel($variables)
    {
        $relation = $this->getRelation();

        $relatedModelId = array_get($variables, $relation->getRelated()->getKeyName());

        return $relation->getRelated()->findOrNew($relatedModelId);
    }

    /**
     * @return bool
     */
    public function isSortable(): bool
    {
        return $this->isSortable;
    }

    /**
     * @return string|null
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param string $orderBy
     *
     * @return $this
     */
    public function setOrderBy(string $orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        $rules = [];

        $items = (array)request()->input($this->getNameSpacedName(), []);

        foreach ($items as $index => $item) {
            $relatedModel = $this->findRelatedModel($item);

            if (filter_var(array_get($item, '_destroy'), FILTER_VALIDATE_BOOLEAN)) {
                $relatedModel->delete();

                $relatedModel->content()->delete();

                continue;
            }

            $relation = $this->getRelation();

            if (!$relation instanceof MorphMany) {
                throw new \LogicException("Unknown relation used");
            }

            $relatedModel->setAttribute($relation->getForeignKeyName(), $this->getModel()->getKey());
            $relatedModel->fill(array_only($item, $relatedModel->getFillable()));
            $relatedModel->setAttribute($relation->getMorphType(), $relation->getMorphClass());

            $blockName     = array_get($item, static::BLOCK_NAME);
            $blockResource = array_get($item, $relatedModel->content()->getMorphType());
            $block         = $this->resolveBlockByName($blockName);

            if (!$block) {
                throw new \LogicException("Unknown block '{$blockName}'");
            }

            if ($blockResource !== $block->resource()) {
                throw new \LogicException("Invalid resource for '{$blockName}'");
            }

            $relatedFieldSet = $this->getRelationFieldSet(
                $relatedModel,
                $index
            );

            foreach($relatedFieldSet->getFields() as $field) {
                $rules = array_merge($rules, $field->getRules());

            }
        }
        
        return $rules;
    }

    /**
     * @param $name
     *
     * @return BlockInterface|null
     */
    public function resolveBlockByName($name): ?BlockInterface
    {
        return $this->registry->resolve($name);
    }

    /**
     * @param BlockInterface $block
     *
     * @return ConstructorBlock|Model
     */
    public function buildFromBlock(BlockInterface $block):Model
    {
        $content = $block->resource();

        $model = $this->getRelatedModel();
        $model->name = $block->name();
        $model->content()->associate(
            new $content
        );

        return $model;
    }

    /**
     * @param $model
     *
     * @return FieldInterface|FieldSet
     */
    public function getNestedFieldSet($model)
    {
        return $this->getRelationFieldSet($model, 0);
    }

    /**
     * Make this field sortable
     *
     * @param string $field
     *
     * @return $this
     */
    public function sortable($field = 'position')
    {
        $this->isSortable = true;
        $this->setOrderBy($field);

        return $this;
    }

    /**
     * @param FieldInterface $field
     *
     * @return bool
     */
    protected function isContentField( FieldInterface $field ): bool
    {
        return $field instanceof HasOne && $field->getName() === 'content';
}
}
