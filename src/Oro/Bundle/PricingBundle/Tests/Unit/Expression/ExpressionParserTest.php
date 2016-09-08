<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression;

use Oro\Bundle\PricingBundle\Expression;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class ExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Expression\ExpressionParser
     */
    protected $expressionParser;

    /**
     * @var PriceRuleFieldsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    protected function setUp()
    {
        $this->fieldsProvider = $this->getMockBuilder(PriceRuleFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->expressionParser = new Expression\ExpressionParser(
            new Expression\ExpressionLanguageConverter($this->fieldsProvider)
        );
        $this->expressionParser->addNameMapping('PriceList', 'pl');
        $this->expressionParser->addNameMapping('Product', 'p');
    }

    public function testGetNamesMapping()
    {
        $this->assertEquals(
            [
                'PriceList' => 'pl',
                'Product' => 'p'
            ],
            $this->expressionParser->getNamesMapping()
        );
    }

    public function testGetReverseNameMapping()
    {
        $this->assertEquals(
            [
                'pl' => 'PriceList',
                'p' => 'Product'
            ],
            $this->expressionParser->getReverseNameMapping()
        );
    }

    /**
     * @dataProvider invalidExpressionDataProvider
     * @param string $expression
     * @param string $exceptionMessage
     */
    public function testParseException($expression, $exceptionMessage)
    {
        $this->setExpectedException(SyntaxError::class, $exceptionMessage);
        $this->expressionParser->parse($expression);
    }

    /**
     * @return array
     */
    public function invalidExpressionDataProvider()
    {
        return [
            [
                'PriceList.value[1]', 'Attribute is supported only for root variable in expression'
            ],
            [
                'PriceList.relation.value[1]', 'Attribute is supported only for root variable in expression'
            ],
            [
                'PriceList.relation[1].value', 'Attribute is supported only for root variable in expression'
            ],
            [
                'PriceList.category.relation.id', 'Relations of related entities are not allowed to be used'
            ],
            [
                'PriceList.value(1)', 'Function calls are not supported'
            ]
        ];
    }

    /**
     * @dataProvider expressionsDataProvider
     * @param string $expression
     * @param Expression\NodeInterface $expected
     */
    public function testParse($expression, Expression\NodeInterface $expected)
    {
        $this->prepareCategoryRelation();
        $this->assertEquals($expected, $this->expressionParser->parse($expression));
    }

    /**
     * @return array
     */
    public function expressionsDataProvider()
    {
        return [
            [
                'PriceList[42].prices.currency',
                new Expression\RelationNode('pl', 'prices', 'currency', 42)
            ],
            [
                'PriceList[42].assignedProducts',
                new Expression\NameNode('pl', 'assignedProducts', 42)
            ],
            [
                "(PriceList.currency == 'USD' and Product.margin * 10 > 130*Product.category.minMargin)" .
                " || (Product.category == -Product.MSRP and not (Product.MSRP.currency matches 'U'))",
                new Expression\BinaryNode(
                    new Expression\BinaryNode(
                        new Expression\BinaryNode(
                            new Expression\NameNode('pl', 'currency'),
                            new Expression\ValueNode('USD'),
                            '=='
                        ),
                        new Expression\BinaryNode(
                            new Expression\BinaryNode(
                                new Expression\NameNode('p', 'margin'),
                                new Expression\ValueNode(10),
                                '*'
                            ),
                            new Expression\BinaryNode(
                                new Expression\ValueNode(130),
                                new Expression\RelationNode('p', 'category', 'minMargin'),
                                '*'
                            ),
                            '>'
                        ),
                        'and'
                    ),
                    new Expression\BinaryNode(
                        new Expression\BinaryNode(
                            new Expression\RelationNode('p', 'category', 'categoryId'),
                            new Expression\UnaryNode(
                                new Expression\NameNode('p', 'MSRP'),
                                '-'
                            ),
                            '=='
                        ),
                        new Expression\UnaryNode(
                            new Expression\BinaryNode(
                                new Expression\RelationNode('p', 'MSRP', 'currency'),
                                new Expression\ValueNode('U'),
                                'matches'
                            ),
                            'not'
                        ),
                        'and'
                    ),
                    '||'
                )
            ]
        ];
    }

    /**
     * @dataProvider lexemeExpressionsDataProvider
     * @param string $expression
     * @param array $expected
     */
    public function testGetUsedLexemes($expression, array $expected)
    {
        $this->prepareCategoryRelation();
        $this->assertEquals($expected, $this->expressionParser->getUsedLexemes($expression));
    }

    /**
     * @return array
     */
    public function lexemeExpressionsDataProvider()
    {
        return [
            [
                "(PriceList.currency == 'USD' and Product.margin * 10 > 130*Product.category.minMargin)" .
                " || (Product.category == -Product.someValue and not (Product.MSRP.currency matches 'U'))",
                [
                    'pl' => ['currency'],
                    'p' => ['margin', 'someValue'],
                    'p::MSRP' => ['currency'],
                    'p::category' => ['minMargin', 'categoryId']
                ]
            ],
            [
                '1+1',
                []
            ]
        ];
    }

    /**
     * @dataProvider getUsedLexemesConsideringContainerIdDataProvider
     * @param string $expression
     * @param array $expected
     */
    public function testGetUsedLexemesConsideringContainerId($expression, array $expected)
    {
        $this->prepareCategoryRelation();
        $this->assertEquals($expected, $this->expressionParser->getUsedLexemesConsideringContainerId($expression));
    }

    /**
     * @return array
     */
    public function getUsedLexemesConsideringContainerIdDataProvider()
    {
        return [
            [
                "(PriceList.currency == 'USD' and Product.margin * 10 > 130*Product.category.minMargin)" .
                " || (Product.category == -Product.someValue and not (Product.MSRP.currency matches 'U'))",
                [
                    'pl' => [null => ['currency']],
                    'p' => [null => ['margin', 'someValue']],
                    'p::MSRP' => [null => ['currency']],
                    'p::category' => [null => ['minMargin', 'categoryId']]
                ]
            ],
        ];
    }

    protected function prepareCategoryRelation()
    {
        $this->fieldsProvider->expects($this->any())
            ->method('isRelation')
            ->willReturnMap(
                [
                    ['p', 'category', true]
                ]
            );
        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->with('p', 'category')
            ->willReturn('Category');
        $this->fieldsProvider->expects($this->any())
            ->method('getIdentityFieldName')
            ->with('Category')
            ->willReturn('categoryId');
    }
}
