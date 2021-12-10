<?php

namespace Ivoz\Core\Infrastructure\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RepositoryCompiler implements CompilerPassInterface
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $this->container = $container;

        $this->setRepositoryAliases(
            $this->container->findTaggedServiceIds('domain.repository')
        );
    }

    /**
     * @return void
     */
    protected function setRepositoryAliases(array $services)
    {

        foreach ($services as $fqdn => $value) {
            $repositoryInterface = preg_replace(
                '/(.*)Infrastructure\\\\Persistence\\\\Doctrine\\\\(.*)DoctrineRepository/',
                '${1}Domain\Model\\\\${2}\\\\${2}Repository',
                $fqdn
            );

            $alias = $this->container->setAlias(
                $repositoryInterface,
                $fqdn
            );

            $alias->setPublic(true);
        }
    }
}
