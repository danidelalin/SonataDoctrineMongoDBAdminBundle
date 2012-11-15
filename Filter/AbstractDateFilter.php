<?php

namespace Sonata\DoctrineMongoDBAdminBundle\Filter;

use Sonata\AdminBundle\Form\Type\Filter\DateType;
use Sonata\AdminBundle\Form\Type\Filter\DateRangeType;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;

abstract class AbstractDateFilter extends Filter
{
    /**
     * Flag indicating that filter will have range
     * @var boolean
     */
    protected $range = false;

    /**
     * Flag indicating that filter will filter by datetime instead by date
     * @var boolean
     */
    protected $time = false;

    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        //check data sanity
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        if ($this->range) {
            //additional data check for ranged items
            if (!array_key_exists('start', $data['value']) || !array_key_exists('end', $data['value'])) {
                return;
            }

            if (!$data['value']['start'] || !$data['value']['end']) {
                return;
            }

            //transform types
            if ($this->getOption('input_type') == 'timestamp') {
                $data['value']['start'] = $data['value']['start'] instanceof \DateTime ? $data['value']['start']->getTimestamp() : 0;
                $data['value']['end'] = $data['value']['end'] instanceof \DateTime ? $data['value']['end']->getTimestamp() : 0;
            }

            //default type for range filter
            $data['type'] = !isset($data['type']) || !is_numeric($data['type']) ?  DateRangeType::TYPE_BETWEEN : $data['type'];

            $startDate = $data['value']['start'];
            $endDate = $data['value']['end'];

            if ($data['type'] == DateRangeType::TYPE_NOT_BETWEEN) {
                $this->setCondition(self::CONDITION_OR);
                $this->applyWhere($queryBuilder, $field, $this->getOperator(DateType::TYPE_LESS_THAN) ,$startDate);
                $this->applyWhere($queryBuilder, $field, $this->getOperator(DateType::TYPE_GREATER_THAN), $endDate);
                
            } else {
                $this->applyWhere($queryBuilder, $field, $this->getOperator(DateType::TYPE_GREATER_EQUAL) ,$startDate);
                $this->applyWhere($queryBuilder, $field, $this->getOperator(DateType::TYPE_LESS_EQUAL), $endDate);
            }

        } else {

            if (!$data['value']) {
                return;
            }

            //default type for simple filter
            $data['type'] = !isset($data['type']) || !is_numeric($data['type']) ? DateType::TYPE_EQUAL : $data['type'];


            //transform types
            if ($this->getOption('input_type') == 'timestamp') {
                $data['value'] = $data['value'] instanceof \DateTime ? $data['value']->getTimestamp() : 0;
            }

            //null / not null only check for col
            if ($data['type'] == DateType::TYPE_NULL) {
                $this->applyWhere($queryBuilder, $field, 'exist' , false);
                
            } elseif($data['type'] == DateType::TYPE_NOT_NULL) {
                $this->applyWhere($queryBuilder, $field, 'exist' , true);
                
            } else {
                //just find an operator and apply query
                $operator = $this->getOperator($data['type']);
                $this->applyWhere($queryBuilder, $field, $operator ,$data['value']);
            }
        }
    }
    
    /**
     * Resolves DataType:: constants to SQL operators
     *
     * @param integer $type
     *
     * @return string
     */
    protected function getOperator($type)
    {
        $type = intval($type);

        $choices = array(
            DateType::TYPE_EQUAL            => 'equals',
            DateType::TYPE_GREATER_EQUAL    => 'gte',
            DateType::TYPE_GREATER_THAN     => 'gt',
            DateType::TYPE_LESS_EQUAL       => 'lte',
            DateType::TYPE_LESS_THAN        => 'lt',
        );

        return isset($choices[$type]) ? $choices[$type] : '=';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array(
            'input_type' => 'datetime'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        $name = 'sonata_type_filter_date';

        if ($this->time) {
            $name .= 'time';
        }

        if ($this->range) {
            $name .= '_range';
        }

        return array($name, array(
            'field_type'    => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label'         => $this->getLabel(),
        ));
    }
}
