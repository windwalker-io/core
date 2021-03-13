<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Seed;

use Faker\Factory;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Windwalker\DI\Container;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

use function Windwalker\DI\create;
use function Windwalker\Promise\resolve;

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
     * @param string $locale
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
     * @param string $locale
     * @param bool   $new
     *
     * @return  FakerGenerator
     *
     * @throws \Psr\Cache\InvalidArgumentException
     *
     * @since  3.5
     */
    public function get(string $locale = FakerFactory::DEFAULT_LOCALE, bool $new = false): FakerGenerator
    {
        return $this->once($locale, fn() => $this->create($locale), $new);
    }
}
