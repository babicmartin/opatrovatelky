<?php declare(strict_types=1);

namespace Tests\Regression;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\MissingRegistryTableMap;
use App\Model\Table\TurnusTableMap;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\ConfirmedFormWorkflowTrait;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class AdminDeleteFormRegressionTest extends DatabaseTestCase
{
	use ConfirmedFormWorkflowTrait;

	/**
	 * Both pages are open to every signed-in role (DEALER_JUNIOR and up), so no role is denied here.
	 * The only actor without the permission is a guest: scenario 'signed out'.
	 *
	 * @return iterable<string, array{string, string}>
	 */
	public static function deleteRequests(): iterable
	{
		foreach (['Admin:Turnus', 'Admin:MissingRegistry'] as $presenter) {
			foreach (['confirmed POST', 'legacy GET', 'form GET', 'cross-site POST', 'invalid token', 'signed out'] as $scenario) {
				yield $presenter . ' / ' . $scenario => [$presenter, $scenario];
			}
		}
	}

	#[DataProvider('deleteRequests')]
	public function testDeleteForm(string $presenter, string $scenario): void
	{
		$userId = $this->loginAs(UserRole::ADMIN);
		if ($presenter === 'Admin:Turnus') {
			$table = TurnusTableMap::TABLE_NAME;
			$deleted = TurnusTableMap::COL_DELETED;
			$context = 'turnus.update';
			$controlId = TestDatabase::createTurnus();
			$id = TestDatabase::createTurnus();
			$params = ['action' => 'update', 'id' => $id];
			$formId = 'frm-deleteTurnusForm';
			$legacy = [Presenter::SignalKey => 'delete'];
		} else {
			$table = MissingRegistryTableMap::TABLE_NAME;
			$deleted = MissingRegistryTableMap::COL_DELETED;
			$context = 'missingRegistry.row';
			$controlId = TestDatabase::insert($table, [MissingRegistryTableMap::COL_USER_ID => 0]);
			$id = TestDatabase::insert($table, [
				MissingRegistryTableMap::COL_USER_ID => 0,
				MissingRegistryTableMap::COL_DATE_FROM => '2026-11-01',
			]);
			$params = ['action' => 'default'];
			$formId = 'frm-missingRegistryList-deleteRegistryForm-' . $id;
			$legacy = [Presenter::SignalKey => 'missingRegistryList-delete', 'missingRegistryList-id' => $id];
		}

		$rows = $this->getDatabase()->table($table);
		$target = $rows->get($id);
		$control = $rows->get($controlId);
		self::assertNotNull($target);
		self::assertNotNull($control);
		$targetBefore = $target->toArray();
		$controlBefore = $control->toArray();
		self::assertSame(0, (int) $targetBefore[$deleted]);
		$logs = $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME);
		$auditCount = $logs->count('*');
		$html = $this->renderPresenter($presenter, $params);
		self::assertStringNotContainsString('do=' . $legacy[Presenter::SignalKey], $html);
		$post = $this->readDeleteForm($html, $formId);
		$method = 'POST';

		switch ($scenario) {
			case 'legacy GET':
				$method = 'GET';
				$params += $legacy;
				$post = [];
				break;
			case 'form GET':
				$method = 'GET';
				$params += [Presenter::SignalKey => $post['_do']] + $post;
				$post = [];
				break;
			case 'invalid token':
				$post['_token_'] = 'invalid';
				break;
			case 'signed out':
				$this->logout();
				break;
		}

		try {
			$response = $this->runFormRequest($presenter, $method, $params, $post, sameSite: $scenario !== 'cross-site POST');
			if ($scenario === 'form GET') {
				self::assertInstanceOf(TextResponse::class, $response);
			} else {
				self::assertContains($scenario, ['confirmed POST', 'cross-site POST', 'signed out']);
				self::assertInstanceOf(RedirectResponse::class, $response);
				if ($scenario === 'signed out') {
					self::assertStringEndsWith('/login/', (string) parse_url($response->getUrl(), PHP_URL_PATH));
				}
			}
		} catch (BadRequestException $exception) {
			self::assertContains($scenario, ['legacy GET', 'invalid token']);
			self::assertSame(403, $exception->getCode());
		}

		self::assertEquals($controlBefore, $rows->get($controlId)?->toArray());
		if ($scenario !== 'confirmed POST') {
			self::assertEquals($targetBefore, $rows->get($id)?->toArray());
			self::assertSame($auditCount, $logs->count('*'));
			return;
		}

		self::assertEquals(array_replace($targetBefore, [$deleted => 1]), $rows->get($id)?->toArray());
		self::assertSame($auditCount + 1, $logs->count('*'));
		$log = $logs->order(ChangeLogTableMap::COL_ID . ' DESC')->fetch();
		self::assertNotNull($log);
		self::assertSame($context, $log->{ChangeLogTableMap::COL_CONTEXT});
		self::assertSame($table, $log->{ChangeLogTableMap::COL_ENTITY_TABLE});
		self::assertSame($id, (int) $log->{ChangeLogTableMap::COL_ENTITY_ID});
		self::assertSame($userId, (int) $log->{ChangeLogTableMap::COL_USER_ID});
		self::assertJsonStringEqualsJsonString('{"action":"deleted"}', (string) $log->{ChangeLogTableMap::COL_METADATA});

		try {
			$this->runFormRequest($presenter, 'POST', $params, $post);
			self::fail('The deleted row must no longer be available.');
		} catch (BadRequestException $exception) {
			self::assertSame(404, $exception->getCode());
		}
		self::assertSame($auditCount + 1, $logs->count('*'));
	}

	protected function tearDown(): void
	{
		$this->logout();
		$this->getContainer()->removeService('http.request');
		parent::tearDown();
	}
}
