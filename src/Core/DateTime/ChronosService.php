<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use Windwalker\Core\Runtime\Config;
use Windwalker\Database\DatabaseAdapter;

/**
 * The ChronosManager class.
 */
class ChronosService
{
    /**
     * ChronosService constructor.
     *
     * @param  Config  $config
     * @param  DatabaseAdapter  $db
     */
    public function __construct(
        protected Config $config,
        protected DatabaseAdapter $db
    ) {
        //
    }

    public function getTimezone(): string
    {
        return $this->config->getDeep('app.timezone');
    }

    public function getServerTimezone(): string
    {
        return $this->config->getDeep('app.server_timezone');
    }

    public function getSqlFormat(): string
    {
        return $this->db->getDateFormat();
    }

    public function getNullDate(): string
    {
        return $this->db->getNullDate();
    }

    public function isNullDate(string|int|null|\DateTimeInterface $date): bool
    {
        return $this->db->isNullDate($date);
    }

    public function toServer(mixed $date, string|\DateTimeZone $from = null): Chronos
    {
        $from = $from ?? $this->getTimezone();

        return static::convert($date, $from, $this->getServerTimezone());
    }

    public function toServerFormat(
        mixed $date,
        string $format = Chronos::FORMAT_YMD_HIS,
        string|\DateTimeZone $from = null
    ): string {
        return $this->toServer($date, $from)->format($format);
    }

    public function toLocal(mixed $date, string|\DateTimeZone $to = null): Chronos
    {
        $to = $to ?? $this->getTimezone();

        return static::convert($date, $this->getServerTimezone(), $to);
    }

    public function toLocalFormat(
        mixed $date,
        string $format = Chronos::FORMAT_YMD_HIS,
        string|\DateTimeZone $to = null
    ): string {
        return $this->toLocal($date, $to)->format($format);
    }

    public static function convert(
        mixed $date,
        string|\DateTimeZone $from = 'UTC',
        string|\DateTimeZone $to = 'UTC'
    ): Chronos {
        $from = new \DateTimeZone($from);
        $to   = new \DateTimeZone($to);
        $date = Chronos::wrap($date, $from);

        $date = $date->setTimezone($to);

        return $date;
    }
}
