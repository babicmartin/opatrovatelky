<?php declare(strict_types=1);

namespace Tests\Regression;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\AgencyTableMap;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyProposalTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\MissingRegistryTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\TodoClientTableMap;
use App\Model\Table\TurnusTableMap;
use App\Model\Table\UserTableMap;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Response;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;
use Nette\Http\Helpers;
use Nette\Http\IResponse;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

final class AdminCreateSignalRegressionTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	/**
	 * @return iterable<string, array{array<string, mixed>}>
	 */
	public static function createFlows(): iterable
	{
		yield 'family' => [[
			'presenter' => 'Admin:Family',
			'button' => 'Pridať novú rodinu',
			'confirm' => 'Naozaj chcete vytvoriť novú rodinu?',
			'legacySignal' => 'create',
			'table' => FamilyTableMap::TABLE_NAME,
			'auditContext' => 'family.shortInfo',
			'auditTable' => FamilyTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created', 'created_as' => 'family'],
			'assertion' => 'family',
		]];
		yield 'babysitter' => [[
			'presenter' => 'Admin:Babysitter',
			'button' => 'Pridať novú opatrovateľku',
			'confirm' => 'Naozaj chcete vytvoriť novú opatrovateľku?',
			'legacySignal' => 'create',
			'table' => OpatrovatelkaTableMap::TABLE_NAME,
			'auditContext' => 'babysitter.main',
			'auditTable' => OpatrovatelkaTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created', 'created_as' => 'babysitter'],
			'assertion' => 'babysitter',
		]];
		yield 'partner' => [[
			'presenter' => 'Admin:Partner',
			'button' => 'Pridať nového partnera',
			'confirm' => 'Naozaj chcete vytvoriť nového partnera?',
			'legacySignal' => 'create',
			'table' => PartnerTableMap::TABLE_NAME,
			'auditContext' => 'partner.update',
			'auditTable' => PartnerTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created'],
			'assertion' => 'partner',
		]];
		yield 'agency' => [[
			'presenter' => 'Admin:Agency',
			'button' => 'Pridať novú agentúru',
			'confirm' => 'Naozaj chcete vytvoriť novú agentúru?',
			'legacySignal' => 'create',
			'table' => AgencyTableMap::TABLE_NAME,
			'auditContext' => 'agency.update',
			'auditTable' => AgencyTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created'],
			'assertion' => 'agency',
		]];
		yield 'worker' => [[
			'presenter' => 'Admin:Worker',
			'button' => 'Pridať nového pracovníka',
			'confirm' => 'Naozaj chcete vytvoriť nového pracovníka?',
			'legacySignal' => 'create',
			'table' => OpatrovatelkaTableMap::TABLE_NAME,
			'auditContext' => 'babysitter.main',
			'auditTable' => OpatrovatelkaTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created', 'created_as' => 'worker'],
			'assertion' => 'worker',
		]];
		yield 'project' => [[
			'presenter' => 'Admin:Project',
			'button' => 'Pridať nový projekt',
			'confirm' => 'Naozaj chcete vytvoriť nový projekt?',
			'legacySignal' => 'create',
			'table' => FamilyTableMap::TABLE_NAME,
			'auditContext' => 'family.shortInfo',
			'auditTable' => FamilyTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created', 'created_as' => 'project'],
			'assertion' => 'project',
		]];
		yield 'turnus' => [[
			'presenter' => 'Admin:Turnus',
			'button' => 'Pridať nový turnus',
			'confirm' => 'Naozaj chcete vytvoriť nový turnus?',
			'legacySignal' => 'create',
			'table' => TurnusTableMap::TABLE_NAME,
			'auditContext' => 'turnus.update',
			'auditTable' => TurnusTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created'],
			'assertion' => 'turnus',
		]];
		yield 'todo' => [[
			'presenter' => 'Admin:Todo',
			'button' => 'Vytvoriť novú úlohu',
			'confirm' => 'Naozaj chcete vytvoriť novú úlohu?',
			'legacySignal' => 'create',
			'table' => TodoClientTableMap::TABLE_NAME,
			'auditContext' => 'todo.update',
			'auditTable' => TodoClientTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created'],
			'assertion' => 'todo',
		]];
		yield 'country' => [[
			'presenter' => 'Admin:Country',
			'button' => 'Pridať novú krajinu',
			'confirm' => 'Naozaj chcete vytvoriť novú krajinu?',
			'legacySignal' => 'create',
			'table' => CountryTableMap::TABLE_NAME,
			'auditContext' => 'country.update',
			'auditTable' => CountryTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created'],
			'assertion' => 'country',
		]];
		yield 'user-management' => [[
			'presenter' => 'Admin:UserManagement',
			'button' => 'Pridať nového užívateľa',
			'confirm' => 'Naozaj chcete vytvoriť nového užívateľa?',
			'legacySignal' => 'create',
			'table' => UserTableMap::TABLE_NAME,
			'auditContext' => 'user.profile',
			'auditTable' => UserTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created'],
			'assertion' => 'user-management',
		]];
		yield 'missing-registry' => [[
			'presenter' => 'Admin:MissingRegistry',
			'button' => 'Pridať novú evidenciu',
			'confirm' => 'Naozaj chcete vytvoriť novú evidenciu neprítomnosti?',
			'legacySignal' => 'missingRegistryList-create',
			'table' => MissingRegistryTableMap::TABLE_NAME,
			'auditContext' => 'missingRegistry.row',
			'auditTable' => MissingRegistryTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created'],
			'assertion' => 'missing-registry',
			'legacyCreateNeedle' => 'do=missingRegistryList-create',
		]];
	}

	/**
	 * @param array<string, mixed> $flow
	 */
	#[DataProvider('createFlows')]
	public function testCreateRequiresConfirmedPostAndLegacyGetDoesNotCreate(array $flow): void
	{
		$adminId = $this->loginAs(UserRole::ADMIN);
		$html = $this->renderFlow($flow);

		self::assertStringContainsString((string) $flow['button'], $html);
		self::assertStringContainsString((string) $flow['confirm'], $html);
		self::assertStringNotContainsString((string) ($flow['legacyCreateNeedle'] ?? 'do=create'), $html);

		$table = (string) $flow['table'];
		$entityCountBefore = $this->rowCount($table);
		$changeLogCountBefore = $this->rowCount(ChangeLogTableMap::TABLE_NAME);

		$response = $this->runFlow($flow, 'GET', [
			'action' => $flow['action'] ?? 'default',
			Presenter::SignalKey => $flow['legacySignal'],
		]);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame($entityCountBefore, $this->rowCount($table));
		self::assertSame($changeLogCountBefore, $this->rowCount(ChangeLogTableMap::TABLE_NAME));

		$formHtml = $this->extractFormBySubmitLabel($html, (string) $flow['button']);

		$response = $this->runFlow($flow, 'POST', [
			'action' => $flow['action'] ?? 'default',
		], [
			'_do' => $this->extractHiddenInput($formHtml, '_do'),
			'_token_' => $this->extractHiddenInput($formHtml, '_token_'),
			'create' => (string) $flow['button'],
		]);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame($entityCountBefore + 1, $this->rowCount($table));
		self::assertSame($changeLogCountBefore + 1, $this->rowCount(ChangeLogTableMap::TABLE_NAME));

		$createdRow = $this->fetchLatestRow($table);
		$this->assertCreatedEntityDefaults((string) $flow['assertion'], $createdRow, $adminId);
		$this->assertLatestChangeLog($flow, (int) $createdRow->id);
	}

	public function testTodoClosedActionRendersPostCreateForm(): void
	{
		$this->loginAs(UserRole::ADMIN);

		$html = $this->renderPresenter('Admin:Todo', ['action' => 'closed']);

		self::assertStringContainsString('Vytvoriť novú úlohu', $html);
		self::assertStringContainsString('Naozaj chcete vytvoriť novú úlohu?', $html);
		self::assertStringNotContainsString('do=create', $html);
		$this->extractFormBySubmitLabel($html, 'Vytvoriť novú úlohu');
	}

	public function testFamilyUpdateCreateTurnusRequiresConfirmedPostAndLegacyGetDoesNotCreate(): void
	{
		$adminId = $this->loginAs(UserRole::ADMIN);
		$familyId = TestDatabase::createFamily();
		$flow = ['presenter' => 'Admin:Family'];

		$response = $this->runFlow($flow, 'GET', [
			'action' => 'update',
			'id' => $familyId,
			'tab' => 'main',
		]);
		self::assertInstanceOf(TextResponse::class, $response);
		$html = $this->sendTextResponse($response);

		self::assertStringContainsString('Vytvoriť novú evidenciu', $html);
		self::assertStringContainsString('Naozaj chcete vytvoriť novú evidenciu?', $html);
		self::assertStringNotContainsString('do=createTurnus', $html);

		$turnusCountBefore = $this->rowCount(TurnusTableMap::TABLE_NAME);
		$changeLogCountBefore = $this->rowCount(ChangeLogTableMap::TABLE_NAME);

		$response = $this->runFlow($flow, 'GET', [
			'action' => 'update',
			'id' => $familyId,
			'tab' => 'main',
			Presenter::SignalKey => 'createTurnus',
			'familyId' => $familyId,
		]);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame($turnusCountBefore, $this->rowCount(TurnusTableMap::TABLE_NAME));
		self::assertSame($changeLogCountBefore, $this->rowCount(ChangeLogTableMap::TABLE_NAME));

		$formHtml = $this->extractFormBySubmitLabel($html, 'Vytvoriť novú evidenciu');

		$response = $this->runFlow($flow, 'POST', [
			'action' => 'update',
			'id' => $familyId,
			'tab' => 'main',
		], [
			'_do' => $this->extractHiddenInput($formHtml, '_do'),
			'_token_' => $this->extractHiddenInput($formHtml, '_token_'),
			'create' => 'Vytvoriť novú evidenciu',
		]);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame($turnusCountBefore + 1, $this->rowCount(TurnusTableMap::TABLE_NAME));
		self::assertSame($changeLogCountBefore + 1, $this->rowCount(ChangeLogTableMap::TABLE_NAME));

		$createdRow = $this->fetchLatestRow(TurnusTableMap::TABLE_NAME);
		self::assertSame($familyId, (int) $createdRow->{TurnusTableMap::COL_FAMILY_ID});
		self::assertSame($adminId, (int) $createdRow->{TurnusTableMap::COL_USER_CREATED});
		$this->assertLatestChangeLog([
			'auditContext' => 'turnus.update',
			'auditTable' => TurnusTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created', 'created_from' => 'family', 'family_id' => $familyId],
		], (int) $createdRow->id);
	}

	public function testFamilyUpdateCreateProposalRequiresConfirmedPostAndLegacyGetDoesNotCreate(): void
	{
		$adminId = $this->loginAs(UserRole::ADMIN);
		$familyId = TestDatabase::createFamily();
		$flow = ['presenter' => 'Admin:Family'];

		$response = $this->runFlow($flow, 'GET', [
			'action' => 'update',
			'id' => $familyId,
			'tab' => 'proposals',
		]);
		self::assertInstanceOf(TextResponse::class, $response);
		$html = $this->sendTextResponse($response);

		self::assertStringContainsString('Vytvoriť nový návrh', $html);
		self::assertStringContainsString('Naozaj chceš vytvoriť návrh pre rodinu?', $html);
		self::assertStringNotContainsString('do=createProposal', $html);

		$proposalCountBefore = $this->rowCount(FamilyProposalTableMap::TABLE_NAME);
		$changeLogCountBefore = $this->rowCount(ChangeLogTableMap::TABLE_NAME);

		$response = $this->runFlow($flow, 'GET', [
			'action' => 'update',
			'id' => $familyId,
			'tab' => 'proposals',
			Presenter::SignalKey => 'createProposal',
			'familyId' => $familyId,
		]);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame($proposalCountBefore, $this->rowCount(FamilyProposalTableMap::TABLE_NAME));
		self::assertSame($changeLogCountBefore, $this->rowCount(ChangeLogTableMap::TABLE_NAME));

		$formHtml = $this->extractFormBySubmitLabel($html, 'Vytvoriť nový návrh');

		$response = $this->runFlow($flow, 'POST', [
			'action' => 'update',
			'id' => $familyId,
			'tab' => 'proposals',
		], [
			'_do' => $this->extractHiddenInput($formHtml, '_do'),
			'_token_' => $this->extractHiddenInput($formHtml, '_token_'),
			'create' => 'Vytvoriť nový návrh',
		]);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame($proposalCountBefore + 1, $this->rowCount(FamilyProposalTableMap::TABLE_NAME));
		self::assertSame($changeLogCountBefore + 1, $this->rowCount(ChangeLogTableMap::TABLE_NAME));

		$createdRow = $this->fetchLatestRow(FamilyProposalTableMap::TABLE_NAME);
		self::assertSame($familyId, (int) $createdRow->{FamilyProposalTableMap::COL_FAMILY_ID});
		self::assertSame($adminId, (int) $createdRow->{FamilyProposalTableMap::COL_USER_CREATED});
		$this->assertLatestChangeLog([
			'auditContext' => 'proposal.update',
			'auditTable' => FamilyProposalTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created', 'created_from' => 'family', 'family_id' => $familyId],
		], (int) $createdRow->id);
	}

	public function testBabysitterUpdateCreateTurnusRequiresConfirmedPostAndLegacyGetDoesNotCreate(): void
	{
		$adminId = $this->loginAs(UserRole::ADMIN);
		$babysitterId = TestDatabase::createBabysitter();
		$flow = ['presenter' => 'Admin:Babysitter'];

		$response = $this->runFlow($flow, 'GET', [
			'action' => 'update',
			'id' => $babysitterId,
			'tab' => 'main',
		]);
		self::assertInstanceOf(TextResponse::class, $response);
		$html = $this->sendTextResponse($response);

		self::assertStringContainsString('Vytvoriť novú evidenciu', $html);
		self::assertStringContainsString('Naozaj chcete vytvoriť novú evidenciu?', $html);
		self::assertStringNotContainsString('do=createTurnus', $html);

		$turnusCountBefore = $this->rowCount(TurnusTableMap::TABLE_NAME);
		$changeLogCountBefore = $this->rowCount(ChangeLogTableMap::TABLE_NAME);

		$response = $this->runFlow($flow, 'GET', [
			'action' => 'update',
			'id' => $babysitterId,
			'tab' => 'main',
			Presenter::SignalKey => 'createTurnus',
			'babysitterId' => $babysitterId,
		]);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame($turnusCountBefore, $this->rowCount(TurnusTableMap::TABLE_NAME));
		self::assertSame($changeLogCountBefore, $this->rowCount(ChangeLogTableMap::TABLE_NAME));

		$formHtml = $this->extractFormBySubmitLabel($html, 'Vytvoriť novú evidenciu');

		$response = $this->runFlow($flow, 'POST', [
			'action' => 'update',
			'id' => $babysitterId,
			'tab' => 'main',
		], [
			'_do' => $this->extractHiddenInput($formHtml, '_do'),
			'_token_' => $this->extractHiddenInput($formHtml, '_token_'),
			'create' => 'Vytvoriť novú evidenciu',
		]);

		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame($turnusCountBefore + 1, $this->rowCount(TurnusTableMap::TABLE_NAME));
		self::assertSame($changeLogCountBefore + 1, $this->rowCount(ChangeLogTableMap::TABLE_NAME));

		$createdRow = $this->fetchLatestRow(TurnusTableMap::TABLE_NAME);
		self::assertSame($babysitterId, (int) $createdRow->{TurnusTableMap::COL_BABYSITTER_ID});
		self::assertSame($adminId, (int) $createdRow->{TurnusTableMap::COL_USER_CREATED});
		$this->assertLatestChangeLog([
			'auditContext' => 'turnus.update',
			'auditTable' => TurnusTableMap::TABLE_NAME,
			'metadata' => ['action' => 'created', 'created_from' => 'babysitter', 'babysitter_id' => $babysitterId],
		], (int) $createdRow->id);
	}

	/**
	 * @param array<string, mixed> $flow
	 */
	private function renderFlow(array $flow): string
	{
		$response = $this->runFlow($flow, 'GET', [
			'action' => $flow['action'] ?? 'default',
		]);
		self::assertInstanceOf(TextResponse::class, $response);

		return $this->sendTextResponse($response);
	}

	/**
	 * @param array<string, mixed> $flow
	 * @param array<string, mixed> $params
	 * @param array<string, mixed> $post
	 */
	private function runFlow(array $flow, string $method, array $params, array $post = []): Response
	{
		$this->replaceHttpRequest($method, $post);

		$presenterName = (string) $flow['presenter'];
		$presenter = $this->getContainer()
			->getByType(IPresenterFactory::class)
			->createPresenter($presenterName);
		self::assertInstanceOf(Presenter::class, $presenter);
		$presenter->autoCanonicalize = false;

		return $presenter->run(new ApplicationRequest($presenterName, $method, $params, $post));
	}

	/**
	 * @param array<string, mixed> $post
	 */
	private function replaceHttpRequest(string $method, array $post): void
	{
		$request = new HttpRequest(
			new UrlScript('https://opatrovatelky.local/admin-create-test'),
			$post,
			[],
			[Helpers::StrictCookieName => '1'],
			[],
			$method,
		);

		$this->getContainer()->removeService('http.request');
		$this->getContainer()->addService('http.request', $request);
		$this->getContainer()->getByType(IResponse::class)->setCode(IResponse::S200_OK);
	}

	private function extractFormBySubmitLabel(string $html, string $label): string
	{
		if (preg_match_all('/<form\b[^>]*>.*?<\/form>/is', $html, $matches) !== false) {
			foreach ($matches[0] as $formHtml) {
				if (str_contains($formHtml, $label)) {
					return $formHtml;
				}
			}
		}

		self::fail('Form with submit "' . $label . '" was not rendered.');
	}

	private function extractHiddenInput(string $html, string $name): string
	{
		$quotedName = preg_quote($name, '/');
		if (preg_match('/<input\b(?=[^>]*\bname="' . $quotedName . '")(?=[^>]*\bvalue="([^"]*)")[^>]*>/i', $html, $matches) !== 1) {
			self::fail('Hidden input "' . $name . '" was not rendered.');
		}

		return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
	}

	private function rowCount(string $table): int
	{
		return $this->getDatabase()->table($table)->count('*');
	}

	private function fetchLatestRow(string $table): ActiveRow
	{
		$row = $this->getDatabase()
			->table($table)
			->order('id DESC')
			->fetch();
		self::assertInstanceOf(ActiveRow::class, $row);

		return $row;
	}

	private function assertCreatedEntityDefaults(string $assertion, ActiveRow $row, int $adminId): void
	{
		switch ($assertion) {
			case 'family':
				self::assertSame(1, (int) $row->{FamilyTableMap::COL_TYPE});
				break;
			case 'project':
				self::assertSame(2, (int) $row->{FamilyTableMap::COL_TYPE});
				break;
			case 'babysitter':
				self::assertSame(1, (int) $row->{OpatrovatelkaTableMap::COL_TYPE});
				break;
			case 'worker':
				self::assertSame(2, (int) $row->{OpatrovatelkaTableMap::COL_TYPE});
				break;
			case 'turnus':
				self::assertSame($adminId, (int) $row->{TurnusTableMap::COL_USER_CREATED});
				break;
			case 'todo':
				self::assertSame($adminId, (int) $row->{TodoClientTableMap::COL_TODO_FROM_USER});
				break;
			case 'country':
				self::assertSame(1, (int) $row->{CountryTableMap::COL_ACTIVE});
				break;
			case 'user-management':
				self::assertSame(UserRole::DEALER_JUNIOR->getPermissionId(), (int) $row->{UserTableMap::COL_PERMISSION});
				break;
			case 'missing-registry':
				self::assertSame(0, (int) $row->{MissingRegistryTableMap::COL_DELETED});
				break;
			default:
				self::assertNotSame(0, (int) $row->id);
		}

		if ($assertion === 'family' || $assertion === 'project') {
			self::assertSame(0, (int) $row->{FamilyTableMap::COL_DELETED});
		}
		if ($assertion === 'babysitter' || $assertion === 'worker') {
			self::assertSame(1, (int) $row->{OpatrovatelkaTableMap::COL_ACTIVE});
		}
		if ($assertion === 'missing-registry') {
			self::assertSame(1, (int) $row->{MissingRegistryTableMap::COL_ACTIVE});
		}
		if ($assertion === 'user-management') {
			self::assertSame(1, (int) $row->{UserTableMap::COL_ACTIVE});
		}
	}

	/**
	 * @param array<string, mixed> $flow
	 */
	private function assertLatestChangeLog(array $flow, int $entityId): void
	{
		$log = $this->fetchLatestRow(ChangeLogTableMap::TABLE_NAME);

		self::assertSame((string) $flow['auditContext'], $log->{ChangeLogTableMap::COL_CONTEXT});
		self::assertSame((string) $flow['auditTable'], $log->{ChangeLogTableMap::COL_ENTITY_TABLE});
		self::assertSame($entityId, (int) $log->{ChangeLogTableMap::COL_ENTITY_ID});
		self::assertJsonStringEqualsJsonString(
			json_encode($flow['metadata'], JSON_THROW_ON_ERROR),
			(string) $log->{ChangeLogTableMap::COL_METADATA},
		);
	}

	protected function tearDown(): void
	{
		$this->logout();
		$this->getContainer()->removeService('http.request');

		parent::tearDown();
	}
}
