<?php

namespace spec\Pim\Bundle\CatalogBundle\Doctrine\ORM\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Exception\InvalidArgumentException;
use Pim\Component\Catalog\Query\Filter\Operators;
use Prophecy\Argument;

class CompletenessFilterSpec extends ObjectBehavior
{
    function let(QueryBuilder $qb)
    {
        $this->beConstructedWith(
            ['completeness'],
            [
                '=',
                '<',
                '>',
                '>=',
                '<=',
                '!=',
                'GREATER THAN ON ALL LOCALES',
                'GREATER OR EQUALS THAN ON ALL LOCALES',
                'LOWER OR EQUALS THAN ON ALL LOCALES',
                'LOWER THAN ON ALL LOCALES'
            ]
        );
        $this->setQueryBuilder($qb);
    }

    function it_is_a_filter()
    {
        $this->shouldImplement('Pim\Component\Catalog\Query\Filter\FieldFilterInterface');
    }

    function it_supports_operators()
    {
        $this
            ->getOperators()
            ->shouldReturn([
                '=',
                '<',
                '>',
                '>=',
                '<=',
                '!=',
                'GREATER THAN ON ALL LOCALES',
                'GREATER OR EQUALS THAN ON ALL LOCALES',
                'LOWER OR EQUALS THAN ON ALL LOCALES',
                'LOWER THAN ON ALL LOCALES'
            ]);
        $this->supportsOperator('=')->shouldReturn(true);
        $this->supportsOperator('FAKE')->shouldReturn(false);
    }

    function it_returns_supported_fields()
    {
        $this->getFields()->shouldReturn(['completeness']);
    }

    function it_adds_an_equal_filter_on_a_field_in_the_query(
        $qb,
        Expr $expr,
        EntityManager $em,
        ClassMetadata $cm,
        Expr\Comparison $comparison
    ) {
        $qb->expr()->willReturn($expr);
        $qb->getRootAliases()->willReturn(['c']);
        $qb->getRootEntities()->willReturn(['Completeness']);
        $qb->getEntityManager()->willReturn($em);
        $em->getClassMetadata('Completeness')->willReturn($cm);
        $comparison->__toString()->willReturn('filterCompleteness.ratio = 100');
        $cm->getAssociationMapping('completenesses')->willReturn(['targetEntity' => 'test']);
        $expr->literal('100')
            ->willReturn('100');
        $expr->eq(Argument::any(), '100')
            ->willReturn($comparison);

        $qb->leftJoin(
            'PimCatalogBundle:Locale',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalled()->willReturn($qb);

        $qb->leftJoin(
            'PimCatalogBundle:Channel',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalled()->willReturn($qb);

        $qb->leftJoin(
            'test',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalled()->willReturn($qb);

        $qb->setParameter(Argument::any(), Argument::any())->shouldBeCalled();
        $qb->setParameter('cScopeCode', Argument::any())->shouldBeCalled();

        $qb->andWhere('filterCompleteness.ratio = 100')->shouldBeCalled();

        $this->addFieldFilter('completeness', '=', 100, 'en_US', 'ecommerce');
    }

    function it_adds_filters_complete_on_all_locales(
        $qb,
        Expr $expr,
        EntityManager $em,
        ClassMetadata $cm,
        Expr\Comparison $comparison
    ) {
        $qb->expr()->willReturn($expr);
        $qb->getRootAliases()->willReturn(['c']);
        $qb->getRootEntities()->willReturn(['Completeness']);
        $qb->getEntityManager()->willReturn($em);
        $em->getClassMetadata('Completeness')->willReturn($cm);
        $comparison->__toString()->willReturn('filterCompleteness.ratio = 100');
        $cm->getAssociationMapping('completenesses')->willReturn(['targetEntity' => 'test']);
        $expr->literal(100)
            ->willReturn('100');
        $expr->gte(Argument::any(), '100')
            ->shouldBeCalledTimes(2)
            ->willReturn($comparison);

        $qb->leftJoin(
            'PimCatalogBundle:Locale',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalledTimes(2)->willReturn($qb);

        $qb->leftJoin(
            'PimCatalogBundle:Channel',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalledTimes(2)->willReturn($qb);

        $qb->leftJoin(
            'test',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalledTimes(2)->willReturn($qb);

        $qb->setParameter(Argument::any(), Argument::any())->shouldBeCalled();
        $qb->setParameter('cScopeCode', Argument::any())->shouldBeCalled();

        $qb->andWhere('filterCompleteness.ratio = 100')->shouldBeCalledTimes(2);

        $this->addFieldFilter(
            'completeness',
            'GREATER OR EQUALS THAN ON ALL LOCALES',
            100,
            'en_US',
            'mobile',
            ['locales' => ['fr_FR', 'en_US']]
        );
    }

    function it_adds_filters_on_not_complete_on_all_locales(
        $qb,
        Expr $expr,
        EntityManager $em,
        ClassMetadata $cm,
        Expr\Comparison $comparison
    ) {
        $qb->expr()->willReturn($expr);
        $qb->getRootAliases()->willReturn(['c']);
        $qb->getRootEntities()->willReturn(['Completeness']);
        $qb->getEntityManager()->willReturn($em);
        $em->getClassMetadata('Completeness')->willReturn($cm);
        $comparison->__toString()->willReturn('filterCompleteness.ratio < 100');
        $cm->getAssociationMapping('completenesses')->willReturn(['targetEntity' => 'test']);
        $expr
            ->literal(100)
            ->willReturn('100');
        $expr
            ->lt(Argument::any(), '100')
            ->shouldBeCalledTimes(2)
            ->willReturn($comparison);

        $qb->leftJoin(
            'PimCatalogBundle:Locale',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalledTimes(2)->willReturn($qb);

        $qb->leftJoin(
            'PimCatalogBundle:Channel',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalledTimes(2)->willReturn($qb);

        $qb->leftJoin(
            'test',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalledTimes(2)->willReturn($qb);

        $qb->setParameter(Argument::any(), Argument::any())->shouldBeCalled();
        $qb->setParameter('cScopeCode', Argument::any())->shouldBeCalled();

        $qb->andWhere('filterCompleteness.ratio < 100')->shouldBeCalledTimes(2);

        $this->addFieldFilter(
            'completeness',
            'LOWER THAN ON ALL LOCALES',
            100,
            'en_US',
            'mobile',
            ['locales' => ['fr_FR', 'en_US']]
        );
    }

    function it_adds_a_filter_on_a_field_in_the_query(
        $qb,
        Expr $expr,
        EntityManager $em,
        ClassMetadata $cm,
        Expr\Comparison $comparison
    ) {
        $qb->expr()->willReturn($expr);
        $qb->getRootAliases()->willReturn(['c']);
        $qb->getRootEntities()->willReturn(['Completeness']);
        $qb->getEntityManager()->willReturn($em);
        $em->getClassMetadata('Completeness')->willReturn($cm);
        $comparison->__toString()->willReturn('filterCompleteness.ratio < 100');
        $cm->getAssociationMapping('completenesses')->willReturn(['targetEntity' => 'test']);
        $expr->literal('100')
            ->willReturn('100');
        $expr->lt(Argument::any(), '100')
            ->willReturn($comparison);

        $qb->leftJoin(
            'PimCatalogBundle:Locale',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalled()->willReturn($qb);

        $qb->leftJoin(
            'PimCatalogBundle:Channel',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalled()->willReturn($qb);

        $qb->leftJoin(
            'test',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalled()->willReturn($qb);

        $qb->setParameter(Argument::any(), Argument::any())->shouldBeCalled();
        $qb->setParameter('cScopeCode', Argument::any())->shouldBeCalled();

        $qb->andWhere('filterCompleteness.ratio < 100')->shouldBeCalled();

        $this->addFieldFilter('completeness', '<', 100, 'en_US', 'mobile');
    }

    function it_filters_on_completeness_on_any_locale(
        $qb,
        Expr $expr,
        EntityManager $em,
        ClassMetadata $cm,
        Expr\Comparison $comparison
    ) {
        $qb->expr()->willReturn($expr);
        $qb->getRootAliases()->willReturn(['c']);
        $qb->getRootEntities()->willReturn(['Completeness']);
        $qb->getEntityManager()->willReturn($em);
        $em->getClassMetadata('Completeness')->willReturn($cm);
        $comparison->__toString()->willReturn('filterCompleteness.ratio < 100');
        $cm->getAssociationMapping('completenesses')->willReturn(['targetEntity' => 'test']);
        $expr->literal('100')
            ->willReturn('100');
        $expr->lt(Argument::any(), '100')
            ->willReturn($comparison);

        $qb->leftJoin(
            'PimCatalogBundle:Locale',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldNotBeCalled();

        $qb->leftJoin(
            'PimCatalogBundle:Channel',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalled()->willReturn($qb);

        $qb->leftJoin(
            'test',
            Argument::any(),
            'WITH',
            Argument::any()
        )->shouldBeCalled()->willReturn($qb);

        $qb->setParameter('cLocaleCode', Argument::any())->shouldNotBeCalled();
        $qb->setParameter('cScopeCode', Argument::any())->shouldBeCalled();

        $qb->andWhere('filterCompleteness.ratio < 100')->shouldBeCalled();

        $this->addFieldFilter('completeness', '<', 100, null, 'mobile');
    }

    function it_checks_if_field_is_supported()
    {
        $this->supportsField('completeness')->shouldReturn(true);
        $this->supportsField('groups')->shouldReturn(false);
    }

    function it_throws_an_exception_if_value_is_not_a_integer()
    {
        $this->shouldThrow(InvalidArgumentException::numericExpected('completeness', 'filter', 'completeness', gettype('123')))
            ->during('addFieldFilter', ['completeness', '=', '12z3', 'en_US', 'mobile']);
    }

    function it_throws_an_exception_if_options_are_not_set_correctly()
    {
        $this
            ->shouldThrow(
                InvalidArgumentException::arrayKeyExpected(
                    'completeness',
                    'locales',
                    'filter',
                    'completeness',
                    print_r([], true)
                )
            )
            ->during(
                'addFieldFilter',
                [
                    'completeness',
                    'LOWER THAN ON ALL LOCALES',
                    100,
                    null,
                    'mobile',
                    []
                ]
            );

        $this
            ->shouldThrow(
                InvalidArgumentException::arrayOfArraysExpected(
                    'completeness',
                    'filter',
                    'completeness',
                    print_r(['locales' => 'fr_FR'], true)
                )
            )
            ->during(
                'addFieldFilter',
                [
                    'completeness',
                    'LOWER THAN ON ALL LOCALES',
                    100,
                    null,
                    'mobile',
                    ['locales' => 'fr_FR']
                ]
            );
    }
}
