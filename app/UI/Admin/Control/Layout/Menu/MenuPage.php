<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Menu;

use App\Model\Enum\Acl\Resource;

/**
 * Pages of `sany_pages` the menu knows how to show. The value is the row's `url`.
 * The only place that maps a menu row to the permission it needs and to the presenter it opens.
 * A row whose `url` is not listed here is never shown.
 */
enum MenuPage: string
{
	case HOME = 'homepage';
	case BABYSITTER = 'opatrovatelky';
	case FAMILY = 'families';
	case PARTNER = 'partneri';
	case AGENCY = 'agencies';
	case WORKER = 'pracovnici';
	case PROJECT = 'projekty';
	case TURNUS = 'turnus';
	case PROPOSAL = 'proposal-records';
	case STATS = 'stats';
	case TODO = 'todo';
	case MISSING = 'missing-registry';
	case SETTINGS = 'settings';
	case COUNTRY = 'country';
	case USER_MANAGEMENT = 'user-management';
	case TRANSLATION = 'translation';

	public function resource(): Resource
	{
		return match ($this) {
			self::HOME => Resource::HOME,
			self::BABYSITTER => Resource::BABYSITTER,
			self::FAMILY => Resource::FAMILY,
			self::PARTNER => Resource::PARTNER,
			self::AGENCY => Resource::AGENCY,
			self::WORKER => Resource::WORKER,
			self::PROJECT => Resource::PROJECT,
			self::TURNUS => Resource::TURNUS,
			self::PROPOSAL => Resource::PROPOSAL,
			self::STATS => Resource::STATS,
			self::TODO => Resource::TODO,
			self::MISSING => Resource::MISSING,
			self::SETTINGS => Resource::SETTINGS,
			self::COUNTRY => Resource::COUNTRY,
			self::USER_MANAGEMENT => Resource::USER_MANAGEMENT,
			self::TRANSLATION => Resource::TRANSLATION,
		};
	}

	public function destination(): string
	{
		return match ($this) {
			self::HOME => ':Admin:Home:default',
			self::BABYSITTER => ':Admin:Babysitter:default',
			self::FAMILY => ':Admin:Family:default',
			self::PARTNER => ':Admin:Partner:default',
			self::AGENCY => ':Admin:Agency:default',
			self::WORKER => ':Admin:Worker:default',
			self::PROJECT => ':Admin:Project:default',
			self::TURNUS => ':Admin:Turnus:default',
			self::PROPOSAL => ':Admin:Proposal:default',
			self::STATS => ':Admin:Stats:default',
			self::TODO => ':Admin:Todo:default',
			self::MISSING => ':Admin:MissingRegistry:calendar',
			self::SETTINGS => ':Admin:Settings:default',
			self::COUNTRY => ':Admin:Country:default',
			self::USER_MANAGEMENT => ':Admin:UserManagement:default',
			self::TRANSLATION => ':Admin:Translation:default',
		};
	}
}
