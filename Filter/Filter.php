<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineMongoDBAdminBundle\Filter;

use Sonata\AdminBundle\Filter\Filter as BaseFilter;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

abstract class Filter extends BaseFilter
{
    protected $active = false;
    
    public function apply($queryBuilder, $value)
    {
        $this->value = $value;

        $this->filter($queryBuilder, null, $this->getFieldName(), $value);
    }
    
    /**
     * @param \Sonata\AdminBundle\Datagrid\ProxyQueryInterface $queryBuilder
     * @param mixed                                            $parameter
     */
    protected function applyWhere(ProxyQueryInterface $queryBuilder, $field, $operator, $value)
    {
        $expr = $queryBuilder->expr()->field($field)->$operator($value);
        
        if ($this->getCondition() == self::CONDITION_OR) {
            $queryBuilder->addOr($expr);
        } else {
            $queryBuilder->addAnd($expr);
        }

        // filter is active since it's added to the queryBuilder
        $this->active = true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return $this->active;
    }
    
    
}
