<?php

namespace Arbory\Base\Admin\Form\Fields\Renderer;

use Arbory\Base\Html\Elements\Content;
use Arbory\Base\Html\Elements\Element;
use Arbory\Base\Admin\Form\Fields\FieldInterface;
use Arbory\Base\Admin\Form\Fields\Renderer\Styles\Options\StyleOptionsInterface;

interface RendererInterface
{
    /**
     * Get the evaluated contents of the object.
     *
     * @return Element|Content
     */
    public function render();

    /**
     * @param FieldInterface $field
     *
     * @return mixed
     */
    public function setField(FieldInterface $field): self;

    /**
     * @return FieldInterface
     */
    public function getField(): FieldInterface;

    /**
     * Configure the style before rendering the field.
     *
     * @param StyleOptionsInterface $options
     *
     * @return StyleOptionsInterface
     */
    public function configure(StyleOptionsInterface $options): StyleOptionsInterface;
}
