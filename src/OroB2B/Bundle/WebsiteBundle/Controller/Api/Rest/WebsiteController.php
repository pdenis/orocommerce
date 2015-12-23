<?php

namespace OroB2B\Bundle\WebsiteBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @NamePrefix("orob2b_api_")
 */
class WebsiteController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete website",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_website_delete",
     *      type="entity",
     *      class="OroB2BWebsiteBundle:Website",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }
    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_website.website.manager.api');
    }
    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }
    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}