<?php

declare(strict_types=1);

namespace CashierBundle\DependencyInjection;

use CashierBundle\Service\InvoiceRenderer\DompdfInvoiceRenderer;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cashier');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Stripe API key')
                ->end()
                ->scalarNode('secret')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Stripe API secret')
                ->end()
                ->scalarNode('path')
                    ->defaultValue('stripe')
                    ->cannotBeEmpty()
                    ->info('Base path for Stripe routes')
                ->end()
                ->arrayNode('webhook')
                    ->children()
                        ->scalarNode('secret')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('Webhook signing secret for verification')
                        ->end()
                        ->integerNode('tolerance')
                            ->defaultValue(300)
                            ->min(0)
                            ->info('Webhook timestamp tolerance in seconds')
                        ->end()
                        ->arrayNode('events')
                            ->scalarPrototype()
                            ->info('List of webhook events to handle')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('currency')
                    ->defaultValue('usd')
                    ->cannotBeEmpty()
                    ->info('Default currency for payments')
                ->end()
                ->scalarNode('currency_locale')
                    ->defaultValue('en')
                    ->cannotBeEmpty()
                    ->info('Locale for currency formatting')
                ->end()
                ->scalarNode('default_subscription_type')
                    ->defaultValue('default')
                    ->cannotBeEmpty()
                    ->info('Default subscription type for webhook-created subscriptions')
                ->end()
                ->arrayNode('invoices')
                    ->children()
                        ->scalarNode('renderer')
                            ->defaultValue(DompdfInvoiceRenderer::class)
                            ->cannotBeEmpty()
                            ->info('Invoice renderer service class')
                        ->end()
                        ->arrayNode('options')
                            ->children()
                                ->scalarNode('paper')
                                    ->defaultValue('letter')
                                    ->cannotBeEmpty()
                                    ->info('Paper size for invoice PDF')
                                ->end()
                                ->booleanNode('remote_enabled')
                                    ->defaultFalse()
                                    ->info('Enable remote resources in invoice PDF')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('logger')
                    ->defaultNull()
                    ->info('Logger service ID (default: null)')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
