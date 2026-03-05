<?php

declare(strict_types=1);

namespace CashierBundle\DependencyInjection;

use CashierBundle\Contract\InvoiceRendererInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class CashierExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $phpLoader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.yaml');
        $phpLoader->load('webhook_services.php');

        if (class_exists('Symfony\Component\Messenger\MessageBusInterface')) {
            $phpLoader->load('messenger_services.php');
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerConfigurationParameters($container, $config);
    }

    private function registerConfigurationParameters(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('cashier.key', $config['key']);
        $container->setParameter('cashier.secret', $config['secret']);
        $container->setParameter('cashier.path', $config['path']);
        $container->setParameter('cashier.webhook.secret', $config['webhook']['secret']);
        $container->setParameter('cashier.webhook.tolerance', $config['webhook']['tolerance']);
        $container->setParameter('cashier.webhook.events', $config['webhook']['events']);
        $container->setParameter('cashier.webhook', $config['webhook']);
        $container->setParameter('cashier.currency', $config['currency']);
        $container->setParameter('cashier.currency_locale', $config['currency_locale']);
        $container->setParameter('cashier.default_subscription_type', $config['default_subscription_type']);
        $container->setParameter('cashier.invoices.renderer', $config['invoices']['renderer']);
        $container->setParameter('cashier.invoices.default_locale', $config['invoices']['default_locale']);
        $container->setParameter('cashier.invoices.supported_locales', $config['invoices']['supported_locales']);
        $container->setParameter('cashier.invoices.storage.driver', $config['invoices']['storage']['driver']);
        $container->setParameter('cashier.invoices.storage.path', $config['invoices']['storage']['path']);
        $container->setParameter('cashier.invoices.options.paper', $config['invoices']['options']['paper']);
        $container->setParameter('cashier.invoices.options.remote_enabled', $config['invoices']['options']['remote_enabled']);
        $container->setParameter('cashier.logger', $config['logger']);

        $container->setAlias(InvoiceRendererInterface::class, $config['invoices']['renderer']);
    }
}
