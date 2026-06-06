<?php declare(strict_types=1);

/**
 * Builds a tiny, deterministic ISO Base Media (MP4) file with a single 1-second
 * video track so getID3 reports a video stream + playtime. No encoder needed.
 *
 * Run: php tests/Support/fixtures/build-sample-video.php
 * Output: tests/Support/fixtures/sample-video.mp4
 */

function atom(string $type, string $payload): string
{
	return pack('N', 8 + strlen($payload)) . $type . $payload;
}

function fullBox(int $version, int $flags, string $payload): string
{
	return pack('C', $version) . substr(pack('N', $flags), 1) . $payload;
}

$timescale = 1000;
$duration = 1000; // 1 second

$ftyp = atom('ftyp', 'isom' . pack('N', 0) . 'isom' . 'mp41');

$matrix = pack('N9', 0x00010000, 0, 0, 0, 0x00010000, 0, 0, 0, 0x40000000);

$mvhd = atom('mvhd', fullBox(0, 0,
	pack('N', 0) . pack('N', 0) . pack('N', $timescale) . pack('N', $duration)
	. pack('N', 0x00010000) . pack('n', 0x0100) . str_repeat("\0", 10)
	. $matrix . str_repeat("\0", 24) . pack('N', 2),
));

$tkhd = atom('tkhd', fullBox(0, 7,
	pack('N', 0) . pack('N', 0) . pack('N', 1) . pack('N', 0) . pack('N', $duration)
	. str_repeat("\0", 8) . pack('n', 0) . pack('n', 0) . pack('n', 0) . pack('n', 0)
	. $matrix . pack('N', 320 << 16) . pack('N', 240 << 16),
));

$mdhd = atom('mdhd', fullBox(0, 0,
	pack('N', 0) . pack('N', 0) . pack('N', $timescale) . pack('N', $duration)
	. pack('n', 0x55c4) . pack('n', 0),
));

$hdlr = atom('hdlr', fullBox(0, 0,
	pack('N', 0) . 'vide' . str_repeat("\0", 12) . "VideoHandler\0",
));

$vmhd = atom('vmhd', fullBox(0, 1, pack('n', 0) . str_repeat("\0", 6)));

$url = atom('url ', fullBox(0, 1, ''));
$dref = atom('dref', fullBox(0, 0, pack('N', 1) . $url));
$dinf = atom('dinf', $dref);

$compressorName = str_repeat("\0", 32);
$mp4v = atom('mp4v',
	str_repeat("\0", 6) . pack('n', 1)
	. pack('n', 0) . pack('n', 0) . str_repeat("\0", 12)
	. pack('n', 320) . pack('n', 240)
	. pack('N', 0x00480000) . pack('N', 0x00480000) . pack('N', 0)
	. pack('n', 1) . $compressorName . pack('n', 0x0018) . pack('n', 0xFFFF),
);
$stsd = atom('stsd', fullBox(0, 0, pack('N', 1) . $mp4v));
$stts = atom('stts', fullBox(0, 0, pack('N', 1) . pack('N', 1) . pack('N', $duration)));
$stsc = atom('stsc', fullBox(0, 0, pack('N', 0)));
$stsz = atom('stsz', fullBox(0, 0, pack('N', 0) . pack('N', 0)));
$stco = atom('stco', fullBox(0, 0, pack('N', 0)));
$stbl = atom('stbl', $stsd . $stts . $stsc . $stsz . $stco);

$minf = atom('minf', $vmhd . $dinf . $stbl);
$mdia = atom('mdia', $mdhd . $hdlr . $minf);
$trak = atom('trak', $tkhd . $mdia);
$moov = atom('moov', $mvhd . $trak);

$mp4 = $ftyp . $moov;

$target = __DIR__ . '/sample-video.mp4';
file_put_contents($target, $mp4);

echo 'Wrote ' . strlen($mp4) . " bytes to $target\n";
