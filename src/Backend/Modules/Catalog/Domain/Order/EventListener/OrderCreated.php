<?php

namespace Backend\Modules\Catalog\Domain\Order\EventListener;

use Backend\Core\Engine\Model;
use Backend\Modules\Catalog\Domain\Order\Event\OrderCreated as OrderCreatedEvent;
use Backend\Modules\Catalog\Domain\Order\Event\OrderGenerateInvoiceNumber;
use Backend\Modules\Catalog\Domain\Order\Order;
use Backend\Modules\Catalog\Domain\OrderHistory\OrderHistory;
use Common\Mailer\Message;
use Common\ModulesSettings;
use Frontend\Core\Engine\TwigTemplate;
use Frontend\Core\Language\Language;
use Knp\Snappy\Pdf;
use Swift_Mailer;
use Swift_Mime_SimpleMessage;

final class OrderCreated
{
    /**
     * @var ModulesSettings
     */
    protected $modulesSettings;

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    public function __construct(
        Swift_Mailer $mailer,
        ModulesSettings $modulesSettings
    ) {
        $this->mailer = $mailer;
        $this->modulesSettings = $modulesSettings;
    }

    public function onOrderCreated(OrderCreatedEvent $event): void
    {
        // Skip when we don't need to notify anyone
        if (!$event->getOrderHistory()->isNotify()) {
            return;
        }

        $customerMessage = $this->getCustomerMessage(
            $event->getOrder(),
            $event->getOrderHistory()
        );

        $companyMessage = $this->getCompanyMessage(
            $event->getOrder(),
            $event->getOrderHistory()
        );

        $this->mailer->send($customerMessage);
        $this->mailer->send($companyMessage);
    }

    /**
     * @param Order $order
     * @param OrderHistory $orderHistory
     *
     * @return Swift_Mime_SimpleMessage
     */
    private function getCustomerMessage(Order $order, OrderHistory $orderHistory) : Swift_Mime_SimpleMessage {
        $subject = $this->modulesSettings->get('Core', 'site_title_' . LANGUAGE) .
                   ' - '.
                   sprintf(Language::getLabel('NewOrderMailSubject'), $order->getId());

        $from = $this->modulesSettings->get('Core', 'mailer_from');

        $message = Message::newInstance($subject)
                          ->parseHtml(
                              '/Catalog/Layout/Templates/Mails/Order/ConfirmationCustomer.html.twig',
                              [
                                  'order' => $order,
                                  'orderHistory' => $orderHistory,
                              ],
                              true
                          )
                          ->setTo($order->getInvoiceAddress()->getEmailAddress())
                          ->setFrom([$from['email'] => $from['name']]);

        if ($orderHistory->isAttachInvoice()) {
            /** @var OrderGenerateInvoiceNumber $orderGenerateInvoiceNumber */
            $orderGenerateInvoiceNumber = Model::get('event_dispatcher')->dispatch(
                OrderGenerateInvoiceNumber::EVENT_NAME,
                new OrderGenerateInvoiceNumber($order)
            );

            $filename = Language::lbl('Invoice') . '-' . $orderGenerateInvoiceNumber->getOrder()->getInvoiceNumber() .'.pdf';

            $attachment = new \Swift_Attachment(
                $this->generateInvoice($orderGenerateInvoiceNumber->getOrder()),
                $filename,
                'application/pdf'
            );

            $message->attach($attachment);
        }

        return $message;
    }

    /**
     * @param Order $order
     * @param OrderHistory $orderHistory
     *
     * @return Swift_Mime_SimpleMessage
     */
    private function getCompanyMessage(Order $order, OrderHistory $orderHistory) : Swift_Mime_SimpleMessage {
        $subject = $this->modulesSettings->get('Core', 'site_title_' . LANGUAGE) .
                   ' - '.
                   sprintf(Language::getLabel('NewOrderMailSubject'), $order->getId());

        $from = $this->modulesSettings->get('Core', 'mailer_from');
        $to = $this->modulesSettings->get('Core', 'mailer_to');

        $message = Message::newInstance($subject)
                          ->parseHtml(
                              '/Catalog/Layout/Templates/Mails/Order/ConfirmationCompany.html.twig',
                              [
                                  'order' => $order,
                                  'orderHistory' => $orderHistory,
                              ],
                              true
                          )
                          ->setTo($to['email'])
                          ->setFrom([$from['email'] => $from['name']]);

        return $message;
    }

    private function generateInvoice($order): string
    {
        /** @var TwigTemplate $template */
        $template = Model::get('templating');
        $template->assign('order', $order);

        /** @var Pdf $pdf */
        $pdf = Model::get('knp_snappy.pdf');
        $pdf->setOption('viewport-size', '1024x768');

        return $pdf->getOutputFromHtml($template->getContent('Catalog/Layout/Templates/Invoice.html.twig'));
    }
}
