<?php declare(strict_types=1);

/**
 * Inventory of entry points and audit coverage for the security-fix skill.
 *
 *   php security-scan.php inventory <path>... [--summary]   every entry point and risky line in the given files
 *   php security-scan.php status                             coverage by unit, most exposed unaudited unit first
 *   php security-scan.php mark <path>...                     record the given files as audited in their current state
 *
 * The output is a list of places to read, not a list of holes: a missing guard in a method body
 * may be covered by the presenter, and a present one may check the wrong thing.
 *
 * Run with PHP 8.5: /c/wamp64/bin/php/php8.5.0/php.exe
 */

const SCAN_EXTENSIONS = ['php', 'phpt', 'latte', 'js', 'neon'];
const SKIP_DIRS = ['vendor', 'node_modules', 'temp', 'log', '.git', 'private', 'assets', 'img'];
const STATUS_SCOPE = ['app', 'config', 'www'];
const PUBLIC_FILE_DIRS = ['www/documents', 'www/export', 'www/uploads'];
const COVERAGE_FILE = 'workflow/_active/security-audit/coverage.json';

const FORM_EVENTS = ['onSuccess', 'onSubmit', 'onValidate', 'onClick'];
const POLICY_METHODS = [
	'startup', 'checkRequirements', 'getResource', 'getPrivilege',
	'getAllowedAutosaveContexts', 'getCurrentEntityAutosaveContexts', 'validateAutosavePartialRequest',
];
const GUARD_PATTERN = '~\b(?:isAllowed|isInRole|isLoggedIn)\s*\(|(?:->|::)\s*(?!checkDefaultValue)(?:assert|ensure|check|verify|can|deny)[A-Z]\w*\s*\(~';

const PHP_PATTERNS = [
	'file-send' => '~\bFileResponse\b~',
	'upload' => '~->\s*(?:addUpload|addMultiUpload|move)\s*\(~',
	'raw-request' => '~->\s*(?:getPost|getQuery|getHttpData|getUntrustedValues|getParameters|getParameter|getHeader|getCookie|getFiles?|getRawBody)\s*\(~',
	'values-as-array' => '~\(array\)\s*\$(?:values|data|form)|getValues\s*\(\s*(?:true|[\'"]array[\'"])|iterator_to_array\s*\(\s*\$values~',
	'write-whole-variable' => '~->\s*(?:update|insert)\s*\((?:\s*\$\w+\s*,)?\s*(?:\(array\)\s*)?\$\w+\s*\)~',
	'raw-sql' => '~->\s*query\s*\(~',
	'dynamic-order' => '~\b(?:ORDER BY|LIMIT)\b[^;\n]*\$\w+|->\s*(?:order|select|group)\s*\(\s*\$~i',
	'bare-id-load' => '~->\s*get\s*\(\s*(?:\(int\)\s*)?\$\w+~',
	'bare-id-write' => '~->\s*(?:softDelete|delete\w*|remove\w*|update\w*)\s*\(\s*(?:\(int\)\s*)?\$\w*[iI]d\b~',
	'context-default-zero' => '~^\s*(?:private|protected|public)\s+int\s+\$\w*[iI]d\s*=\s*0\s*;~',
	'command' =>'~(?<![\w>:])(?:exec|shell_exec|proc_open|passthru|system|popen|pcntl_exec)\s*\(|`[^`]*\$[^`]*`~',
	'unserialize' => '~\bunserialize\s*\(~',
	'file-op' => '~\b(?:unlink|readfile|file_get_contents|file_put_contents|fopen|copy|rename|rmdir)\s*\(|FileSystem::(?:delete|read|write|copy|rename)\s*\(~',
	'dynamic-include' => '~\b(?:include|include_once|require|require_once)\b[^;\n]*\$~',
	'xml' => '~\b(?:simplexml_load_\w+|XMLReader|xml_parse)\b|->\s*(?:loadXML|loadHTML)\s*\(~',
	'zip' => '~->\s*extractTo\s*\(|\bPharData\b~',
	'cross-origin' => '~CrossOrigin~',
	'raw-html' => '~->\s*setHtml\s*\(|Html::fromHtml\s*\(~',
	'redirect-url' => '~->\s*(?:redirectUrl|restoreRequest)\s*\(|header\s*\(\s*[\'"]Location~i',
	'weak-random' => '~(?<![\w>:])(?:md5|sha1|rand|mt_rand|uniqid)\s*\(~',
	'superglobal' => '~\$_(?:GET|POST|REQUEST|FILES|COOKIE)\b~',
	'outbound-http' => '~\bcurl_init\s*\(|\bGuzzleHttp\b|file_get_contents\s*\(\s*[\'"]https?:~',
	'eval' => '~(?<![\w>:])(?:eval|create_function)\s*\(|\bcall_user_func(?:_array)?\s*\(\s*\$~',
	'hidden-field' => '~->\s*addHidden\s*\(~',
	'form-trust' => '~checkDefaultValue\s*\(\s*false|setOmitted\s*\(\s*false~',
	'form-without-factory' => '~\bnew\s+(?:\\\\?Nette\\\\Application\\\\UI\\\\)?Form\s*\(~',
	'json-out' => '~->\s*sendJson\s*\((?!\s*\[\s*[\'"]success[\'"]\s*=>\s*(?:true|false)\s*\]\s*\))|\bnew\s+JsonResponse\b~',
];
const LATTE_PATTERNS = [
	'noescape' => '~\|\s*noescape~',
	'nocheck' => '~\|\s*(?:nocheck|dataStream)~',
	'signal-link' => '~(?:n:href\s*=\s*"|\{p?link\s+|\bp?link\()[^}"\n]*!~',
	'destructive-get-link' => '~(?:n:href\s*=\s*"|\{p?link\s+|\bp?link\()\s*[\'"]?[\w:-]*(?:delete|remove|destroy|cancel|close|deactivate|reset|approve|archive)\w*!~i',
	'dom-html' => '~\.(?:innerHTML|outerHTML)\s*[+]?=|insertAdjacentHTML\s*\(|document\.write\s*\(~',
	'control' => '~\{control\s+[^}]+\}~',
	'form' => '~\{form\s+[^}]+\}|<form\b[^>]*n:name~',
	'inline-js-variable' => '~\son\w+\s*=\s*"[^"]*\{[$=]~',
];
const JS_PATTERNS = [
	'dom-html' => '~\.(?:innerHTML|outerHTML)\s*[+]?=|insertAdjacentHTML\s*\(|document\.write\s*\(|\.html\s*\(\s*[^)\s]|\beval\s*\(|new\s+Function\s*\(~',
	'ajax-call' => '~\bfetch\s*\(|\$\.(?:ajax|post|get|getJSON)\s*\(|XMLHttpRequest|sendBeacon~',
	'client-storage' => '~localStorage|sessionStorage|document\.cookie~',
];
const NEON_PATTERNS = [
	'config-flag' => '~\b(?:debugMode|debugger|catchExceptions|autoStart|cookieSamesite|cookieSecure|expiration|frames|csp|password|secret|token|apiKey)\b~i',
];
const HTACCESS_PATTERNS = [
	'htaccess-rule' => '~^\s*(?:Require|Options|Header|RewriteRule|RewriteCond|php_flag|php_value|SetEnv|Deny|Allow|Order|<Files|<FilesMatch|AddHandler|AddType)\b~i',
];
const INFO_LABELS = ['control', 'form', 'ajax-call'];
const WRITE_PATTERN = '~->\s*(?:update\w*|insert\w*|delete\w*|softDelete|remove\w*|save\w*|move)\s*\(~';
const AUDIT_PATTERN = '~[aA]uditLogger|logChange|ChangeLog~';
const SENSITIVE_LABELS = ['bare-id-write', 'destructive-get-link', 'dom-html', 'dynamic-order', 'json-out', 'upload','file-send', 'raw-request', 'bare-id-load', 'write-whole-variable', 'noescape', 'values-as-array', 'command', 'file-op'];

main($argv);


/** @param list<string> $argv */
function main(array $argv): void
{
	$command = $argv[1] ?? 'help';
	$arguments = array_slice($argv, 2);
	$flags = array_values(array_filter($arguments, static fn (string $a): bool => str_starts_with($a, '--')));
	$paths = array_values(array_filter($arguments, static fn (string $a): bool => !str_starts_with($a, '--')));

	match ($command) {
		'inventory' => commandInventory($paths, in_array('--summary', $flags, true)),
		'status' => commandStatus(),
		'mark' => commandMark($paths),
		default => fwrite(STDERR, "Usage: security-scan.php inventory <path>... [--summary] | status | mark <path>...\n"),
	};
}


function projectRoot(): string
{
	static $root = null;
	if ($root === null) {
		$candidate = dirname(__DIR__, 4);
		$root = is_dir($candidate . '/app') ? $candidate : (string) getcwd();
		$root = rtrim(str_replace('\\', '/', $root), '/');
	}

	return $root;
}


function relativePath(string $path): string
{
	$path = str_replace('\\', '/', $path);
	$isAbsolute = preg_match('~^(?:[A-Za-z]:)?/~', $path) === 1;
	if (!$isAbsolute) {
		$fromCwd = str_replace('\\', '/', (string) getcwd()) . '/' . $path;
		$path = file_exists($fromCwd) ? $fromCwd : projectRoot() . '/' . $path;
	}

	$real = realpath($path);
	$path = $real === false ? $path : str_replace('\\', '/', $real);
	$root = projectRoot();
	if (stripos($path, $root . '/') === 0) {
		return substr($path, strlen($root) + 1);
	}

	return $path === $root ? '' : $path;
}


/**
 * @param list<string> $paths
 * @return list<string> paths relative to the project root
 */
function collectFiles(array $paths): array
{
	$files = [];
	foreach ($paths as $path) {
		$relative = relativePath($path);
		$absolute = $relative === '' ? projectRoot() : projectRoot() . '/' . $relative;
		if (is_file($absolute)) {
			$files[$relative] = true;
			continue;
		}

		if (!is_dir($absolute)) {
			fwrite(STDERR, "Not found: $path\n");
			continue;
		}

		$iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS),
			static fn (SplFileInfo $item): bool => !$item->isDir() || !in_array($item->getFilename(), SKIP_DIRS, true),
		));
		foreach ($iterator as $item) {
			if ($item instanceof SplFileInfo && $item->isFile() && isScannable($item->getFilename())) {
				$files[relativePath($item->getPathname())] = true;
			}
		}
	}

	$files = array_keys($files);
	sort($files);

	return $files;
}


function isScannable(string $fileName): bool
{
	if ($fileName === '.htaccess') {
		return true;
	}

	if (str_ends_with($fileName, '.min.js')) {
		return false;
	}

	return in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), SCAN_EXTENSIONS, true);
}


/** @return array<string, array{hash: string, date: string}> */
function loadCoverage(): array
{
	$file = projectRoot() . '/' . COVERAGE_FILE;
	if (!is_file($file)) {
		return [];
	}

	$data = json_decode((string) file_get_contents($file), true);

	return is_array($data) ? $data : [];
}


/** @param array<string, array{hash: string, date: string}> $coverage */
function fileState(string $relative, array $coverage): string
{
	if (!isset($coverage[$relative])) {
		return 'never audited';
	}

	$hash = sha1_file(projectRoot() . '/' . $relative);

	return $hash === $coverage[$relative]['hash']
		? 'audited ' . $coverage[$relative]['date']
		: 'CHANGED since audit ' . $coverage[$relative]['date'];
}


/** @return array<string, mixed> */
function analyseFile(string $relative): array
{
	$code = (string) file_get_contents(projectRoot() . '/' . $relative);
	$fileName = basename($relative);
	$extension = $fileName === '.htaccess' ? 'htaccess' : strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
	$patterns = match ($extension) {
		'php', 'phpt' => PHP_PATTERNS,
		'latte' => LATTE_PATTERNS,
		'js' => JS_PATTERNS,
		'neon' => NEON_PATTERNS,
		'htaccess' => HTACCESS_PATTERNS,
		default => [],
	};

	return [
		'path' => $relative,
		'classes' => in_array($extension, ['php', 'phpt'], true) ? analysePhp($code, $relative) : [],
		'hits' => findHits($code, $patterns, $extension),
	];
}


/**
 * @param array<string, string> $patterns
 * @return list<array{line: int, label: string, code: string}>
 */
function findHits(string $code, array $patterns, string $extension): array
{
	$hits = [];
	$isPhp = in_array($extension, ['php', 'phpt'], true);
	foreach (preg_split('~\R~', $code) ?: [] as $index => $line) {
		$trimmed = trim($line);
		if ($trimmed === '') {
			continue;
		}

		if ($isPhp && preg_match('~^(?://|\*|/\*|use\s|namespace\s)~', $trimmed) === 1) {
			continue;
		}

		// A table alias such as `$f."` in front of a TableMap constant is not a dynamic part of the query.
		$withoutAliases = (string) preg_replace('~\$\w+\.(?=["\'])~', '', $line);
		foreach ($patterns as $label => $pattern) {
			if (preg_match($pattern, $label === 'dynamic-order' ? $withoutAliases : $line) === 1) {
				$hits[] = ['line' => $index + 1, 'label' => $label, 'code' => shorten($trimmed, 150)];
			}
		}
	}

	return $hits;
}


/** @return list<array<string, mixed>> */
function analysePhp(string $code, string $relative): array
{
	$tokens = PhpToken::tokenize($code);
	$count = count($tokens);
	$classes = [];
	$stack = [];
	$pending = null;
	$depth = 0;

	for ($i = 0; $i < $count; $i++) {
		$token = $tokens[$i];

		if ($token->is([T_CLASS, T_TRAIT, T_INTERFACE, T_ENUM])) {
			$previous = previousSignificant($tokens, $i);
			if ($previous !== null && $tokens[$previous]->is(T_DOUBLE_COLON)) {
				continue;
			}

			$next = nextSignificant($tokens, $i);
			$isNamed = $next !== null && $tokens[$next]->is(T_STRING) && !($previous !== null && $tokens[$previous]->is(T_NEW));
			$header = '';
			for ($j = $i; $j < $count && $tokens[$j]->id !== 123; $j++) {
				$header .= $tokens[$j]->text;
			}

			$pending = [
				'kind' => strtolower($token->text),
				'name' => $isNamed && $next !== null ? $tokens[$next]->text : '(anonymous)',
				'line' => $token->line,
				'extends' => preg_match('~\bextends\s+([\\\\\w]+)~', $header, $m) === 1 ? $m[1] : null,
				'methods' => [],
				'depth' => 0,
			];
			continue;
		}

		if ($token->is(T_FUNCTION) && $stack !== [] && $depth === $stack[array_key_last($stack)]['depth']) {
			$method = readMethod($tokens, $i);
			if ($method !== null) {
				$i = $method['endIndex'];
				unset($method['endIndex']);
				$stack[array_key_last($stack)]['methods'][] = $method;
				continue;
			}
		}

		if (isOpenBrace($token)) {
			$depth++;
			if ($pending !== null) {
				$pending['depth'] = $depth;
				$stack[] = $pending;
				$pending = null;
			}

			continue;
		}

		if ($token->id === 125) {
			if ($stack !== [] && $stack[array_key_last($stack)]['depth'] === $depth) {
				$classes[] = array_pop($stack);
			}

			$depth--;
		}
	}

	foreach ($classes as &$class) {
		$class['role'] = classRole($class, $relative);
	}

	usort($classes, static fn (array $a, array $b): int => $a['line'] <=> $b['line']);

	return $classes;
}


/**
 * @param list<PhpToken> $tokens
 * @return array<string, mixed>|null
 */
function readMethod(array $tokens, int $functionIndex): ?array
{
	$count = count($tokens);
	$modifiers = [];
	for ($j = $functionIndex - 1; $j >= 0; $j--) {
		if ($tokens[$j]->isIgnorable()) {
			continue;
		}

		if (!$tokens[$j]->is([T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL])) {
			break;
		}

		$modifiers[] = strtolower($tokens[$j]->text);
	}

	$nameIndex = nextSignificant($tokens, $functionIndex);
	if ($nameIndex !== null && $tokens[$nameIndex]->text === '&') {
		$nameIndex = nextSignificant($tokens, $nameIndex);
	}

	if ($nameIndex === null || $tokens[$nameIndex]->id === 40) {
		return null;
	}

	$open = $nameIndex + 1;
	while ($open < $count && $tokens[$open]->id !== 40) {
		$open++;
	}

	$close = matchPair($tokens, $open, 40, 41);
	if ($close === null) {
		return null;
	}

	$bodyOpen = null;
	for ($j = $close + 1; $j < $count; $j++) {
		if ($tokens[$j]->id === 59) {
			break;
		}

		if ($tokens[$j]->id === 123) {
			$bodyOpen = $j;
			break;
		}
	}

	$end = $bodyOpen === null ? $close : (matchBraces($tokens, $bodyOpen) ?? $count - 1);
	$visibility = array_values(array_intersect(['public', 'protected', 'private'], $modifiers))[0] ?? 'public';
	$body = $bodyOpen === null ? '' : tokensToCode($tokens, $bodyOpen, $end);

	return [
		'name' => $tokens[$nameIndex]->text,
		'visibility' => $visibility,
		'static' => in_array('static', $modifiers, true),
		'line' => $tokens[$functionIndex]->line,
		'endLine' => $tokens[$end]->line,
		'params' => shorten((string) preg_replace('~\s+~', ' ', trim(tokensToCode($tokens, $open + 1, $close - 1))), 90),
		'guard' => describeGuard($body),
		'notes' => describeNotes($body),
		'events' => $bodyOpen === null ? [] : readFormEvents($tokens, $bodyOpen, $end),
		'endIndex' => $end,
	];
}


/**
 * Closures and callables attached to form events inside one method body.
 *
 * @param list<PhpToken> $tokens
 * @return list<array{event: string, line: int, endLine: int, guard: string, notes: string, target: string}>
 */
function readFormEvents(array $tokens, int $from, int $to): array
{
	$events = [];
	for ($i = $from; $i <= $to; $i++) {
		if (!$tokens[$i]->is(T_STRING) || !in_array($tokens[$i]->text, FORM_EVENTS, true)) {
			continue;
		}

		$previous = previousSignificant($tokens, $i);
		if ($previous === null || !$tokens[$previous]->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])) {
			continue;
		}

		$a = nextSignificant($tokens, $i);
		$b = $a === null ? null : nextSignificant($tokens, $a);
		$c = $b === null ? null : nextSignificant($tokens, $b);
		if ($a === null || $b === null || $c === null || $tokens[$a]->id !== 91 || $tokens[$b]->id !== 93 || $tokens[$c]->id !== 61) {
			continue;
		}

		$start = nextSignificant($tokens, $c);
		if ($start !== null && $tokens[$start]->is(T_STATIC)) {
			$start = nextSignificant($tokens, $start);
		}

		if ($start === null) {
			continue;
		}

		$end = $start;
		$target = 'closure';
		if ($tokens[$start]->is(T_FUNCTION)) {
			$bodyOpen = $start;
			while ($bodyOpen <= $to && $tokens[$bodyOpen]->id !== 123) {
				$bodyOpen++;
			}

			$end = matchBraces($tokens, $bodyOpen) ?? $to;
		} else {
			$nesting = 0;
			for ($end = $start; $end <= $to; $end++) {
				if (in_array($tokens[$end]->id, [40, 91, 123], true)) {
					$nesting++;
				} elseif (in_array($tokens[$end]->id, [41, 93, 125], true)) {
					$nesting--;
				} elseif ($tokens[$end]->id === 59 && $nesting <= 0) {
					break;
				}
			}

			if (!$tokens[$start]->is(T_FN)) {
				$target = 'callable ' . shorten((string) preg_replace('~\s+~', ' ', tokensToCode($tokens, $start, $end - 1)), 70);
			}
		}

		$handlerCode = tokensToCode($tokens, $start, min($end, $to));
		$events[] = [
			'event' => $tokens[$i]->text,
			'line' => $tokens[$i]->line,
			'endLine' => $tokens[min($end, $to)]->line,
			'guard' => describeGuard($handlerCode),
			'notes' => describeNotes($handlerCode),
			'target' => $target,
		];
		$i = max($i, min($end, $to));
	}

	return $events;
}


function describeGuard(string $code): string
{
	if (preg_match_all(GUARD_PATTERN, $code, $matches) < 1) {
		return '';
	}

	$calls = array_unique(array_map(static fn (string $m): string => trim($m, " \t\n\r(->:"), $matches[0]));
	preg_match_all('~Resource::(\w+)~', $code, $resources);
	$resources = array_unique($resources[1]);

	return implode(', ', $calls) . ($resources === [] ? '' : ' [' . implode(', ', $resources) . ']');
}


/** A write with no audit call in the same body: the change may leave no trace. */
function describeNotes(string $code): string
{
	return preg_match(WRITE_PATTERN, $code) === 1 && preg_match(AUDIT_PATTERN, $code) !== 1 ? 'write-no-audit' : '';
}


/** @param array<string, mixed> $class */
function classRole(array $class, string $relative): string
{
	$name = (string) $class['name'];
	$extends = (string) ($class['extends'] ?? '');

	return match (true) {
		$class['kind'] === 'interface' => 'interface',
		$class['kind'] === 'enum' => 'enum',
		str_ends_with($name, 'PresenterTrait') => 'presenter trait',
		str_ends_with($name, 'Presenter') || str_ends_with($extends, 'Presenter') => 'presenter',
		str_ends_with($name, 'Control') || str_ends_with($extends, 'Control') => 'control',
		str_ends_with($name, 'FormFactory') => 'form factory',
		str_ends_with($name, 'Repository') => 'repository',
		$class['kind'] === 'trait' => 'trait',
		str_contains($relative, '/UI/') => 'ui class',
		default => 'class',
	};
}


function entryKind(string $methodName): ?string
{
	return match (true) {
		preg_match('~^handle[A-Z]~', $methodName) === 1 => 'signal',
		preg_match('~^action(?:[A-Z]|$)~', $methodName) === 1 => 'action',
		preg_match('~^render(?:[A-Z]|$)~', $methodName) === 1 => 'render',
		preg_match('~^createComponent[A-Z]~', $methodName) === 1 => 'component',
		default => null,
	};
}


/** @param list<PhpToken> $tokens */
function previousSignificant(array $tokens, int $index): ?int
{
	for ($j = $index - 1; $j >= 0; $j--) {
		if (!$tokens[$j]->isIgnorable()) {
			return $j;
		}
	}

	return null;
}


/** @param list<PhpToken> $tokens */
function nextSignificant(array $tokens, int $index): ?int
{
	for ($j = $index + 1, $count = count($tokens); $j < $count; $j++) {
		if (!$tokens[$j]->isIgnorable()) {
			return $j;
		}
	}

	return null;
}


function isOpenBrace(PhpToken $token): bool
{
	return $token->id === 123 || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]);
}


/** @param list<PhpToken> $tokens */
function matchBraces(array $tokens, int $open): ?int
{
	$depth = 0;
	for ($j = $open, $count = count($tokens); $j < $count; $j++) {
		if (isOpenBrace($tokens[$j])) {
			$depth++;
		} elseif ($tokens[$j]->id === 125 && --$depth === 0) {
			return $j;
		}
	}

	return null;
}


/** @param list<PhpToken> $tokens */
function matchPair(array $tokens, int $open, int $openId, int $closeId): ?int
{
	$depth = 0;
	for ($j = $open, $count = count($tokens); $j < $count; $j++) {
		if ($tokens[$j]->id === $openId) {
			$depth++;
		} elseif ($tokens[$j]->id === $closeId && --$depth === 0) {
			return $j;
		}
	}

	return null;
}


/** @param list<PhpToken> $tokens */
function tokensToCode(array $tokens, int $from, int $to): string
{
	$code = '';
	for ($j = $from; $j <= $to; $j++) {
		if (!$tokens[$j]->is([T_COMMENT, T_DOC_COMMENT])) {
			$code .= $tokens[$j]->text;
		}
	}

	return $code;
}


function shorten(string $text, int $length): string
{
	return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
}


function cell(string $text): string
{
	return str_replace('|', '\|', $text);
}


/**
 * Entry points of one analysed file as flat rows.
 *
 * @param array<string, mixed> $analysis
 * @return list<array{class: string, role: string, kind: string, name: string, line: int, endLine: int, params: string, guard: string, notes: string}>
 */
function entryPoints(array $analysis): array
{
	$rows = [];
	foreach ($analysis['classes'] as $class) {
		$isUi = in_array($class['role'], ['presenter', 'presenter trait', 'control', 'ui class', 'trait'], true);
		foreach ($class['methods'] as $method) {
			$kind = $isUi ? entryKind($method['name']) : null;
			if ($kind !== null) {
				$rows[] = [
					'class' => $class['name'], 'role' => $class['role'], 'kind' => $kind, 'name' => $method['name'],
					'line' => $method['line'], 'endLine' => $method['endLine'], 'params' => $method['params'], 'guard' => $method['guard'],
					'notes' => $method['notes'],
				];
			}

			foreach ($method['events'] as $event) {
				$rows[] = [
					'class' => $class['name'], 'role' => $class['role'], 'kind' => 'form ' . $event['event'],
					'name' => $method['name'] . ' → ' . $event['target'], 'line' => $event['line'], 'endLine' => $event['endLine'],
					'params' => '', 'guard' => $event['guard'], 'notes' => $event['notes'],
				];
			}
		}
	}

	return $rows;
}


/** @param list<string> $paths */
function commandInventory(array $paths, bool $summaryOnly): void
{
	if ($paths === []) {
		fwrite(STDERR, "inventory needs at least one path.\n");
		exit(1);
	}

	$coverage = loadCoverage();
	$files = collectFiles($paths);
	$totals = ['files' => count($files), 'entries' => 0, 'unguarded' => 0];
	$byKind = [];
	$output = '';
	$nothingToList = [];

	foreach ($files as $relative) {
		$analysis = analyseFile($relative);
		$entries = entryPoints($analysis);
		$unguarded = count(array_filter($entries, static fn (array $e): bool => $e['guard'] === ''));
		$totals['entries'] += count($entries);
		$totals['unguarded'] += $unguarded;
		foreach ($entries as $entry) {
			$byKind[$entry['kind']] = ($byKind[$entry['kind']] ?? 0) + 1;
		}

		$state = fileState($relative, $coverage);
		if ($summaryOnly) {
			$labels = array_count_values(array_column($analysis['hits'], 'label'));
			$output .= sprintf(
				"- `%s` — %s · %d entry points (%d without a guard in the body)%s\n",
				$relative,
				$state,
				count($entries),
				$unguarded,
				$labels === [] ? '' : ' · ' . implode(', ', array_map(static fn (string $l, int $n): string => $l . '×' . $n, array_keys($labels), $labels)),
			);
			continue;
		}

		if ($entries === [] && $analysis['hits'] === [] && array_filter($analysis['classes'], static fn (array $c): bool => !in_array($c['role'], ['interface', 'enum'], true)) === []) {
			$nothingToList[] = "`$relative` ($state)";
			continue;
		}

		$output .= "\n## $relative — $state\n";
		$output .= renderFile($analysis, $entries);
	}

	arsort($byKind);
	echo sprintf(
		"# Security inventory: %d files, %d entry points, %d without a guard in their own body\n",
		$totals['files'],
		$totals['entries'],
		$totals['unguarded'],
	);
	echo 'By kind: ' . ($byKind === [] ? 'none' : implode(', ', array_map(static fn (string $k, int $n): string => "$k $n", array_keys($byKind), $byKind))) . "\n";
	echo "A missing guard here is a place to read, not a finding: the presenter's startup() may cover it.\n";
	echo $output;
	if ($nothingToList !== []) {
		echo "\nIn scope with no entry point and no risky line (interfaces, enums, plain templates): " . implode(', ', $nothingToList) . "\n";
	}
}


/**
 * @param array<string, mixed> $analysis
 * @param list<array<string, mixed>> $entries
 */
function renderFile(array $analysis, array $entries): string
{
	$output = '';
	$hits = $analysis['hits'];
	$labelsInRange = static function (int $from, int $to) use ($hits): string {
		$labels = [];
		foreach ($hits as $hit) {
			if ($hit['line'] >= $from && $hit['line'] <= $to && !in_array($hit['label'], INFO_LABELS, true)) {
				$labels[$hit['label']] = true;
			}
		}

		return implode(', ', array_keys($labels));
	};

	foreach ($analysis['classes'] as $class) {
		if (in_array($class['role'], ['interface', 'enum'], true)) {
			continue;
		}

		$output .= sprintf("\n%s `%s`%s\n", ucfirst($class['role']), $class['name'], $class['extends'] !== null ? ' extends `' . $class['extends'] . '`' : '');
		foreach ($class['methods'] as $method) {
			if (in_array($method['name'], POLICY_METHODS, true)) {
				$output .= sprintf("- policy method `%s()` at line %d%s\n", $method['name'], $method['line'], $method['guard'] === '' ? '' : ' — ' . $method['guard']);
			}
		}

		$isUi = in_array($class['role'], ['presenter', 'presenter trait', 'control', 'ui class', 'trait'], true);
		if ($isUi) {
			continue;
		}

		$public = array_filter(
			$class['methods'],
			static fn (array $m): bool => $m['visibility'] === 'public' && $m['name'] !== '__construct' && !str_starts_with($m['name'], 'inject'),
		);
		if ($public !== []) {
			$output .= "\n| line | public method | parameters | risky constructs inside |\n|---|---|---|---|\n";
			foreach ($public as $method) {
				$output .= sprintf(
					"| %d | `%s` | %s | %s |\n",
					$method['line'],
					$method['name'],
					cell($method['params']),
					$labelsInRange($method['line'], $method['endLine']),
				);
			}
		}
	}

	if ($entries !== []) {
		$output .= "\n| line | entry point | parameters | guard in its own body | risky constructs inside |\n|---|---|---|---|---|\n";
		foreach ($entries as $entry) {
			$output .= sprintf(
				"| %d | %s `%s::%s` | %s | %s | %s |\n",
				$entry['line'],
				$entry['kind'],
				$entry['class'],
				cell($entry['name']),
				cell($entry['params']),
				$entry['guard'] === '' ? '**none**' : cell($entry['guard']),
				implode(', ', array_filter([$labelsInRange($entry['line'], $entry['endLine']), $entry['notes']])),
			);
		}
	}

	foreach (['Risky lines' => false, 'Components, forms and calls (for the map, not risky by themselves)' => true] as $title => $informational) {
		$selected = array_filter($hits, static fn (array $hit): bool => in_array($hit['label'], INFO_LABELS, true) === $informational);
		if ($selected === []) {
			continue;
		}

		$output .= "\n$title:\n";
		foreach ($selected as $hit) {
			$output .= sprintf("- %d `%s` — `%s`\n", $hit['line'], $hit['label'], str_replace('`', "'", $hit['code']));
		}
	}

	return $output;
}


function unitOf(string $relative): string
{
	$parts = explode('/', $relative);
	$isDir = static fn (int $segments): bool => count($parts) > $segments;

	return match (true) {
		str_starts_with($relative, 'app/UI/Admin/Control/') && $isDir(5) => implode('/', array_slice($parts, 0, 5)),
		str_starts_with($relative, 'app/UI/Admin/Form/') && $isDir(5) => implode('/', array_slice($parts, 0, 5)),
		str_starts_with($relative, 'app/UI/') && $isDir(4) => implode('/', array_slice($parts, 0, 4)),
		str_starts_with($relative, 'app/UI/') => implode('/', array_slice($parts, 0, count($parts) - 1)) . ' (base files)',
		str_starts_with($relative, 'app/Model/') && $isDir(4) => implode('/', array_slice($parts, 0, 4)),
		str_starts_with($relative, 'app/Model/') && $isDir(3) => implode('/', array_slice($parts, 0, 3)),
		str_starts_with($relative, 'app/') && $isDir(2) => implode('/', array_slice($parts, 0, 2)),
		str_starts_with($relative, 'www/js/') => 'www/js',
		count($parts) > 1 => $parts[0],
		default => '(root)',
	};
}


function commandStatus(): void
{
	$coverage = loadCoverage();
	$units = [];
	foreach (collectFiles(STATUS_SCOPE) as $relative) {
		$unit = unitOf($relative);
		$units[$unit] ??= ['files' => 0, 'never' => 0, 'changed' => 0, 'audited' => 0, 'signals' => 0, 'forms' => 0, 'sensitive' => 0, 'unguarded' => 0, 'score' => 0];
		$state = fileState($relative, $coverage);
		$bucket = str_starts_with($state, 'never') ? 'never' : (str_starts_with($state, 'CHANGED') ? 'changed' : 'audited');
		$units[$unit]['files']++;
		$units[$unit][$bucket]++;

		$analysis = analyseFile($relative);
		$entries = entryPoints($analysis);
		$signals = count(array_filter($entries, static fn (array $e): bool => $e['kind'] === 'signal'));
		$forms = count(array_filter($entries, static fn (array $e): bool => str_starts_with($e['kind'], 'form ')));
		$unguarded = count(array_filter($entries, static fn (array $e): bool => $e['guard'] === ''));
		$sensitive = count(array_filter($analysis['hits'], static fn (array $h): bool => in_array($h['label'], SENSITIVE_LABELS, true)));
		$units[$unit]['signals'] += $signals;
		$units[$unit]['forms'] += $forms;
		$units[$unit]['sensitive'] += $sensitive;
		$units[$unit]['unguarded'] += $unguarded;
		if ($bucket !== 'audited') {
			$units[$unit]['score'] += $unguarded * 3 + $signals * 2 + $forms + $sensitive;
		}
	}

	uasort($units, static fn (array $a, array $b): int => [$b['score'], $b['files']] <=> [$a['score'], $a['files']]);

	$totalFiles = array_sum(array_column($units, 'files'));
	$totalAudited = array_sum(array_column($units, 'audited'));
	echo sprintf("# Security audit coverage: %d of %d files audited in their current state\n\n", $totalAudited, $totalFiles);
	$next = array_key_first(array_filter($units, static fn (array $row): bool => $row['never'] + $row['changed'] > 0));
	echo 'Next unit: ' . ($next === null ? 'none, everything in scope is audited in its current state.' : "`$next`") . "\n\n";
	echo "Sorted by exposure of what is still unaudited (score = unguarded×3 + signals×2 + form handlers + sensitive lines).\n\n";
	echo "| unit | files | never | changed | audited | signals | form handlers | sensitive lines | no guard in body | score |\n|---|---|---|---|---|---|---|---|---|---|\n";
	$quiet = [];
	foreach ($units as $unit => $row) {
		if ($row['score'] === 0 && $row['audited'] === 0 && $row['changed'] === 0) {
			$quiet[] = "`$unit` (" . $row['files'] . ')';
			continue;
		}

		echo sprintf(
			"| `%s` | %d | %d | %d | %d | %d | %d | %d | %d | %d |\n",
			$unit,
			$row['files'],
			$row['never'],
			$row['changed'],
			$row['audited'],
			$row['signals'],
			$row['forms'],
			$row['sensitive'],
			$row['unguarded'],
			$row['score'],
		);
	}

	if ($quiet !== []) {
		echo "\nNever audited, score 0 (no entry point and no sensitive line; file count in brackets): " . implode(', ', $quiet) . "\n";
	}

	echo "\nFolders under the web root that hold files (the rules shown decide whether Apache serves them):\n";
	$rootHtaccess = projectRoot() . '/www/.htaccess';
	$rootRules = is_file($rootHtaccess) ? (preg_split('~\R~', (string) file_get_contents($rootHtaccess)) ?: []) : [];
	foreach (PUBLIC_FILE_DIRS as $dir) {
		$folder = preg_quote(basename($dir), '~');
		$rules = array_values(array_filter(
			$rootRules,
			// The folder has to be in the pattern the rule matches, not in the address it redirects to.
			static fn (string $line): bool => preg_match('~^\s*(?:RewriteRule|RedirectMatch|Redirect)\s+\S*\b' . $folder . '\b~i', $line) === 1,
		));
		$absolute = projectRoot() . '/' . $dir;
		$count = 0;
		if (is_dir($absolute)) {
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS)) as $item) {
				if ($item instanceof SplFileInfo && $item->isFile() && $item->getFilename() !== '.htaccess') {
					$count++;
				}
			}
		}

		echo sprintf(
			"- `%s`: %d files; own .htaccess: %s; rule in www/.htaccess: %s\n",
			$dir,
			$count,
			is_file($absolute . '/.htaccess') ? 'yes' : 'no',
			$rules === [] ? 'none found' : '`' . trim($rules[0]) . '`',
		);
	}
}


/** @param list<string> $paths */
function commandMark(array $paths): void
{
	if ($paths === []) {
		fwrite(STDERR, "mark needs at least one path.\n");
		exit(1);
	}

	$files = collectFiles($paths);
	$file = projectRoot() . '/' . COVERAGE_FILE;
	if (!is_dir(dirname($file))) {
		mkdir(dirname($file), 0777, true);
	}

	// Read right before writing: other sessions mark their own files in the same ledger.
	$coverage = loadCoverage();
	foreach ($files as $relative) {
		$coverage[$relative] = ['hash' => (string) sha1_file(projectRoot() . '/' . $relative), 'date' => date('Y-m-d')];
	}

	ksort($coverage);
	$temporary = $file . '.' . getmypid() . '.tmp';
	file_put_contents($temporary, json_encode($coverage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
	rename($temporary, $file);

	echo sprintf("Marked %d files as audited on %s.\n", count($files), date('Y-m-d'));
}
