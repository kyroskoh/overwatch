<?php

namespace Overwatch\ServiceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('overwatch_service');

        $rootNode
            ->children()
                ->arrayNode('to_ping')
                    ->children()
                        ->floatNode('timeout')
                            ->info('Time, in seconds, to wait for a ping response before timing out and marking as unmet')
                            ->defaultValue(2)
                            ->min(0)
                        ->end()
                        ->floatNode('unsatisfactory')
                            ->info('Time, in seconds, to wait for a ping response before marking as unsatisfactory')
                            ->defaultValue(1)
                            ->min(0)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('to_resolve_to')
                    ->children()
                        ->variableNode('record_types')
                            ->info('Array of record types to look at when resolving')
                            ->defaultValue(['A', 'AAAA', 'CNAME'])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('to_respond_http')
                    ->children()
                        ->variableNode('allowable_codes')
                            ->info('Array of acceptable HTTP codes')
                            ->defaultValue([200, 201, 204, 206, 304])
                        ->end()
                        ->variableNode('unsatisfactory_codes')
                            ->info('Array of unsatisfactory HTTP codes')
                            ->defaultValue([301, 302, 307, 308])
                        ->end()
                        ->floatNode('timeout')
                            ->info('Time, in seconds, to wait for a HTTP response before timing out. Use 0 for no timeout.')
                            ->defaultValue(10)
                            ->min(0)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('to_respond_with_mime_type')
                    ->children()
                        ->booleanNode('allow_errors')
                            ->info('If true, HTTP errors will be ignored and the MIME type of the response will still be checked.')
                            ->defaultFalse()
                        ->end()
                        ->floatNode('timeout')
                            ->info('Time, in seconds, to wait for a HTTP response before timing out. Use 0 for no timeout.')
                            ->defaultValue(10)
                            ->min(0)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('to_contain_text')
                    ->children()
                        ->booleanNode('allow_errors')
                            ->info('If true, HTTP errors will be ignored and the response will still be checked.')
                            ->defaultFalse()
                        ->end()
                        ->variableNode('crawlable_types')
                            ->info('Array of MIME types that will be parsed by the Symfony DomCrawler')
                            ->defaultValue(['text/html', 'text/xml', 'application/xml', 'text/rss+xml', 'application/rss+xml', 'application/rdf+xml', 'application/atom+xml'])
                        ->end()
                        ->floatNode('timeout')
                            ->info('Time, in seconds, to wait for a HTTP response before timing out. Use 0 for no timeout.')
                            ->defaultValue(10)
                            ->min(0)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('email_reporter')
                    ->children()
                        ->booleanNode('enabled')
                            ->info('Send email reports when the results of a test change')
                            ->defaultTrue()
                        ->end()
                        ->variableNode('report_from')
                            ->info('E-mail address to send reports from')
                            ->defaultValue('overwatch@example.com')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('sms_reporter')
                    ->children()
                        ->booleanNode('enabled')
                            ->info('Send sms reports when the results of a test change')
                            ->defaultFalse()
                        ->end()
                        ->variableNode('twilio_account_sid')
                            ->info('Twilio AccountSid from https://www.twilio.com/user/account')
                            ->defaultValue('ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX')
                        ->end()
                        ->variableNode('twilio_auth_token')
                            ->info('Twilio AuthToken from https://www.twilio.com/user/account')
                            ->defaultValue('YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY')
                        ->end()
                        ->variableNode('twilio_from_number')
                            ->info('Twilio number the account specified has been assigned to send notifications from')
                            ->defaultValue('+CCXXXXXXXXXX')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
