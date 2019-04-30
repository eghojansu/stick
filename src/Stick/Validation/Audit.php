<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Validation;

use Fal\Stick\Fw;

/**
 * Audit class.
 *
 * Ported from F3\Audit.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Audit
{
    const UA_MOBILE = 'android|blackberry|phone|ipod|palm|windows\s+ce';
    const UA_DESKTOP = 'bsd|linux|os\s+[x9]|solaris|windows';
    const UA_BOT = 'bot|crawl|slurp|spider';

    /**
     * @var Fw
     */
    protected $fw;

    /**
     * Class constructor.
     *
     * @param Fw $fw
     */
    public function __construct(Fw $fw)
    {
        $this->fw = $fw;
    }

    /**
     * Return TRUE if string is a valid URL.
     *
     * @param string $str
     *
     * @return bool
     */
    public function url(string $str): bool
    {
        return is_string(filter_var($str, FILTER_VALIDATE_URL));
    }

    /**
     * Return TRUE if string is a valid e-mail address,Check DNS MX records if specified.
     *
     * @param string $str
     * @param bool   $mx
     *
     * @return bool
     */
    public function email(string $str, bool $mx = true): bool
    {
        $hosts = array();

        return is_string(filter_var($str, FILTER_VALIDATE_EMAIL)) && (!$mx || getmxrr(substr($str, strrpos($str, '@') + 1), $hosts));
    }

    /**
     * Return TRUE if string is a valid IPV4 address.
     *
     * @param string $addr
     *
     * @return bool
     */
    public function ipv4(string $addr): bool
    {
        return (bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * Return TRUE if string is a valid IPV6 address.
     *
     * @param string $addr
     *
     * @return bool
     */
    public function ipv6(string $addr): bool
    {
        return (bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * Return TRUE if IP address is within private range.
     *
     * @param string $addr
     *
     * @return bool
     */
    public function isPrivate(string $addr): bool
    {
        return !(bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE);
    }

    /**
     * Return TRUE if IP address is within reserved range.
     *
     * @param string $addr
     *
     * @return bool
     */
    public function isReserved(string $addr): bool
    {
        return !(bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Return TRUE if IP address is neither private nor reserved.
     *
     * @param string $addr
     *
     * @return bool
     */
    public function isPublic(string $addr): bool
    {
        return (bool) filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Return TRUE if user agent is a desktop browser.
     *
     * @param string|null $agent
     *
     * @return bool
     */
    public function isDesktop(string $agent = null): bool
    {
        if (null === $agent) {
            $agent = $this->fw->get('AGENT');
        }

        return (bool) preg_match('/('.self::UA_DESKTOP.')/i', $agent) && !$this->ismobile($agent);
    }

    /**
     * Return TRUE if user agent is a mobile device.
     *
     * @param string|null $agent
     *
     * @return bool
     */
    public function isMobile(string $agent = null): bool
    {
        return (bool) preg_match('/('.self::UA_MOBILE.')/i', $agent ?? $this->fw->get('AGENT'));
    }

    /**
     * Return TRUE if user agent is a Web bot.
     *
     * @param string|null $agent
     *
     * @return bool
     */
    public function isBot(string $agent = null): bool
    {
        return (bool) preg_match('/('.self::UA_BOT.')/i', $agent ?? $this->fw->get('AGENT'));
    }

    /**
     * Return TRUE if specified ID has a valid (Luhn) Mod-10 check digit.
     *
     * @param string $id
     *
     * @return bool
     */
    public function mod10(string $id): bool
    {
        if (!ctype_digit($id)) {
            return false;
        }

        $id = strrev($id);
        $sum = 0;

        for ($i = 0,$l = strlen($id); $i < $l; ++$i) {
            $sum += $id[$i] + $i % 2 * (($id[$i] > 4) * -4 + $id[$i] % 5);
        }

        return !($sum % 10);
    }

    /**
     * Return credit card type if number is valid.
     *
     * @param string $id
     *
     * @return string|null
     */
    public function card(string $id): ?string
    {
        $id = preg_replace('/[^\d]/', '', $id);

        if ($this->mod10($id)) {
            $rules = array(
                '3[47][0-9]{13}' => 'American Express',
                '3(?:0[0-5]|[68][0-9])[0-9]{11}' => 'Diners Club',
                '6(?:011|5[0-9][0-9])[0-9]{12}' => 'Discover',
                '(?:2131|1800|35\d{3})\d{11}' => 'JCB',
                '(5[1-5][0-9]{14})|((222[1-9]|2[3-6]\d{2}|27[0-1]\d|2720)\d{12})' => 'MasterCard',
                '4[0-9]{12}(?:[0-9]{3})?' => 'Visa',
            );

            foreach ($rules as $rule => $vendor) {
                if (preg_match('/^'.$rule.'$/', $id)) {
                    return $vendor;
                }
            }
        }

        return null;
    }

    /**
     * Return entropy estimate of a password (NIST 800-63).
     *
     * @param string $str
     *
     * @return float
     */
    public function entropy(string $str): float
    {
        $len = strlen($str);
        $entropy = 4 * min($len, 1) + ($len > 1 ? (2 * (min($len, 8) - 1)) : 0) +
            ($len > 8 ? (1.5 * (min($len, 20) - 8)) : 0) + ($len > 20 ? ($len - 20) : 0) +
            6 * (bool) (preg_match('/[A-Z].*?[0-9[:punct:]]|[0-9[:punct:]].*?[A-Z]/', $str));

        return (float) $entropy;
    }
}
