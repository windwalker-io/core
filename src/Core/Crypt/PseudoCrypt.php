<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Crypt;

/**
 * PseudoCrypt by KevBurns (http://blog.kevburnsjr.com/php-unique-hash)
 * Reference/source: http://stackoverflow.com/a/1464155/933782
 */
class PseudoCrypt
{
    /* Key: Next prime greater than 62 ^ n / 1.618033988749894848 */
    /* Value: modular multiplicative inverse */
    private static array $goldenPrimes = [
        '1' => '1',
        '41' => '59',
        '2377' => '1677',
        '147299' => '187507',
        '9132313' => '5952585',
        '566201239' => '643566407',
        '35104476161' => '22071637057',
        '2176477521929' => '294289236153',
        '134941606358731' => '88879354792675',
        '8366379594239857' => '7275288500431249',
        '518715534842869223' => '280042546585394647',
    ];

    /* Ascii :                    0  9,         A  Z,         a  z     */
    /* $chars = array_merge(range(48,57), range(65,90), range(97,122)) */
    private static array $chars62 = [
        0 => 48,
        1 => 49,
        2 => 50,
        3 => 51,
        4 => 52,
        5 => 53,
        6 => 54,
        7 => 55,
        8 => 56,
        9 => 57,
        10 => 65,
        11 => 66,
        12 => 67,
        13 => 68,
        14 => 69,
        15 => 70,
        16 => 71,
        17 => 72,
        18 => 73,
        19 => 74,
        20 => 75,
        21 => 76,
        22 => 77,
        23 => 78,
        24 => 79,
        25 => 80,
        26 => 81,
        27 => 82,
        28 => 83,
        29 => 84,
        30 => 85,
        31 => 86,
        32 => 87,
        33 => 88,
        34 => 89,
        35 => 90,
        36 => 97,
        37 => 98,
        38 => 99,
        39 => 100,
        40 => 101,
        41 => 102,
        42 => 103,
        43 => 104,
        44 => 105,
        45 => 106,
        46 => 107,
        47 => 108,
        48 => 109,
        49 => 110,
        50 => 111,
        51 => 112,
        52 => 113,
        53 => 114,
        54 => 115,
        55 => 116,
        56 => 117,
        57 => 118,
        58 => 119,
        59 => 120,
        60 => 121,
        61 => 122,
    ];

    /**
     * @param  int  $offset
     */
    public function __construct(protected int $offset = 0)
    {
    }

    public static function base62(int|string $int): string
    {
        $int = (string) $int;
        $key = '';

        while (bccomp($int, '0') > 0) {
            $mod = bcmod($int, '62');
            $key .= chr(static::$chars62[$mod]);
            $int = bcdiv($int, '62');
        }

        return strrev($key);
    }

    public function hash(int|string $num, int $len = 5): string
    {
        $num = (string) $num;

        $ceil = bcpow('62', (string) $len);
        $primes = array_keys(static::$goldenPrimes);
        $prime = $primes[$len];
        $dec = bcmod(bcmul($num, (string) $prime), $ceil);

        $hash = static::base62(bcadd($dec, (string) $this->getOffset()));

        return str_pad($hash, $len, "0", STR_PAD_LEFT);
    }

    public static function unbase62(string $key): int|string
    {
        $int = 0;

        foreach (str_split(strrev($key)) as $i => $char) {
            $dec = array_search(ord($char), self::$chars62);
            $int = bcadd(bcmul((string) $dec, bcpow('62', (string) $i)), (string) $int);
        }

        return $int;
    }

    public function unhash(string $hash): int|string
    {
        $len = strlen($hash);
        $ceil = bcpow('62', (string) $len);
        $mmiprimes = array_values(static::$goldenPrimes);
        $mmi = $mmiprimes[$len];
        $num = static::unbase62($hash);
        $dec = bcmod(bcmul($num, $mmi), $ceil);

        return bcsub($dec, (string) $this->getOffset());
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param  int  $offset
     *
     * @return  static  Return self to support chaining.
     */
    public function setOffset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }
}
