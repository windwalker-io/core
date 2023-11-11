<?php

declare(strict_types=1);

namespace Windwalker\SRP;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use Brick\Math\Exception\NumberFormatException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Windwalker\Utilities\Options\OptionsResolverTrait;

use const Windwalker\Crypt\SECRET_256BIT;

class SRPServer
{
    use OptionsResolverTrait;

    protected const PRIME = '217661744586174357731910088918027537819076683742555385111446432246898862353838409572109' .
    '090130860564015713997172358072665816496064721484102914133641521973644771808873956554837381150726774022351017625' .
    '219015698207402931495296204193332662620734710545483687360395197024862265062488610602569718029849535611214426801' .
    '576680007614299882224570904138739739701719270939921147517651680636147611196154762334220964427831179712363716473' .
    '338714143358957734746673089670508070055093204247996784170368679283167612722742303140675482911335824795830614395' .
    '77559347101961771406173684378522703483495337037655006751328447510550299250924469288819';

    protected string $identity;

    protected BigInteger $verifier;

    protected BigInteger $salt;

    /**
     * Server private key
     *
     * @var BigInteger
     */
    protected BigInteger $b;

    /**
     * Server public key
     *
     * @var BigInteger
     */
    protected BigInteger $B;

    /**
     * Constant computed by the server.
     *
     * @var BigInteger
     */
    protected BigInteger $k;

    /**
     * Shared secret key
     *
     * @var BigInteger
     */
    protected BigInteger $K;

    /**
     * Shared secret hashed form
     *
     * @var string
     */
    protected string $S;

    protected int $step = 1;

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
     * @throws DivisionByZeroException
     * @throws NegativeNumberException
     * @throws MathException
     * @throws NumberFormatException
     */
    public function step1(string $identity, BigInteger $salt, BigInteger $verifier): BigInteger
    {
        static::checkNotEmpty($identity, 'identity');
        static::checkNotEmpty($salt, 'salt');
        static::checkNotEmpty($verifier, 'verifier');

        $this->identity = $identity;
        $this->verifier = $verifier;
        $this->salt = $salt;

        $b = $this->generateRandomPrivate();
        $B = $this->generatePublic($b, $verifier);

        $this->step = 1;

        return $B;
    }

    public function step2(
        string $identity,
        BigInteger $salt,
        BigInteger $verifier,
        BigInteger $A,
        BigInteger $b,
        BigInteger $M1
    ) {
        static::checkNotEmpty($A, 'A');
        static::checkNotEmpty($M1, 'M1');

        $B = $this->generatePublic($b, $verifier);

        $u = $this->computeU($A, $B);

        $S = $this->generatePreMasterSecret($A, $b, $verifier, $u);

        // K = H(S)
        $K = $this->hash($S);

        // M = H(H(N) xor H(g), H(I), s, A, B, K)
        $M2 = $this->hash(
            $this->hash($this->getPrime())
                ->xor(
                    $this->hash($this->getGenerator())
                ),
            $this->hash($identity),
            $salt, // s
            $A,
            $B,
            $K
        );

        if (!hash_equals((string) $M2, (string) $M1)) {
            throw new \InvalidArgumentException('Invalid client session proof', 401);
        }

        $proof = $this->hash($A, $M2, $K);
        $key = $K;

        return compact('key', 'proof');
    }

    /**
     * Generate random [b]
     *
     * (b = random())
     *
     * @param  int  $length
     *
     * @return  BigInteger (b)
     *
     * @throws NumberFormatException
     * @throws \Exception
     */
    public function generateRandomPrivate(int $length = SECRET_256BIT): BigInteger
    {
        $hex = bin2hex(random_bytes($length));

        return BigInteger::fromBase($hex, 16);
    }

    /**
     * Generate public (B)
     *
     * ((k*v + g^b) % N)
     *
     * @param  BigInteger  $private  (b)
     *
     * @return  BigInteger (B)
     *
     * @throws \Brick\Math\Exception\DivisionByZeroException
     * @throws \Brick\Math\Exception\MathException
     * @throws \Brick\Math\Exception\NegativeNumberException
     */
    public function generatePublic(BigInteger $private, BigInteger $verifier): BigInteger
    {
        return $this->getGenerator()
            ->modPow($private, $this->getPrime())
            ->plus($verifier->multipliedBy($this->getKey()))
            ->mod($this->getPrime());
    }

    /**
     * @throws DivisionByZeroException
     * @throws NegativeNumberException
     * @throws MathException
     */
    public function generatePreMasterSecret(
        BigInteger $A,
        BigInteger $b,
        BigInteger $verifier,
        BigInteger $u
    ): BigInteger {
        return $verifier->modPow($u, $this->getPrime())
            ->multipliedBy($A)
            ->modPow($b, $this->getPrime());
    }

    public function generateSessionKey(
        BigInteger $public, // A
        BigInteger $serverPublic, // B
        BigInteger $serverPrivate, // b
        BigInteger $x,
        BigInteger $userSecret // v
    )
    {
        $N = $this->getPrime();

        // (B - kg^x) % N
        $kgx = $this->getKey()->multipliedBy(
            $this->getGenerator()->modPow($x, $N)
        );
        $B2 = $serverPublic->minus($kgx)->mod($N);
        // (a + ux) % N
    }

    /**
     * [u] = HASH(PAD(A) | PAD(B))
     *
     * @param  BigInteger  $A
     * @param  BigInteger  $B
     *
     * @return  BigInteger
     * @throws NumberFormatException
     */
    protected function computeU(BigInteger $A, BigInteger $B): BigInteger
    {
        static::checkNotEmpty($A, 'A');
        static::checkNotEmpty($B, 'B');

        return BigInteger::fromBase(
            $this->hashToString($this->pad($A) . $this->pad($B)),
            16
        );
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

    protected function hash(\Stringable|string ...$args): BigInteger
    {
        return BigInteger::of($this->hashToString(implode('', $args)));
    }

    protected function hashToString(\Stringable|string $str): string
    {
        return hash($this->getOption('algo', 'sha256'), (string) $str);
    }

    protected static function checkNotEmpty(mixed $num, string $name): void
    {
        if (!$num) {
            throw new \UnexpectedValueException("Value: `$name` should not be empty.");
        }

        if ($num instanceof BigNumber && $num->isZero()) {
            throw new \UnexpectedValueException("Value: `$name` should not be zero.");
        }
    }

    protected static function intToBytes(BigInteger $val): string
    {
        $hexStr = $val->toBase(16);

        $hexStr = strlen($hexStr) % 2 ? '0' . $hexStr : $hexStr;

        return pack('H*', $hexStr);
    }

    protected function pad(BigInteger $val): string
    {
        $length = strlen(static::intToBytes($this->getPrime()));

        return str_pad((string) $val, $length, '0');
    }
}
