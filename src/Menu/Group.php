<?php

namespace CubeSystems\Leaf\Menu;

use CubeSystems\Leaf\Html\Elements;
use CubeSystems\Leaf\Html\Html;
use Illuminate\Support\Collection;

class Group extends AbstractItem
{
    /**
     * @var Collection
     */
    protected $children;

    /**
     * Group constructor.
     */
    public function __construct()
    {
        $this->children = new Collection();
    }

    /**
     * @param Elements\Element $parentElement
     * @return Elements\Element
     */
    public function render( Elements\Element $parentElement ): Elements\Element
    {
        $ul = Html::ul()->addClass( 'block' );

        foreach( $this->getChildren() as $child )
        {
            /** @var AbstractItem $child */
            $li = Html::li()->addAttributes( [ 'data-name' => '' ] );

            if( $child->isAccessible() )
            {
                $child->render( $li );

                if( $child->isActive() )
                {
                    $li->addClass( 'active' );
                }

                $ul->append( $li );
            }
        }

        return
            $parentElement
                ->append(
                    Html::span( [
                        Html::abbr( $this->getAbbreviation() )->addAttributes( [ 'title' => $this->getTitle() ] ),
                        Html::span( $this->getTitle() )->addClass( 'name' ),
                        Html::span( Html::button( Html::i()->addClass( 'fa fa-chevron-up' ) )->addAttributes( [ 'type' => 'button' ] ) )->addClass( 'collapser' ),
                    ] )->addClass( 'trigger ' . ( $this->isActive() ? 'active' : '' ) )
                )
                ->append( $ul );
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getChildren()->first( function( Item $item )
        {
            return $item->isActive();
        } );
    }

    /**
     * @param AbstractItem $child
     * @return void
     */
    public function addChild( AbstractItem $child )
    {
        $this->children->push( $child );
    }

    /**
     * @return Collection
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param Collection $children
     */
    public function setChildren( Collection $children )
    {
        $this->children = $children;
    }

    /**
     * @return bool
     */
    public function isAccessible(): bool
    {
        return $this->getChildren()->reject( function( AbstractItem $item )
        {
            return $item->isAccessible();
        } )->isEmpty();
    }
}
