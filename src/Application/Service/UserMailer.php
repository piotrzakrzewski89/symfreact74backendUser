<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Message\SendMailMessage;
use App\Domain\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;

class UserMailer
{
    public function __construct(
        private Environment $twig,
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator,
    ) {}

    public function sendCreated(User $user): void
    {
        $subject = $this->translator->trans('email.user.created.subject');
        $bodyContent = $this->translator->trans(
            'email.user.created.body',
            [
                '%firstName%' => $user->getFirstName(),
                '%lastName%' => $user->getFirstName(),
                '%email%' => $user->getEmail(),
                '%employeeNumber%' => $user->getEmployeeNumber(),
            ]
        );

        $body = $this->renderBaseTemplate($subject, $bodyContent);

        $this->messageBus->dispatch(
            new SendMailMessage($user->getEmail(), $subject, $body)
        );
    }

    public function sendUpdated(User $user): void
    {
        $subject = $this->translator->trans('email.user.updated.subject');
        $bodyContent = $this->translator->trans(
            'email.user.updated.body',
            [
                '%firstName%' => $user->getFirstName(),
                '%lastName%' => $user->getFirstName(),
                '%email%' => $user->getEmail(),
                '%employeeNumber%' => $user->getEmployeeNumber(),
            ]
        );

        $body = $this->renderBaseTemplate($subject, $bodyContent);

        $this->messageBus->dispatch(
            new SendMailMessage($user->getEmail(), $subject, $body)
        );
    }

    public function sendChangeActive(User $user): void
    {
        $subject = $this->translator->trans('email.user.changeActive.subject');
        $bodyContent = $this->translator->trans(
            'email.user.changeActive.body',
            [
                '%firstName%' => $user->getFirstName(),
                '%lastName%' => $user->getLastName(),
                '%email%' => $user->getEmail(),
                '%employeeNumber%' => $user->getEmployeeNumber(),
                '%isActive%' => $user->isActive() ? 'true' : 'false',
            ]
        );

        $body = $this->renderBaseTemplate($subject, $bodyContent);

        $this->messageBus->dispatch(
            new SendMailMessage($user->getEmail(), $subject, $body)
        );
    }

    public function sendDeleted(User $user): void
    {
        $subject = $this->translator->trans('email.user.deleted.subject');
        $bodyContent = $this->translator->trans(
            'email.user.deleted.body',
            [
                '%firstName%' => $user->getFirstName(),
                '%lastName%' => $user->getFirstName(),
                '%email%' => $user->getEmail(),
                '%employeeNumber%' => $user->getEmployeeNumber(),
            ]
        );

        $body = $this->renderBaseTemplate($subject, $bodyContent);

        $this->messageBus->dispatch(
            new SendMailMessage($user->getEmail(), $subject, $body)
        );
    }

    public function sendVerifyMail(string $link, string $email): void
    {
        $subject = $this->translator->trans('email.user.verify.subject');
        $bodyContent = $this->translator->trans(
            'email.user.verify.body',
            [
                '%email%' => $email,
                '%link%' => $link,
            ]
        );

        $body = $this->renderBaseTemplate($subject, $bodyContent);

        $this->messageBus->dispatch(
            new SendMailMessage($email, $subject, $body)
        );
    }

    private function renderBaseTemplate(string $title, string $content): string
    {
        return $this->twig->render(
            'emails/user-base.html.twig',
            [
                'title' => $title,
                'content' => $content
            ]
        );
    }
}
