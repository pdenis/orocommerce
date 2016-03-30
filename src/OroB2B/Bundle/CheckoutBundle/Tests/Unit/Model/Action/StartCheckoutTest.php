<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Doctrine\ORM\EntityRepository;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class StartCheckoutTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    /**
     * @var UserCurrencyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyProvider;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * @var AbstractAction|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $redirect;

    /**
     * @var  StartCheckout
     */
    protected $action;

    /**
     * @var  PropertyAccessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $propertyAccessor;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    public function setUp()
    {
        $this->registry = $this->getMockWithoutConstructor('Symfony\Bridge\Doctrine\ManagerRegistry');
        $this->websiteManager = $this->getMockWithoutConstructor('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager');
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->currencyProvider = $this
            ->getMockWithoutConstructor('OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider');
        $this->redirect = $this->getMockBuilder('Oro\Component\Action\Action\AbstractAction')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'execute'])
            ->getMockForAbstractClass();
        $this->propertyAccessor = $this
            ->getMockWithoutConstructor('Symfony\Component\PropertyAccess\PropertyAccessor');

        $this->action = new StartCheckout(
            new ContextAccessor(),
            $this->registry,
            $this->websiteManager,
            $this->currencyProvider,
            $this->tokenStorage,
            $this->propertyAccessor,
            $this->redirect
        );

        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->action->setDispatcher($this->eventDispatcher);
        $this->action->setCheckoutRoute('orob2b_checkout_frontend_checkout');
    }

    public function testInitialize()
    {
        $options = [StartCheckout::SOURCE_FIELD_KEY => 'source', StartCheckout::SOURCE_ENTITY_KEY => new \stdClass()];
        $this->assertEquals($this->action, $this->action->initialize($options));
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     */
    public function testException()
    {
        $this->action->initialize([]);
    }

    /**
     * @dataProvider executeActionDataProvider
     * @param array $options
     * @param CheckoutSource|null $checkoutSource
     */
    public function testExecute(array $options, CheckoutSource $checkoutSource = null)
    {
        $checkout = new Checkout();
        $checkout->setWorkflowItem(new WorkflowItem());
        $entity = new ShoppingList();
        $context = new ActionData(['data' => $entity]);

        $this->action->initialize($options);

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $checkoutRepository */
        $checkoutRepository = $this->getMockWithoutConstructor('Doctrine\ORM\EntityRepository');
        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $checkoutRepository */
        $checkoutSourceRepository = $this->getMockWithoutConstructor('Doctrine\ORM\EntityRepository');
        $checkoutSourceRepository->expects($this->any())
            ->method('findOneBy')
            ->with([$options[StartCheckout::SOURCE_FIELD_KEY] => $options[StartCheckout::SOURCE_ENTITY_KEY]])
            ->willReturn($checkoutSource);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    ['OroB2BCheckoutBundle:Checkout', $checkoutRepository],
                    ['OroB2BCheckoutBundle:CheckoutSource', $checkoutSourceRepository]
                ]
            );

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        if (!$checkoutSource) {
            $account = new Account();
            $account->setOwner(new User());
            $account->setOrganization(new Organization());
            $user = new AccountUser();
            $user->setAccount($account);

            /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
            $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
            $token->expects($this->once())->method('getUser')->willReturn($user);
            $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $this->propertyAccessor
                ->expects($this->once())
                ->method('setValue')
                ->willReturnCallback(
                    function ($entity, $key, $value) use ($options, $propertyAccessor) {
                        if ($entity instanceof CheckoutSource) {
                            \PHPUnit_Framework_Assert::assertEquals($key, $options[StartCheckout::SOURCE_FIELD_KEY]);
                            \PHPUnit_Framework_Assert::assertEquals($value, $options[StartCheckout::SOURCE_ENTITY_KEY]);
                        } else {
                            $propertyAccessor->setValue($entity, $key, $value);
                        }
                    }
                );

            $em->expects($this->once())
                ->method('persist')
                ->with($this->isInstanceOf('OroB2B\Bundle\CheckoutBundle\Entity\Checkout'))
                ->willReturnCallback(
                    function (Checkout $entity) {
                        $entity->setWorkflowItem(new WorkflowItem());
                    }
                );
            $em->expects($this->exactly(2))->method('flush');
        } else {
            $checkoutRepository
                ->expects($this->once())
                ->method('findOneBy')
                ->with(['source' => $checkoutSource])
                ->willReturn($checkout);
        }
        $this->redirect
            ->expects($this->once())
            ->method('initialize')
            ->with(
                [
                    'route' => 'orob2b_checkout_frontend_checkout',
                    'route_parameters' => ['id' => $checkout->getId()]
                ]
            );
        $this->redirect->expects($this->once())
            ->method('execute')
            ->with($context);

        $this->action->execute($context);
    }

    /**
     * /**
     * @return array
     */
    public function executeActionDataProvider()
    {
        return [
            'without_checkout_source' => [
                'options' => [
                    StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                    StartCheckout::SOURCE_ENTITY_KEY => new ShoppingList(),
                    StartCheckout::CHECKOUT_DATA_KEY => [
                        'poNumber' => 123
                    ],
                    StartCheckout::SETTINGS_KEY => [
                        'allow_source_remove' => true,
                        'disallow_billing_address_edit' => false,
                        'disallow_shipping_address_edit' => false,
                        'remove_source' => true
                    ]
                ],
                'checkoutSource' => null
            ],
            'without_checkout_source minimal' => [
                'options' => [
                    StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                    StartCheckout::SOURCE_ENTITY_KEY => new ShoppingList()
                ],
                'checkoutSource' => null
            ],
            'with_checkout_source' => [
                'options' => [
                    StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                    StartCheckout::SOURCE_ENTITY_KEY => new ShoppingList(),
                    StartCheckout::CHECKOUT_DATA_KEY => [
                        'poNumber' => 123
                    ],
                    StartCheckout::SETTINGS_KEY  => [
                        'allow_source_remove' => true,
                        'disallow_billing_address_edit' => false,
                        'disallow_shipping_address_edit' => false,
                        'remove_source' => true
                    ]
                ],
                'checkoutSource' => new CheckoutSourceStub()
            ]
        ];
    }

    /**
     * @param $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockWithoutConstructor($className)
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }
}