<?php

declare(strict_types=1);

namespace Windwalker\SRP;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\NumberFormatException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Windwalker\Utilities\Options\OptionsResolverTrait;

use const Windwalker\Crypt\SECRET_128BIT;

class SRPServer
{
    use OptionsResolverTrait;

    protected const PRIME = '217661744586174357731910088918027537819076683742555385111446432246898862353838409572109' .
    '090130860564015713997172358072665816496064721484102914133641521973644771808873956554837381150726774022351017625' .
    '219015698207402931495296204193332662620734710545483687360395197024862265062488610602569718029849535611214426801' .
    '576680007614299882224570904138739739701719270939921147517651680636147611196154762334220964427831179712363716473' .
    '338714143358957734746673089670508070055093204247996784170368679283167612722742303140675482911335824795830614395' .
    '77559347101961771406173684378522703483495337037655006751328447510550299250924469288819';

    public function __construct(array $options = [])
    {
        $this->resolveOptions(
            $options,
            $this->configureOptions(...)
        );
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('prime')
            ->allowedTypes('string')
            ->default(static::PRIME);

        $resolver->define('generator')
            ->allowedTypes('string')
            ->default('2');

        $resolver->define('key')
            ->allowedTypes('string')
            ->default('5b9e8ef059c6b32ea59fc1d322d37f04aa30bae5aa9003b8321e21ddb04e300');

        $resolver->define('algo')
            ->allowedTypes('string')
            ->default('sha256');
    }

    /**
     * Generate Salt (a)
     *
     * @param  int  $length
     *
     * @return  BigInteger
     *
     * @throws \Brick\Math\Exception\NumberFormatException
     */
    public function generatePrivate(int $length = SECRET_128BIT): BigInteger
    {
        $hex = bin2hex(random_bytes($length));

        return BigInteger::fromBase($hex, 16);
    }

    /**
     * Generate public (A)
     *
     * @param  BigInteger  $private  (a)
     *
     * @return  BigInteger
     *
     * @throws \Brick\Math\Exception\DivisionByZeroException
     * @throws \Brick\Math\Exception\MathException
     * @throws \Brick\Math\Exception\NegativeNumberException
     */
    public function generatePublic(BigInteger $private): BigInteger
    {
        // A = g^a % N
        return $this->getGenerator()->modPow($private, $this->getPrime());
    }

    public function generateSessionKey(
        BigInteger $public, // A
        BigInteger $serverPublic, // B
        BigInteger $serverPrivate, // b
        BigInteger $x,
        BigInteger $userSecret // v
    ) {
        $N = $this->getPrime();

        // (B - kg^x) % N
        $kgx = $this->getKey()->multipliedBy(
            $this->getGenerator()->modPow($x, $N)
        );
        $B2 = $serverPublic->minus($kgx)->mod($N);

        // (a + ux) % N
        $u
    }

    /**
     * (N).
     *
     * @return  BigInteger
     *
     * @throws \Brick\Math\Exception\MathException
     */
    public function getPrime(): BigInteger
    {
        return BigInteger::of($this->getOption('prime'));
    }

    /**
     * (g).
     *
     * @return  BigInteger
     *
     * @throws \Brick\Math\Exception\MathException
     */
    public function getGenerator(): BigInteger
    {
        return BigInteger::of($this->getOption('generator'));
    }

    /**
     * (k).
     *
     * @return BigInteger
     * @throws NumberFormatException
     */
    public function getKey(): BigInteger
    {
        return BigInteger::fromBase(
            $this->getOption('key'),
            16
        );
    }
}
