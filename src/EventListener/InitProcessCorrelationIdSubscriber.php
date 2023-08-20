<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\EventListener;

use Pinai4\ProcessCorrelationIdBundle\Monolog\ProcessCorrelationIdProcessor;
use Pinai4\ProcessCorrelationIdBundle\ProcessCorrelationId;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;

class InitProcessCorrelationIdSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ProcessCorrelationId $processCorrelationId,
        private ProcessCorrelationIdProcessor $loggerProcessor
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() && !$this->processCorrelationId->isGenerated()) {
            $this->processCorrelationId->generate('R-');
            $this->loggerProcessor->setInitialProcessCorrelationId($this->processCorrelationId->get());
        }
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if ($event->getCommand()?->getName() !== ConsumeMessagesCommand::getDefaultName()
            && !$this->processCorrelationId->isGenerated()) {
            $this->processCorrelationId->generate('C-');
            $this->loggerProcessor->setInitialProcessCorrelationId($this->processCorrelationId->get());
        }
    }
}
