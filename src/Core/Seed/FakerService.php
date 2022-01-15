<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Seed;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Psr\Cache\InvalidArgumentException;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

/**
 * The FakerService class.
 *
 * @since  3.5
 */
class FakerService implements EventAwareInterface
{
    use InstanceCacheTrait;
    use EventAwareTrait;

    /**
     * create
     *
     * @param  string  $locale
     *
     * @return  FakerGenerator
     *
     * @since  3.5
     */
    public function create(string $locale = FakerFactory::DEFAULT_LOCALE): FakerGenerator
    {
        $locale = str_replace('-', '_', $locale);

        $faker = FakerFactory::create($locale);

        $event = $this->emit(
            'faker.created',
            compact('faker')
        );

        return $event['faker'];
    }

    /**
     * getInstance
     *
     * @param  string  $locale
     * @param  bool    $new
     *
     * @return  FakerGenerator
     *
     * @throws InvalidArgumentException
     *
     * @since  3.5
     */
    public function get(string $locale = FakerFactory::DEFAULT_LOCALE, bool $new = false): FakerGenerator
    {
        return $this->once($locale, fn() => $this->create($locale), $new);
    }
}
