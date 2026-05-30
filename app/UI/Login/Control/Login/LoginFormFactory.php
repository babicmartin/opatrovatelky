<?php declare(strict_types = 1);

namespace App\UI\Login\Control\Login;

use App\Model\Form\DTO\Login\LoginForm\LoginFormDTO;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Security\AdminAuthenticator;
use App\Model\Security\LoginRateLimiter;
use App\Model\Security\SecurityAuditLogger;
use Nette\Application\UI\Form;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use Nette\SmartObject;
use Psr\Log\LoggerInterface;

class LoginFormFactory
{
	use SmartObject;

	public function __construct(
		private readonly User $user,

		private readonly AdminAuthenticator $adminAuthenticator,
		private readonly LoggerInterface $logger,
		private readonly BaseFormFactory $baseFormFactory,
		private readonly Session $session,
		private readonly IRequest $httpRequest,
		private readonly LoginRateLimiter $loginRateLimiter,
		private readonly SecurityAuditLogger $securityAuditLogger,
	) {	}

	public function create(): Form
	{
		$form = $this->baseFormFactory->create();

		$form->addText('email', 'Email')
			->setRequired('Email je povinný');
		$form->addPassword('password', 'Heslo')
			->setRequired('Heslo je povinné');
		$form->addSubmit('send', 'Prihlásiť');
		$form->onSuccess[] = [$this, 'onSuccess'];

		return $form;
	}

	public function onSuccess(Form $form, LoginFormDTO $formDTO): void
	{
		$email = trim($formDTO->getEmail());
		$ipAddress = $this->httpRequest->getRemoteAddress() ?? '';
		$blockedUntil = $this->loginRateLimiter->getBlockedUntil($email, $ipAddress);

		if ($blockedUntil !== null) {
			$form->addError('Prihlásenie sa nepodarilo');
			$this->securityAuditLogger->log('login_blocked', null, $email, [
				'blockedUntil' => $blockedUntil->format('Y-m-d H:i:s'),
			]);
			$this->logger->warning('Login blocked by rate limit: ' . $email);
			return;
		}

		try {
			$this->user->setAuthenticator($this->adminAuthenticator);
			$this->user->login($email, $formDTO->getPassword());
			$this->session->regenerateId();
			$userId = $this->user->getId();
			$this->loginRateLimiter->recordSuccess($email, $ipAddress);
			$this->securityAuditLogger->log('login_success', is_int($userId) ? $userId : null, $email);
		} catch (AuthenticationException $exception) {
			$this->loginRateLimiter->recordFailure($email, $ipAddress, 'authentication_failed');
			$this->securityAuditLogger->log('login_failed', null, $email);
			$form->addError('Prihlásenie sa nepodarilo');
			$this->logger->warning('Login failed: ' . $email);
		}
	}

}
