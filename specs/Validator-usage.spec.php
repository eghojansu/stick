<?php

use Ekok\Stick\Validation\Validator;
use Ekok\Stick\Validation\Context;
use Ekok\Stick\Validation\ProviderInterface;

describe('Validator Usage', function() {
    it('should be customizable', function () {
        $validator = new Validator();
        $provider = $validator->getProviders()[0];

        $provider->addMessages(array(
            'foo' => 'bar',
        ));

        expect($provider->getMessages())->to->contain->keys(array('foo'));
    });

    it('throws exception if rule is not defined', function () {
        expect(function () {
            (new Validator())->validate(array(
                'foo' => 'bar',
            ), array());
        })->to->throw('InvalidArgumentException', 'Rule provider not found: bar.');
    });

    it('can validate', function() {
        $base = array(
            'today' => date('Y-m-d'),
        );
        $data = array(
            'today' => 'today',
            'foo' => 'bar',
            'arr_foo' => array('foo', 'bar'),
            'accepted' => 1,
            'after' => 'tomorrow',
            'after_or_equal' => 'today',
            'alpha' => 'alpha',
            'alnum' => 'alnum123',
            'array' => array('foo', 'bar', 'baz'),
            'before' => 'yesterday',
            'before_or_equal' => 'today',
            'between' => '2',
            'boolean' => '1',
            'confirmed' => 'bar',
            'date' => $base['today'],
            'date_equal' => 'today',
            'date_format' => $base['today'],
            'different' => 'yesterday',
            'digits' => '0123456789',
            'digits_between' => '12',
            'distinct' => array('foo', 'bar', 'baz'),
            'email' => 'email@example.com',
            'ends_with' => 'foobar',
            'exclude' => 'exclude',
            'exclude_if' => 'exclude_if',
            'exclude_unless' => 'exclude_unless',
            'exclude_unless_true' => 'bar',
            'gt' => 'foobar',
            'gte' => 'foo',
            'in' => 'foo',
            'in_array' => 'foo',
            'integer' => '123',
            'ip' => '189.43.5.56',
            'ip4' => '189.43.5.56',
            'ip6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'json' => '{"foo":"bar"}',
            'lt' => 'fo',
            'lte' => 'foo',
            'match' => 'foo',
            'max' => 'foo',
            'min' => 'foo',
            'not_in' => 'baz',
            'not_match' => 'bar',
            'numeric' => '12.34',
            'optional' => 'optional',
            // 'optional_exclude' => null,
            'required' => 'required',
            'required_if' => 'required_if',
            'required_unless' => 'required_unless',
            'same' => 'foo',
            'size' => 'foo',
            'starts_with' => 'foobar',
            'string' => 'foobar',
            'url' => 'http://example.com',
            'trim' => ' foo ',
            'rtrim' => ' foo ',
            'ltrim' => ' foo ',
        );
        $rules = array(
            'accepted' => 'accepted',
            'after' => 'after:today',
            'after_or_equal' => 'after_or_equal:today',
            'alpha' => 'alpha',
            'alnum' => 'alnum',
            'array' => 'array|size:3',
            'before' => 'before:today',
            'before_or_equal' => 'before_or_equal:today',
            'between' => 'between:1,3',
            'boolean' => 'boolean',
            'confirmed' => 'confirmed:foo',
            'date' => 'date',
            'date_equal' => 'date_equal:today',
            'date_format' => 'date_format:Y-m-d',
            'different' => 'different:today',
            'digits' => 'digits',
            'digits_between' => 'digits_between:1,3',
            'distinct' => 'distinct',
            'email' => 'email',
            'ends_with' => 'ends_with:bar',
            'exclude' => 'exclude',
            'exclude_if' => 'exclude_if:foo,bar',
            'exclude_unless' => 'exclude_unless:foo,baz',
            'exclude_unless_true' => 'exclude_unless:foo,bar',
            'gt' => 'gt:foo',
            'gte' => 'gte:foo',
            'in' => 'in:foo,bar',
            'in_array' => 'in_array:arr_foo',
            'integer' => 'integer',
            'ip' => 'ip',
            'ip4' => 'ip4',
            'ip6' => 'ip6',
            'json' => 'json:true',
            'lt' => 'lt:foo',
            'lte' => 'lte:foo',
            'match' => 'match:/^foo$/',
            'max' => 'max:3',
            'min' => 'min:3',
            'not_in' => 'not_in:foo,bar',
            'not_match' => 'not_match:/^foo$/',
            'numeric' => 'numeric',
            'optional' => 'optional',
            'optional_exclude' => 'optional:true',
            'required' => 'required',
            'required_if' => 'required_if:foo,bar',
            'required_unless' => 'required_unless:foo,bar',
            'same' => 'same:foo',
            'size' => 'size:3',
            'starts_with' => 'starts_with:foo',
            'string' => 'string',
            'url' => 'url',
            'trim' => 'trim',
            'rtrim' => 'rtrim',
            'ltrim' => 'ltrim',
        );
        $expected = array(
            'accepted' => 1,
            'after' => 'tomorrow',
            'after_or_equal' => 'today',
            'alpha' => 'alpha',
            'alnum' => 'alnum123',
            'array' => array('foo', 'bar', 'baz'),
            'before' => 'yesterday',
            'before_or_equal' => 'today',
            'between' => '2',
            'boolean' => true,
            'confirmed' => 'bar',
            'date' => $base['today'],
            'date_equal' => 'today',
            'date_format' => $base['today'],
            'different' => 'yesterday',
            'digits' => '0123456789',
            'digits_between' => '12',
            'distinct' => array('foo', 'bar', 'baz'),
            'email' => 'email@example.com',
            'ends_with' => 'foobar',
            // 'exclude' => 'exclude',
            // 'exclude_if' => 'exclude_if',
            // 'exclude_unless' => 'exclude_unless',
            'exclude_unless_true' => 'bar',
            'gt' => 'foobar',
            'gte' => 'foo',
            'in' => 'foo',
            'in_array' => 'foo',
            'integer' => 123,
            'ip' => '189.43.5.56',
            'ip4' => '189.43.5.56',
            'ip6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'json' => array('foo' => 'bar'),
            'lt' => 'fo',
            'lte' => 'foo',
            'match' => 'foo',
            'max' => 'foo',
            'min' => 'foo',
            'not_in' => 'baz',
            'not_match' => 'bar',
            'numeric' => 12.34,
            'optional' => 'optional',
            // 'optional_exclude' => 'optional',
            'required' => 'required',
            'required_if' => 'required_if',
            'required_unless' => 'required_unless',
            'same' => 'foo',
            'size' => 'foo',
            'starts_with' => 'foobar',
            'string' => 'foobar',
            'url' => 'http://example.com',
            'trim' => 'foo',
            'rtrim' => ' foo',
            'ltrim' => 'foo ',
        );

        $validator = new Validator();
        $success = $validator->validate($rules, $data);
        $result = $validator->getResult();

        expect($success)->to->be->true;
        expect($result->getData())->to->be->equal($expected);
    });

    it('can show violations', function () {
        $today = date('Y-m-d', strtotime('today'));
        $tomorrow = date('Y-m-d', strtotime('tomorrow'));

        $rules = array(
            'required' => 'required',
            'starts_with' => 'starts_with:foo,bar',
            'ends_with' => 'ends_with:foo,bar',
            'before' => 'before:' . $today,
        );
        $data = array(
            'in' => 'd',
            'starts_with' => 'bazqux',
            'ends_with' => 'bazqux',
            'before' => $tomorrow,
        );
        $expected = array(
            'required' => array('This value should not be blank.'),
            'starts_with' => array("'bazqux' should starts with foo or bar."),
            'ends_with' => array("This value should ends with one of these values: ['foo','bar']."),
            'before' => array("This value should be before '{$today}'."),
        );

        $validator = new Validator();
        $validator->getProviders()[0]->addMessage('starts_with', "{value} should starts with foo or bar.");
        $success = $validator->validate($rules, $data);
        $result = $validator->getResult();

        expect($success)->to->be->false;
        expect($result->invalid())->to->be->true;
        expect($result->getErrors())->to->be->equal($expected);
        expect($result->getError('required'))->to->be->equal($expected['required']);

        // enable options
        $options = array(
            'skipOnError' => true,
        );
        $expected = array(
            'required' => array('This value should not be blank.'),
        );
        $success = $validator->validate($rules, $data, $options);
        $result = $validator->getResult();

        expect($success)->to->be->false;
        expect($result->getErrors())->to->be->equal($expected);
    });

    it('can validate with dot style notation', function() {
        $rules = array(
            'foo.*.name' => 'required|confirmed|string|min:3',
            'foo.*.age' => 'required|integer|min:15',
        );
        $data = array(
            'foo' => array(
                array('name' => 'foobar', 'age' => 15, 'name_confirmation' => 'foobar'),
                array('name' => 'barbaz', 'age' => 16, 'name_confirmation' => 'barbaz'),
            ),
        );
        $expected = array(
            'foo' => array(
                array('name' => 'foobar', 'age' => 15),
                array('name' => 'barbaz', 'age' => 16),
            ),
        );

        $validator = new Validator();
        $success = $validator->validate($rules, $data);
        $result = $validator->getResult();

        expect($success)->to->be->true;
        expect($result->getData())->to->be->equal($expected);
    });

    it('can handle dot style validation, event it has violations', function () {
        $rules = array(
            'name' => 'required|string|min:5',
            'addresses.*.street' => 'required|string|min:3|ends_with:st',
            'tags.*' => 'required|string|min:3',
        );
        $data = array(
            'name' => 'whataname',
            'addresses' => array(
                array('street' => '1 street name st'),
                array('street' => '2 street name st'),
            ),
            'age' => 'integer|min:18',
            'tags' => array(
                'first',
                'second',
                'third',
            ),
        );
        $expected = array(
            'name' => 'whataname',
            'addresses' => array(
                array('street' => '1 street name st'),
                array('street' => '2 street name st'),
            ),
            'tags' => array(
                'first',
                'second',
                'third',
            ),
        );
        $validator = new Validator();
        $success = $validator->validate($rules, $data);
        $result = $validator->getResult();

        expect($success)->to->be->true;
        expect($result->getData())->to->be->equal($expected);

        // with invalid data
        $data = array(
            'name' => 'whataname',
            'addresses' => array(
                array('street' => '1 street name st'),
                array('street' => '2 street name'),
            ),
        );
        $expected = array(
            'addresses' => array(
                1 => array('street' => array("This value should ends with one of these values: ['st'].")),
            ),
            'tags' => array(
                0 => array('This value should not be blank.'),
            ),
        );
        $success = $validator->validate($rules, $data);
        $result = $validator->getResult();

        expect($success)->to->be->false;
        expect($result->getErrors())->to->be->equal($expected);
    });

    it('able to handle nested dataset', function () {
        $rules = array(
            'user.*.options.*.name' => 'required|min:3',
            'data.*.optional' => 'exclude_if:optional,null|optional|min:3',
        );
        $data = array(
            'user' => array(
                array(
                    'options' => array(
                        array('name' => 'first'),
                        array('name' => 'second'),
                    ),
                ),
            ),
        );
        $expected = array(
            'user' => array(
                array(
                    'options' => array(
                        array('name' => 'first'),
                        array('name' => 'second'),
                    ),
                ),
            ),
            'data' => array(),
        );
        $validator = new Validator();
        $success = $validator->validate($rules, $data);
        $result = $validator->getResult();

        expect($success)->to->be->true;
        expect($result->getData())->to->be->equal($expected);

        $data = array(
            'data' => array(
                array('optional' => '1'),
            ),
        );
        $expected = array(
            'user' => array(
                array(
                    'options' => array(
                        array('name' => array('This value should not be blank.')),
                    ),
                ),
            ),
            'data' => array(
                array(
                    'optional' => array('This value is too short. It should have 3 characters or more.'),
                )
            )
        );
        $success = $validator->validate($rules, $data);
        $result = $validator->getResult();

        expect($success)->to->be->false;
        expect($result->getErrors())->to->be->equal($expected);
    });

    it('can use validator context', function() {
        $validator = (new Validator())->addProvider(new class implements ProviderInterface
        {
            public function check(string $rule): bool
            {
                return in_array($rule, array('foo', 'bar'));
            }

            public function message(string $rule, Context $context, ...$arguments): string
            {
                return 'This value is not valid.';
            }

            public function validate(string $rule, Context $context, ...$arguments)
            {
                if ('foo' === $rule) {
                    return count($context->getRaw()) === 3 && !$context->hasValue();
                }

                if ('bar' === $rule) {
                    return $context->isDouble() && !$context->isNull();
                }
            }
        });
        $rules = array(
            'foo' => 'foo',
            'bar' => 'numeric|bar',
            'today' => 'date_equal:today',
        );
        $data = array(
            'foo' => 'bar',
            'bar' => '12.03',
            'today' => new \DateTime('today'),
        );
        $success = $validator->validate($rules, $data);

        expect($success)->to->be->true;

        expect(function() use ($validator) {
            $validator->validate(array(
                'after' => 'after:today',
            ), array(
                'after' => 'not_a_date',
            ));
        })->to->throw('RuntimeException', "Both date should be valid date: after.");
    });
});
