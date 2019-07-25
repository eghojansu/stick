<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\TestSuite\Provider\Cli;

class ConsoleProvider
{
    public function colorize()
    {
        return array(
            array('info', 'info', 'info'),
            array("\033[37;41merror\033[39;49m", 'error', '<error>error</error>'),
            array("\033[37;41merror\033[39;49m, \033[32minfo\033[39m and \033[33mcomment\033[39m", 'error, info and comment', '<error>error</error>, <info>info</> and <comment>comment</>'),
            array("\033[32minfo\033[39m", 'info', '<info>info</info>'),
            array("\033[33mcomment\033[39m", 'comment', '<comment>comment</comment>'),
            array("\033[30;46mquestion\033[39;49m", 'question', '<question>question</question>'),
            array("\033[37;41merror\033[39;49m: Foo", 'error: Foo', '<error>error</error>: Foo'),
            array("\033[37;41merror\033[39;49m: Foo \033[32minfo\033[39m Bar ", 'error: Foo info Bar ', '<error>error</>: Foo <info>info</info> Bar '),
            array("\033[37;41merror\033[39;49m <custom>custom</>", 'error <custom>custom</>', '<error>error</error> <custom>custom</>'),
            array('null', 'null', '<null>null</>'),
            array("\033[37;1mwhite bold\033[39;22m", 'white bold', '<bold>white bold</>'),
        );
    }

    public function run()
    {
        return array(
            'app version' => array(
                "\033[32meghojansu/stick\033[39m \033[33mv0.1.0-beta\033[39m\n",
                array(
                    'SERVER.argv' => array('entry', '-v'),
                ),
            ),
            'help list command' => array(
                "List available commands\n\n".
                "\033[33mUsage:\033[39m\n".
                "  list [options]\n\n".
                "\033[33mOptions:\033[39m\n".
                "  \033[32m-h, --help   \033[39m Display command help\n".
                "  \033[32m-v, --version\033[39m Display application version\n",
                array(
                    'SERVER.argv' => array('entry', 'list', '-h'),
                ),
            ),
            'command throws exception' => array(
                "\033[37;41mAn error occured\033[39;49m\n\n".
                "  \033[33mLogicException\033[39m\n".
                "  \033[32mI am an exception.\033[39m\n\n".
                "[Thrown at ::file:: line ::line::]\n",
                array(
                    'SERVER.argv' => array('entry', 'show:error'),
                ),
            ),
        );
    }
}
