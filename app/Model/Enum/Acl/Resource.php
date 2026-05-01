<?php declare(strict_types=1);

namespace App\Model\Enum\Acl;

enum Resource: string
{
	case HOME = 'homepage';
	case BABYSITTER = 'babysitter';
	case FAMILY = 'family';
	case PARTNER = 'partner';
	case AGENCY = 'agency';
	case WORKER = 'worker';
	case PROJECT = 'project';
	case TURNUS = 'turnus';
	case STATS = 'stats';
	case USER_MANAGEMENT = 'userManagement';
	case SETTINGS = 'settings';
	case COUNTRY = 'country';
	case TODO = 'todo';
	case TRANSLATION = 'translation';
	case PROPOSAL = 'proposal';
	case MISSING = 'missing-registry';
}
