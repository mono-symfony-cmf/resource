<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Resource\Description\Enhancer\Sylius;

use Sylius\Component\Resource\Metadata\RegistryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Cmf\Component\Resource\Description\DescriptionEnhancerInterface;
use Symfony\Cmf\Component\Resource\Description\Description;
use Puli\Repository\Api\Resource\PuliResource;
use Symfony\Cmf\Component\Resource\Repository\Resource\CmfResource;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Cmf\Component\Resource\Description\Descriptor;

/**
 * Add descriptors from the Sylius Resource component.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ResourceEnhancer implements DescriptionEnhancerInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var RequestConfigurationFactory
     */
    private $requestConfigurationFactory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        RegistryInterface $registry,
        RequestStack $requestStack,
        RequestConfigurationFactory $requestConfigurationFactory,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->registry = $registry;
        $this->urlGenerator = $urlGenerator;
        $this->requestConfigurationFactory = $requestConfigurationFactory;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function enhance(Description $description)
    {
        $metadata = $this->registry->getByClass($description->getResource()->getPayloadType());
        $payload = $description->getResource()->getPayload();

        // the request configuration provides the route names.
        $request = $this->requestStack->getCurrentRequest();
        $configuration = $this->requestConfigurationFactory->create($metadata, $request);

        $map = [
            Descriptor::LINK_SHOW_HTML => 'show',
            Descriptor::LINK_LIST_HTML => 'index',
            Descriptor::LINK_EDIT_HTML => 'update',
            Descriptor::LINK_CREATE_HTML => 'create',
            Descriptor::LINK_REMOVE_HTML => 'delete',
        ];

        foreach ($map as $descriptor => $action) {
            $url = $this->urlGenerator->generate(
                $configuration->getRouteName($action),
                [
                    'id' => $payload->getId(),
                ]
            );
            $description->set($descriptor, $url);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(PuliResource $resource)
    {
        if (false === $resource instanceof CmfResource) {
            return false;
        }

        try {
            $metadata = $this->registry->getByClass($resource->getPayloadType());
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    private function getRouteName(Metadata $metadata, $action)
    {
        return sprintf('%s_%s_%s', $metadata->getApplicationName(), $metadata->getName(), $action);
    }
}
