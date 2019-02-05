<?php


namespace Arbory\Base\Admin\Form\Fields\Renderer\Styles;


use Arbory\Base\Admin\Form\Fields\ControlFieldInterface;
use Arbory\Base\Admin\Form\Fields\FieldInterface;
use Arbory\Base\Admin\Form\Fields\Renderer\RendererInterface;
use Arbory\Base\Admin\Form\Fields\Renderer\Styles\Options\StyleOptionsInterface;
use Arbory\Base\Html\Html;

class LabeledFieldStyle extends AbstractFieldStyle implements FieldStyleInterface
{
    public function render(RendererInterface $renderer, StyleOptionsInterface $options)
    {
        $field = $renderer->getField();

        $inputId  = $field->getFieldId();
        $template = Html::div()->addClass('field');
        $template->addClass(implode(' ', $field->getFieldClasses()));

        $template->addAttributes($options->getAttributes());
        $template->addClass(implode(' ', $options->getClasses()));

        if ( $name = $field->getName() ) {
            $template->addAttributes(
                [
                    'data-name' => $name,
                ]
            );
        }

        $template->append($this->buildLabel($field, $inputId));
        $template->append(Html::div($this->renderField($field))->addClass('value'));


        return $template;
    }

    protected function buildLabel( FieldInterface $field, $inputId )
    {
        $element = Html::div()->addClass('label-wrap');

        $label = Html::label($field->getLabel())
                     ->addAttributes([ 'for' => $inputId ]);

        $element->append($label);

        if ( $field instanceof ControlFieldInterface && $field->getRequired() ) {
            $label->prepend(Html::span('*')->addClass('required-form-field'));
        }

        if ( $info = $field->getInfoBlock() ) {
            $element->append(
                Html::abbr(' ?')->addAttributes([ 'title' => $info ])
            );
        }

        return $element;
    }
}