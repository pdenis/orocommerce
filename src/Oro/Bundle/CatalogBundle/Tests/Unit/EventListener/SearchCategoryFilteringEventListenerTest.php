<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\SearchCategoryFilteringEventListener;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQuery;
use Oro\Bundle\SearchBundle\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

class SearchCategoryFilteringEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestProductHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestProductHandler;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    protected function setUp()
    {
        $this->requestProductHandler = $this->getMockBuilder(RequestProductHandler::class)
            ->setMethods(['getCategoryId', 'getIncludeSubcategoriesChoice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMock();
    }

    public function testAddsSingleCategoryToQuery()
    {
        $categoryId = 23;

        $this->requestProductHandler
            ->method('getCategoryId')
            ->willReturn($categoryId);

        $this->requestProductHandler
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(false);

        $listener = new SearchCategoryFilteringEventListener(
            $this->requestProductHandler,
            $this->doctrine
        );

        /** @var BuildAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(BuildAfter::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var SearchDatasource|\PHPUnit_Framework_MockObject_MockObject $searchDataSource */
        $datasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var SearchQuery|\PHPUnit_Framework_MockObject_MockObject $searchQuery */
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var WebsiteSearchQuery|\PHPUnit_Framework_MockObject_MockObject $websiteSearchQuery */
        $websiteSearchQuery = $this->getMockBuilder(WebsiteSearchQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteSearchQuery->method('getQuery')
            ->will($this->returnValue($query));

        $dataGrid = $this->getMock(DatagridInterface::class);

        $event->method('getDatagrid')
            ->willReturn($dataGrid);

        $dataGrid->method('getDatasource')
            ->willReturn($datasource);

        $datasource->method('getQuery')
            ->willReturn($websiteSearchQuery);

        $query->expects($this->once())
            ->method('andWhere')
            ->with('cat_id', Query::OPERATOR_EQUALS, $categoryId, 'integer');

        $listener->onBuildAfter($event);
    }

    public function testAddsMultipleCategoriesToQuery()
    {
        $categoryId = 11;
        $subcategoryIds = [1,2,6,10];

        $this->requestProductHandler
            ->method('getCategoryId')
            ->willReturn($categoryId);

        $this->requestProductHandler
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(true);

        $mockedRepo = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()->getMock();

        $category = new Category();

        $mockedRepo->method('find')
            ->with($categoryId)->willReturn($category);

        $mockedRepo->method('getChildrenIds')
            ->with($category)->willReturn($subcategoryIds);

        $this->doctrine->method('getRepository')
            ->willReturn($mockedRepo);

        $listener = new SearchCategoryFilteringEventListener(
            $this->requestProductHandler,
            $this->doctrine
        );

        /** @var BuildAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(BuildAfter::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var SearchDatasource|\PHPUnit_Framework_MockObject_MockObject $searchDataSource */
        $datasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var SearchQuery|\PHPUnit_Framework_MockObject_MockObject $searchQuery */
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var WebsiteSearchQuery|\PHPUnit_Framework_MockObject_MockObject $websiteSearchQuery */
        $websiteSearchQuery = $this->getMockBuilder(WebsiteSearchQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteSearchQuery->method('getQuery')
            ->will($this->returnValue($query));

        $dataGrid = $this->getMock(DatagridInterface::class);

        $event->method('getDatagrid')
            ->willReturn($dataGrid);

        $dataGrid->method('getDatasource')
            ->willReturn($datasource);

        $datasource->method('getQuery')
            ->willReturn($websiteSearchQuery);

        $categories = $subcategoryIds;
        $categories[] = $categoryId;

        $query->expects($this->once())
            ->method('andWhere')
            ->with('cat_id', Query::OPERATOR_IN, $categories, 'integer');

        $listener->onBuildAfter($event);
    }
}
