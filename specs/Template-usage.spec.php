<?php

use Ekok\Stick\Template\Template;

describe('Template Engine Usage', function() {
    beforeEach(function() {
        $this->template = new Template(array(
            __DIR__ . '/fixtures/templates',
            'simple' => __DIR__ . '/fixtures/templates/simple',
        ));
    });

    it('can use namespaced template', function() {
        $content = $this->template->render('simple:profile', array(
            'name' => 'Jonathan',
        ));

        expect($content)->to->be->contain('Hello, Jonathan');
    });

    it('can use stacked template, with dot notation', function () {
        $content = $this->template->render('stacked.article', array(
            'article' => array(
                'title' => 'Article Title',
                'content' => 'Article content',
            ),
        ));

        expect($content)->to->be->contain('Article Title');
        expect($content)->to->be->contain('Article content');
    });

    it('can load view', function () {
        $content = $this->template->render('profile', array(
            'name' => 'Jonathan',
        ));

        expect($content)->to->be->contain('Hello Jonathan');
        expect($content)->to->be->contain('Example Link');
    });

    it('can load view safely', function () {
        $content = $this->template->render('no-sidebar', array(
            'name' => 'Jonathan',
        ));

        expect($content)->to->be->contain('Hello Jonathan');
        expect($content)->to->be->contain('Default Sidebar Menu');
    });

    it('can used by template', function() {
        $this->template->setOptions(array(
            'extension' => 'prefix.php',
        ));
        $this->template->addGlobals(array(
            'foo' => 'FoO',
            'html' => '<strong>Strong as stone</strong>',
        ));
        $this->template->addGlobal('bar', 'baz');
        $this->template->addFunction('upper', function($str) {
            return strtoupper($str);
        });

        $content = $this->template->render('consumer', array('loadUnknown' => true));
        $options = $this->template->getOptions();
        $directories = $this->template->getDirectories();

        expect($options['extension'])->to->be->equal('prefix.php');
        expect($content)->to->be->contain('Foo is uppercased: FOO.');
        expect($content)->to->be->contain('Bar is displayed as is "baz".');
        expect($content)->to->be->contain('HTML escaped: "&lt;strong&gt;Strong as stone&lt;/strong&gt;".');
        expect($content)->to->be->contain('Loading unknown template fallback.');
        expect($directories)->to->be->include->keys(['default', 'simple']);

        expect(function() {
            $this->template->render('consumer', array('call' => 'unknown'));
        })->to->throw('BadFunctionCallException', 'Function is not found in any context: unknown.');

        expect(function() {
            $this->template->render('consumer', array('selfRendering' => true));
        })->to->throw('LogicException', 'Recursive view rendering is not supported.');

        expect(function() {
            $this->template->render('consumer', array('callParent' => true));
        })->to->throw('LogicException', 'Calling parent when not in section context is forbidden.');

        expect(function() {
            $this->template->render('consumer', array('startContent' => true));
        })->to->throw('InvalidArgumentException', 'Section name is reserved: content.');

        expect(function() {
            $this->template->render('consumer', array('doubleStart' => true));
        })->to->throw('LogicException', 'Nested section is not supported.');

        expect(function() {
            $this->template->render('consumer', array('endContent' => true));
        })->to->throw('LogicException', 'No section has been started.');

        expect(function() {
            $this->template->findPath('unknown');
        })->to->throw('InvalidArgumentException', "Template not found: 'unknown'.");

        expect(function() {
            $this->template->getTemplateDirectories('namespace:unknown');
        })->to->throw('InvalidArgumentException', "Directory not exists for template: 'namespace:unknown'.");
    });

    it('cant set reserved *this* variable', function() {
        expect(function () {
            $this->template->addGlobal('_', 'foo');
        })->to->throw('InvalidArgumentException', 'Variable name is reserved for *this*: _.');

        expect(function () {
            $template = $this->template->createTemplate('simple/profile');
            $template->addData(array('_' => 'foo'));
        })->to->throw('InvalidArgumentException', 'Variable name is reserved for *this*: _.');
    });

    it('can be used like blade template concept', function() {
        $template = $this->template->createTemplate('blade/profile.php');

        $template->addData(array('foo' => 'bar'));
        $content = $template->render();
        $expected = '/Default body content.[\h\v]+This is body from profile.[\h\v]+Default body content 2./';

        expect($template->getName())->to->be->equal('blade/profile.php');
        expect($content)->to->match($expected);
    });

    it('can use relative path', function() {
        $content = $this->template->render('relative/template.php');

        expect($content)->to->be->contain('From template.');
        expect($content)->to->be->contain('From My Relative1.');
        expect($content)->to->be->contain('From My Relative2.');

        expect(function () {
            $this->template->render('relative/template', array('loadUnrelative' => true));
        })->to->throw('InvalidArgumentException', "Relative view not found: './unknown_relative'.");
    });
});
