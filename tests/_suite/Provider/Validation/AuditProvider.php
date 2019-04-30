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

namespace Fal\Stick\TestSuite\Provider\Validation;

class AuditProvider
{
    public function url()
    {
        return array(
            array(true, 'http://localhost'),
            array(true, 'http://google.com'),
            array(true, 'http://www.google.com'),
            array(true, 'https://www.google.com'),
            array(false, 'google.com'),
            array(false, 'google.co.id'),
            array(true, 'http://www.example.com/space%20here.html'),
            array(false, 'http://www.example.com/space here.html'),
        );
    }

    public function email()
    {
        return array(
            array(true, 'my@gmail.com'),
            array(true, 'my123.456@gmail.com'),
            array(true, 'niceandsimple@google.com', false),
            array(true, 'very.common@google.com', false),
            array(true, 'a.little.lengthy.but.fine@google.com', false),
            array(true, 'disposable.email.with+symbol@google.com', false),
            array(true, 'user@[IPv6:2001:db8:1ff::a0b:dbd0]', false),
            array(true, '"very.unusual.@.unusual.com"@google.com', false),
            array(true, '!#$%&\'*+-/=?^_`{}|~@google.com', false),
            array(true, '""@google.com', false),
            array(true, 'niceandsimple@google.com', true),
            array(true, 'very.common@google.com', true),
            array(true, 'a.little.lengthy.but.fine@google.com', true),
            array(true, 'disposable.email.with+symbol@google.com', true),
            array(true, 'user@[IPv6:2001:db8:1ff::a0b:dbd0]', false, true),
            array(true, '"very.unusual.@.unusual.com"@google.com', true),
            array(true, '!#$%&\'*+-/=?^_`{}|~@google.com', true),
            array(true, '""@google.com', true),
            array(false, 'gmail.com', false),
            array(false, 'Abc.google.com', false),
            array(false, 'Abc.@google.com', false),
            array(false, 'Abc..123@google.com', false),
            array(false, 'A@b@c@google.com', false),
            array(false, 'a"b(c)d,e:f;g<h>i[j\k]l@google.com', false),
            array(false, 'just"not"right@google.com', false),
            array(false, 'this is"not\allowed@google.com', false),
            array(false, 'this\ still\"not\\allowed@google.com', false),
            array(false, 'Abc.google.com', true),
            array(false, 'Abc.@google.com', true),
            array(false, 'Abc..123@google.com', true),
            array(false, 'A@b@c@google.com', true),
            array(false, 'a"b(c)d,e:f;g<h>i[j\k]l@google.com', true),
            array(false, 'just"not"right@google.com', true),
            array(false, 'this is"not\allowed@google.com', true),
            array(false, 'this\ still\"not\\allowed@google.com', true),
        );
    }

    public function ipv4()
    {
        return array(
            array(true, '0.0.0.0'),
            array(true, '127.0.0.1'),
            array(true, '192.168.1.1'),
            array(true, '30.88.29.1'),
            array(true, '192.168.100.48'),
            array(false, ''),
            array(false, '...'),
            array(false, 'hello, world'),
            array(false, '256.256.0.0'),
            array(false, '255.255.255.'),
            array(false, '.255.255.255'),
            array(false, '172.300.256.100'),
        );
    }

    public function ipv6()
    {
        return array(
            array(true, '::'),
            array(true, '::1'),
            array(true, '2002::'),
            array(true, '::ffff:192.0.2.128'),
            array(true, '0:0:0:0:0:0:0:1'),
            array(true, '2001:DB8:0:0:8:800:200C:417A'),
            array(false, ''),
            array(false, 'FF01::101::2'),
            array(false, '::1.256.3.4'),
            array(false, '2001:DB8:0:0:8:800:200C:417A:221'),
            array(false, 'FF02:0000:0000:0000:0000:0000:0000:0000:0001'),
        );
    }

    public function isPrivate()
    {
        return array(
            array(true, 'fc00::'),
            array(true, '10.10.10.10'),
            array(true, '172.16.93.7'),
            array(true, '192.168.3.5'),
            array(false, '0.1.2.3'),
            array(false, '201.176.14.4'),
        );
    }

    public function isReserved()
    {
        return array(
            array(true, '::1'),
            array(true, '127.0.0.1'),
            array(true, '0.1.2.3'),
            array(true, '169.254.1.2'),
            array(true, '240.241.242.243'),
            array(false, '192.0.2.1'),
            array(false, '224.225.226.227'),
            array(false, '193.194.195.196'),
        );
    }

    public function isPublic()
    {
        return array(
            array(true, '180.1.1.0'),
            array(false, '127.0.0.1'),
            array(false, '0.0.0.0'),
        );
    }

    public function userAgent()
    {
        return array(
            array('mobile', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G960F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.84 Mobile Safari/537.36'),
            array('mobile', 'Mozilla/5.0 (Linux; Android 6.0.1; SM-G920V Build/MMB29K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'),
            array('mobile', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 6P Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.83 Mobile Safari/537.36'),
            array('mobile', 'Mozilla/5.0 (Linux; Android 7.1.1; G8231 Build/41.2.A.0.219; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/59.0.3071.125 Mobile Safari/537.36'),
            array('mobile', 'Mozilla/5.0 (Linux; Android 6.0; HTC One X10 Build/MRA58K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/61.0.3163.98 Mobile Safari/537.36'),
            array('mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1'),
            array('mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) Version/11.0 Mobile/15A5341f Safari/604.1'),
            array('mobile', 'Mozilla/5.0 (Windows Phone 10.0; Android 6.0.1; Microsoft; RM-1152) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Mobile Safari/537.36 Edge/15.15254'),
            array('mobile', 'Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; RM-1127_16056) AppleWebKit/537.36(KHTML, like Gecko) Chrome/42.0.2311.135 Mobile Safari/537.36 Edge/12.10536'),
            array('mobile', 'Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Mobile Safari/537.36 Edge/13.1058'),
            array('mobile', 'Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Mobile Safari/537.36 Edge/13.1058'),
            array('mobile', 'Mozilla/5.0 (Linux; Android 6.0.1; SHIELD Tablet K1 Build/MRA58K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Safari/537.36'),
            array('mobile', 'Mozilla/5.0 (Linux; Android 7.0; SM-T827R4 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.116 Safari/537.36'),
            array('desktop', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36'),
            array('desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246'),
            array('desktop', 'Mozilla/5.0 (X11; CrOS x86_64 8172.45.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.64 Safari/537.36'),
            array('desktop', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9'),
            array('desktop', 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36'),
            array('desktop', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1'),
            array('bot', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'),
            array('bot', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'),
            array('bot', 'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)'),
        );
    }

    public function mod10()
    {
        return array(
            array(true, '378282246310005'),
            array(false, '378282246310001'),
            array(false, 'foo'),
        );
    }

    public function entropy()
    {
        return array(
            array(4.0, 'a'),
            array(4.0, 'f'),
            array(8.0, 'foo'),
            array(21.0, '!@3klds48;'),
            array(14.0, 'secret'),
            array(18.0, 'password'),
            array(19.5, 'p4ss_w0rd'),
            array(25.5, 'dK2#!b846'),
        );
    }

    public function card()
    {
        return array(
            array('American Express', '378282246310005'),
            array('American Express', '371449635398431'),
            array('American Express', '378734493671000'),
            array('Diners Club', '30569309025904'),
            array('Diners Club', '38520000023237'),
            array('Discover', '6011111111111117'),
            array('Discover', '6011000990139424'),
            array('JCB', '3530111333300000'),
            array('JCB', '3566002020360505'),
            array('MasterCard', '5555555555554444'),
            array('MasterCard', '2221000010000015'),
            array('MasterCard', '5105105105105100'),
            array('Visa', '4222222222222'),
            array('Visa', '4111111111111111'),
            array('Visa', '4012888888881881'),
            array(null, '4012888888881882'),
        );
    }
}
