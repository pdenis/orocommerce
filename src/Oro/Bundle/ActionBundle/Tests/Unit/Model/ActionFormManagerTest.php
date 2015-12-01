<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionContext;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\ActionBundle\Model\ActionFormManager;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Model\ContextHelper;

class ActionFormManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|Action */
    protected $action;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface */
    protected $formFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionManager */
    protected $actionManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper */
    protected $contextHelper;

    /** @var ActionFormManager */
    protected $manager;

    protected function setUp()
    {
        $this->action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->actionManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ActionFormManager($this->formFactory, $this->actionManager, $this->contextHelper);
    }

    protected function tearDown()
    {
        unset($this->manager, $this->formFactory, $this->actionManager, $this->contextHelper, $this->action);
    }

    public function testGetActionForm()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionDefinition $definition */
        $definition = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('getFormType')
            ->willReturn('form_type');

        $this->action->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);
        $this->action->expects($this->once())
            ->method('getFormOptions')
            ->willReturn(['some_option' => 'option_value']);

        $this->actionManager->expects($this->once())
            ->method('getAction')
            ->willReturnCallback(function ($actionName) {
                $this->action->expects($this->any())
                    ->method('getName')
                    ->willReturn($actionName);

                return $this->action;
            });

        $context = new ActionContext(['data' => ['param']]);

        $this->contextHelper->expects($this->once())
            ->method('getActionContext')
            ->willReturn($context);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                'form_type',
                $context,
                [
                    'some_option' => 'option_value',
                    'action_context' => $context,
                    'action' => $this->action
                ]
            )
            ->willReturn($form);

        $this->assertSame($form, $this->manager->getActionForm($context));
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\ActionNotFoundException
     * @expectedExceptionMessage Action with name "test" not found
     */
    public function testGetActionFormException()
    {
        $this->actionManager->expects($this->once())
            ->method('getAction')
            ->willReturn(null);

        $this->manager->getActionForm('test');
    }
}
