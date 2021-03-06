<?php

namespace Overwatch\ServiceBundle\Reporter;

use Overwatch\ResultBundle\Entity\TestResult;
use Overwatch\ResultBundle\Reporter\ResultReporterInterface;

/**
 * EmailReporter
 */
class EmailReporter implements ResultReporterInterface
{
    private $container;
    private $config;
    
    public function __construct($container, $config)
    {
        $this->container = $container;
        $this->config = $config;
    }
    
    public function notify(TestResult $result)
    {
        if ($this->config['enabled'] === false) {
            return;
        }
        
        $recipients = [];
        
        foreach ($result->getTest()->getGroup()->getUsers() as $user) {
            if ($user->shouldBeAlerted($result)) {
                $recipients[] = $user->getEmail();
            }
        }
        
        $this->sendEmail($result, $recipients);
    }
    
    private function sendEmail(TestResult $result, array $users)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($result->getTest()->getName() . ' ' . $result->getStatus())
            ->setFrom($this->config['report_from'])
            ->setTo($users)
            ->setBody(
                $this->container->get('templating')->render(
                    'OverwatchServiceBundle:Email:result.txt.twig',
                    ['result' => $result]
                ),
                'text\plain'
            )
        ;
        
        $this->container->get('mailer')->send($message);
    }
}
