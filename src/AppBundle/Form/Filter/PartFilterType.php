<?php

namespace AppBundle\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderExecuterInterface;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('search', Filters\TextFilterType::class, [
            'apply_filter' => [$this, 'partSearchCallback'],
            'label' => 'filter.part.search',
        ]);

        $builder->add('category', CategoryFilterType::class, [
            'add_shared' => function (FilterBuilderExecuterInterface $builderExecuter) {
                $builderExecuter->addOnce($builderExecuter->getAlias().'.category', 'c', function (QueryBuilder $filterBuilder, $alias, $joinAlias, $expr) {
                    $filterBuilder->leftJoin($alias.'.category', $joinAlias);
                });
            },
        ]);
    }

    public function getBlockPrefix()
    {
        return 'part_filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'], // avoid NotBlank() constraint-related message
        ]);
    }

    public function partSearchCallback(QueryInterface $filterQuery, $field, $values)
    {
        if (empty($values['value'])) {
            return null;
        }

        // expression that represent the condition
        $expression = $filterQuery->getExpr()->orX(
            $filterQuery->getExpr()->like('part.number', ':value'),
            $filterQuery->getExpr()->like('part.name', ':value')
        );

        return $filterQuery->createCondition($expression, ['value' => '%'.$values['value'].'%']);
    }
}
